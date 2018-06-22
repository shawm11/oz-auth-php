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
    protected $hawkServer;
    protected $iron;
    protected $schema = [];

    public function __construct(HawkServerInterface $hawkServer = null, IronInterface $iron = null)
    {
        $this->hawkServer = $hawkServer ? $hawkServer : (new HawkServer);

        $this->iron = $iron
            ? $iron
            : (new \Shawm11\Iron\Iron(\Shawm11\Iron\IronOptions::$defaults));
    }

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

    public function reissue($request, $payload, $options)
    {
        $payload = $payload ? $payload : [];

        $encryptionPassword = isset($options['encryptionPassword']) ? $options['encryptionPassword'] : null;

        $ticket = (new Server($this->hawkServer))
                    ->authenticate($request, $encryptionPassword, false, $options)['ticket'];

        /*
         * Load ticket
         */

        $app = $options['loadAppFunc']($ticket['app']);

        if (!$app) {
            throw new UnauthorizedException('Invalid application');
        }

        if ((isset($payload['issueTo']) && $payload['issueTo']) &&
            !(isset($app['delegate']) && $app['delegate'])
        ) {
            throw new ForbiddenException('Application has no delegation rights');
        }

        $reissue = function ($grant = null, $ext = null) use ($options, $payload, $encryptionPassword, $ticket) {
            $ticketOptions = isset($options['ticket']) ? $options['ticket'] : [];

            if ($ext) {
                $ticketOptions['ext'] = $ext;
            }

            if (isset($payload['issueTo']) && $payload['issueTo']) {
                $ticketOptions['issueTo'] = $payload['issueTo'];
            }

            if (isset($payload['scope']) && $payload['scope']) {
                $ticketOptions['scope'] = $payload['scope'];
            }

            return (new Ticket($encryptionPassword, $ticketOptions, $this->iron))->reissue($ticket, $grant);
        };

        /*
         * Application ticket
         */

        if (!(isset($ticket['grant']) && $ticket['grant'])) {
            return $reissue();
        }

        /*
         * User ticket
         */

        $grantResult = $options['loadGrantFunc']($ticket['grant']);
        $grant = $grantResult['grant'];
        $ext = isset($grantResult['ext']) ? $grantResult['ext'] : null;
        $ticketDlg = isset($ticket['dlg']) ? $ticket['dlg'] : null;

        if (!$grant ||
            ($grant['app'] !== $ticket['app'] && $grant['app'] !== $ticketDlg) ||
            $grant['user'] !== $ticket['user'] ||
            !(isset($grant['exp']) && $grant['exp']) ||
            $grant['exp'] <= (new HawkUtils)->now()
        ) {
            throw new UnauthorizedException('Invalid grant');
        }

        return $reissue($grant, $ext);
    }

    public function rsvp($request, $payload, $options)
    {
        if (!$payload) {
            throw new BadRequestException('Missing required payload');
        }

        $encryptionPassword = isset($options['encryptionPassword']) ? $options['encryptionPassword'] : null;

        $ticket = (new Server($this->hawkServer))
                    ->authenticate($request, $encryptionPassword, true, $options)['ticket'];

        if (isset($ticket['user']) && $ticket['user']) {
            throw new UnauthorizedException('User ticket cannot be used on an application endpoint');
        }

        $ticketOptions = isset($options['ticket']) ? $options['ticket'] : [];
        $rsvp = isset($payload['rsvp']) ? $payload['rsvp'] : null;

        $ticketClass = (new Ticket($encryptionPassword, $ticketOptions));

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

        if (!$grant ||
            $grant['app'] !== $ticket['app'] ||
            !(isset($grant['exp']) && $grant['exp']) ||
            $grant['exp'] <= $now
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
}
