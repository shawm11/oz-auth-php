<?php

namespace Shawm11\Oz\Server;

use Shawm11\Iron\IronInterface;
use Shawm11\Hawk\Server\ServerInterface as HawkServerInterface;

interface EndpointsInterface
{
    /**
     * @param HawkServerInterface  $hawkServer  Optional Hawk Server dependency
     * @param IronInterface  $iron  Optional Iron dependency
     */
    public function __construct(HawkServerInterface $hawkServer = null, IronInterface $iron = null);

    /**
     * Authenticate an application request, and if valid, issues an application
     * ticket
     *
     * @param  array  $request  Request data, which contains `method`, `url`,
     *                          `host`, `port`, `authorization`, and
     *                          `contentType`
     * @param  array  $options  Configuration options that must include
     *                          `encryptionPassword` and `loadAppFunc`
     * @throws ServerException
     * @return array  An application ticket for the client (application)
     */
    public function app($request, $options);

    /**
     * Reissue an existing ticket (the ticket used to authenticate the request)
     *
     * @param  array  $request  Request data, which contains `method`, `url`,
     *                          `host`, `port`, `authorization`, and
     *                          `contentType`
     * @param  array  $payload  Parsed request body that may contain `issueTo`
     *                          and `scope`
     * @param  array  $options  Configuration options that must include
     *                          `encryptionPassword`, `loadAppFunc`, and
     *                          `loadGrantFunc`
     * @throws ServerException
     * @return array  The reissued ticket
     */
    public function reissue($request, $payload, $options);

    /**
     * Authenticate an application request and if valid and exchange the
     * provided RSVP with a user ticket
     *
     * @param  array  $request  Request data, which contains `method`, `url`,
     *                          `host`, `port`, `authorization`, and
     *                          `contentType`
     * @param  array  $payload  Parsed request body that must contain `rsvp`
     * @param  array  $options  Configuration options that must include
     *                          `encryptionPassword`, `loadAppFunc`, and
     *                          `loadGrantFunc`
     * @throws ServerException
     * @return array  A user ticket for the client (application) to use
     */
    public function rsvp($request, $payload, $options);
}
