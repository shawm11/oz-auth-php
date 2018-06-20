<?php

namespace Shawm11\Oz\Server;

use Shawm11\Hawk\Server\ServerInterface as HawkServerInterface;
use Shawm11\Hawk\Server\Server as HawkServer;
use Shawm11\Hawk\Server\BadRequestException as HawkBadRequestException;
use Shawm11\Hawk\Server\UnauthorizedException as HawkUnauthorizedException;

class Server implements ServerInterface
{
    protected $hawkServer;

    public function __construct(HawkServerInterface $hawkServer = null)
    {
        $this->hawkServer = $hawkServer ? $hawkServer : (new HawkServer);
    }

    public function authenticate($request, $encryptionPassword, $checkExpiration = true, $options = [])
    {
        if (!$encryptionPassword) {
            throw new InternalException('Invalid encryption password');
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
                throw new UnauthorizedExeption('Expired ticket');
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
            throw new BadRequestException($e->getMessage());
        } catch (HawkUnauthorizedException $e) {
            throw new UnauthorizedException($e->getMessage(), $e->getWwwAuthenticateHeaderAttributes());
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage());
        }

        $credentials = $result['credentials'];
        $artifacts = $result['artifacts'];

        /*
         * Check application
         */

        if ($credentials['app'] !== $artifacts['app']) {
            throw new UnauthorizedExeption('Mismatching application ID');
        }

        if ((
                (isset($credentials['dlg']) && $credentials['dlg']) ||
                (isset($artifacts['dlg']) && $artifacts['dlg'])
            ) &&
            $credentials['dlg'] !== $artifacts['dlg']
        ) {
            throw new UnauthorizedExeption('Mismatching delegated application ID');
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
