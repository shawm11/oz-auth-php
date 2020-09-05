<?php

namespace Shawm11\Oz\Server;

use Shawm11\Hawk\Server\ServerInterface as HawkServerInterface;
use Shawm11\Hawk\Server\Server as HawkServer;
use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Hawk\Server\BadRequestException as HawkBadRequestException;
use Shawm11\Hawk\Server\UnauthorizedException as HawkUnauthorizedException;

class Server implements ServerInterface
{
    /**
     * Hawk server dependency
     *
     * @var HawkServerInterface
     */
    protected $hawkServer;

    /**
     * {@inheritdoc}
     */
    public function __construct(HawkServerInterface $hawkServer = null)
    {
        $this->hawkServer = $hawkServer ? $hawkServer : (new HawkServer);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate($request, $encryptionPassword, $checkExpiration = true, $options = [])
    {
        if (!$encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        /*
         * Hawk credentials lookup method
         */

        $credentialsFunc = function ($id) use ($options, $encryptionPassword, $checkExpiration) {
            /*
             * Parse ticket ID
             */

            $ticketOptions = isset($options['ticket']) ? $options['ticket'] : null;
            $ticket = (new Ticket($encryptionPassword, $ticketOptions))->parse($id);

            /*
             * Check expiration
             */

            if ($checkExpiration && ($ticket['exp'] <= (new HawkUtils)->now())) {
                throw new UnauthorizedException('Expired ticket');
            }

            return $ticket;
        };

        /*
         * Hawk authentication
         */

        $hawkOptions = isset($options['hawk']) ? $options['hawk'] : null;

        try {
            $result = $this->hawkServer->authenticate($request, $credentialsFunc, $hawkOptions);
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

        $credentials = $result['credentials'];
        $artifacts = $result['artifacts'];

        /*
         * Check application
         */

        if ($credentials['app'] !== $artifacts['app']) {
            throw new UnauthorizedException('Mismatching application ID');
        }

        $credentialsDlg = isset($credentials['dlg']) ? $credentials['dlg'] : null;
        $artifactsDlg = isset($artifacts['dlg']) ? $artifacts['dlg'] : null;

        if (($credentialsDlg || $artifactsDlg ) &&
            $credentialsDlg !== $artifactsDlg
        ) {
            throw new UnauthorizedException('Mismatching delegated application ID');
        }

        /*
         * Return result
         */

        return [
            'ticket' => $credentials,
            'artifacts' => $artifacts
        ];
    }
}
