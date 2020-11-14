<?php

namespace Shawm11\Oz\Server;

use Shawm11\Iron\IronInterface;
use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Hawk\Server\ServerInterface as HawkServerInterface;
use Shawm11\Hawk\Server\Server as HawkServer;
use Shawm11\Hawk\Server\BadRequestException as HawkBadRequestException;
use Shawm11\Hawk\Server\UnauthorizedException as HawkUnauthorizedException;

class Endpoints implements EndpointsInterface
{
    /**
     * Hawk server dependency
     *
     * @var HawkServerInterface
     */
    protected $hawkServer;

    /**
     * Iron dependency
     *
     * @var IronInterface
     */
    protected $iron;

    /** @var array */
    protected $schema = [];

    /** @var array */
    protected $allowedGrantTypes = ['rsvp', 'user_credentials', 'implicit'];

    /**
     * {@inheritdoc}
     */
    public function __construct(HawkServerInterface $hawkServer = null, IronInterface $iron = null)
    {
        $this->hawkServer = $hawkServer ? $hawkServer : (new HawkServer);

        $this->iron = $iron
            ? $iron
            : (new \Shawm11\Iron\Iron(\Shawm11\Iron\IronOptions::$defaults));
    }

    /**
     * {@inheritdoc}
     */
    public function app($request, $options)
    {
        $loadAppFunc = isset($options['loadAppFunc']) ? $options['loadAppFunc'] : null;
        $hawkOptions = isset($options['hawk']) ? $options['hawk'] : null;

        try {
            $credentials = $this->hawkServer
                                ->authenticate($request, $loadAppFunc, $hawkOptions)['credentials'];
        } catch (HawkBadRequestException $e) {
            throw new BadRequestException($e->getMessage(), $e->getCode(), $e);
        } catch (HawkUnauthorizedException $e) {
            throw new UnauthorizedException(
                $e->getMessage(),
                $e->getWwwAuthenticateHeaderAttributes(),
                $e->getCode(),
                $e
            );
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $ticketOptions = isset($options['ticket']) ? $options['ticket'] : null;

        return (new Ticket($options['encryptionPassword'], $ticketOptions, $this->iron))
                    ->issue($credentials, null);
    }

    /**
     * {@inheritdoc}
     */
    public function reissue($request, $payload, $options)
    {
        $payload = $payload ? $payload : [];

        $encryptionPassword = isset($options['encryptionPassword']) ? $options['encryptionPassword'] : null;
        $decryptionPasswords = isset($options['decryptionPasswords'])
            ? $options['decryptionPasswords']
            : $encryptionPassword;

        $ticket = (new Server($this->hawkServer, $this->iron))
                    ->authenticate($request, $decryptionPasswords, false, $options)['ticket'];

        /*
         * Load grant
         */

        $ticketGrant = empty($ticket['grant']) ? null : $ticket['grant'];
        $grantType = null;
        $grant = null;
        $ext = null;

        if ($ticketGrant) {
            $grantResult = $options['loadGrantFunc']($ticket['grant']);
            $grant = $grantResult['grant'];
            $grantType = isset($grant['type']) ? $grant['type'] : 'rsvp';
            $ext = isset($grantResult['ext']) ? $grantResult['ext'] : null;
            $ticketDlg = isset($ticket['dlg']) ? $ticket['dlg'] : null;

            if (!$grant ||
                ($grant['app'] !== $ticket['app'] && $grant['app'] !== $ticketDlg) ||
                $grant['user'] !== $ticket['user'] ||
                empty($grant['exp']) ||
                $grant['exp'] <= (new HawkUtils)->now()
            ) {
                throw new UnauthorizedException('Invalid grant');
            }
        }

        $reissue = function (
            $grant = null,
            $ext = null
        ) use (
            $options,
            $payload,
            $encryptionPassword,
            $ticket,
            $grantType
        ) {
            $ticketOptions = isset($options['ticket']) ? $options['ticket'] : [];

            if ($ext) {
                $ticketOptions['ext'] = $ext;
            }

            if ($grantType !== 'implicit' && !empty($payload['issueTo'])) {
                $ticketOptions['issueTo'] = $payload['issueTo'];
            }

            if (!empty($payload['scope'])) {
                $ticketOptions['scope'] = $payload['scope'];
            }

            return (new Ticket($encryptionPassword, $ticketOptions, $this->iron))->reissue($ticket, $grant);
        };

        /*
         * Load app
         */

        if ($grantType !== 'implicit') {
            $app = $options['loadAppFunc']($ticket['app']);

            if (!$app) {
                throw new UnauthorizedException('Invalid application');
            }

            if (!empty($payload['issueTo']) && empty($app['delegate'])) {
                throw new ForbiddenException('Application has no delegation rights');
            }
        }

        /*
         * Reissue ticket
         */

        if (!$ticketGrant) { // application ticket
            return $reissue();
        }

        return $reissue($grant, $ext); // user ticket
    }

    /**
     * {@inheritdoc}
     */
    public function rsvp($request, $payload, $options)
    {
        if (!$payload) {
            throw new BadRequestException('Missing required payload');
        }

        $encryptionPassword = isset($options['encryptionPassword']) ? $options['encryptionPassword'] : null;
        $decryptionPasswords = isset($options['decryptionPasswords'])
            ? $options['decryptionPasswords']
            : $encryptionPassword;

        $ticket = (new Server($this->hawkServer, $this->iron))
                    ->authenticate($request, $decryptionPasswords, true, $options)['ticket'];

        if (!empty($ticket['user'])) {
            throw new UnauthorizedException('User ticket cannot be used on an application endpoint');
        }

        $ticketOptions = isset($options['ticket']) ? $options['ticket'] : [];
        $rsvp = isset($payload['rsvp']) ? $payload['rsvp'] : null;

        $ticketClass = (new Ticket($encryptionPassword, $ticketOptions, $this->iron));

        $envelope = $ticketClass->parse($rsvp);

        if ($envelope['app'] !== $ticket['app']) {
            throw new ForbiddenException('Mismatching ticket and RSVP apps');
        }

        $now = (new HawkUtils)->now();

        if ($envelope['exp'] <= $now) {
            throw new ForbiddenException('Expired RSVP');
        }

        $grantResult = $options['loadGrantFunc']($envelope['grant']);

        if (!$grantResult) {
            throw new ForbiddenException('Invalid grant');
        }

        $grant = $grantResult['grant'];
        $ext = isset($grantResult['ext']) ? $grantResult['ext'] : null;

        $grant['type'] = isset($grant['type']) ? $grant['type'] : 'rsvp';

        if (!$grant ||
            $grant['app'] !== $ticket['app'] ||
            empty($grant['exp']) ||
            $grant['exp'] <= $now ||
            $grant['type'] !== 'rsvp'
        ) {
            throw new ForbiddenException('Invalid grant');
        }

        $app = $options['loadAppFunc']($grant['app']);

        if (!$app) {
            throw new ForbiddenException('Invalid application');
        }

        if ($ext) {
            $ticketOptions['ext'] = $ext;
        }

        return $ticketClass->issue($app, $grant);
    }

    /**
     * {@inheritdoc}
     */
    public function user($request, $payload, $options)
    {
        if (!$payload) {
            throw new BadRequestException('Missing required payload');
        }

        $allowedGrantTypes = isset($options['allowedGrantTypes'])
            ? $options['allowedGrantTypes']
            : $this->allowedGrantTypes;

        // If the application is attempting make an authenticated request by
        // setting the `Authorization` header for Hawk
        $isAuthRequest = isset($request['authorization']);

        if ($isAuthRequest && !in_array('user_credentials', $allowedGrantTypes)) {
            throw new UnauthorizedException('User credentials grant not allowed');
        }

        if (!$isAuthRequest && !in_array('implicit', $allowedGrantTypes)) {
            throw new UnauthorizedException('Implicit grant not allowed');
        }

        $encryptionPassword = isset($options['encryptionPassword']) ? $options['encryptionPassword'] : null;
        $decryptionPasswords = isset($options['decryptionPasswords'])
            ? $options['decryptionPasswords']
            : $encryptionPassword;
        $ticket = [];

        if ($isAuthRequest) {
            $ticket = (new Server($this->hawkServer, $this->iron))->authenticate(
                $request,
                $decryptionPasswords,
                true,
                $options
            )['ticket'];

            if (!empty($ticket['user'])) {
                throw new UnauthorizedException('User ticket cannot be used on an application endpoint');
            }
        }

        $ticketOptions = isset($options['ticket']) ? $options['ticket'] : [];

        /*
         * Verify user credentials
         */

        $userCredentials = isset($payload['user']) ? $payload['user'] : null;
        $userId = $options['verifyUserFunc']($userCredentials);

        if (!$userId) {
            throw new ForbiddenException('Invalid user credentials');
        }

        /*
         * Load app
         */

        $app = null;

        if ($isAuthRequest) {
            $app = $options['loadAppFunc']($ticket['app']);

            if (!$app) {
                throw new ForbiddenException('Invalid application');
            }
        }

        /*
         * Create grant
         */

        if (empty($options['grant'])) {
            throw new ServerException('Invalid grant options');
        }

        $grant = [];
        $grant['exp'] = empty($options['grant']['exp']) ? null : $options['grant']['exp'];
        $grant['scope'] = empty($options['grant']['scope']) ? [] : $options['grant']['scope'];
        $grant['user'] = $userId;
        $grant['app'] = $app ? $app['id'] : null;
        $grant['type'] = $app ? 'user_credentials' : 'implicit';

        $storedGrantId = $options['storeGrantFunc']($grant);

        if (!$storedGrantId || gettype($storedGrantId) !== 'string') {
            throw new ServerException('Invalid stored grant ID');
        }

        $grant['id'] = $storedGrantId;

        /*
         * Issue ticket
         */

        return (new Ticket($encryptionPassword, $ticketOptions, $this->iron))->issue($app, $grant);
    }
}
