<?php

namespace Shawm11\Oz\Server;

use Shawm11\Hawk\Server\ServerInterface as HawkServerInterface;

interface ServerInterface
{
    /**
     * @param HawkServerInterface  $hawkServer  Optional Hawk Server dependency
     */
    public function __construct(HawkServerInterface $hawkServer = null);

    /**
     * Validate an incoming request using Hawk and performs additional
     * Oz-specific validations. If the request is valid, an application ticket
     * is issued
     *
     * @param  array  $request  Request data, which contains `method`, `url`,
     *                          `host`, `port`, `authorization`, and
     *                          `contentType`
     * @param  string|array  $encryptionPassword  A password used to generate
     *                                      the ticket encryption key. Must be
     *                                      kept confidential by the server.
     * @param  boolean  $checkExpiration  If the ticket expiration should be
     *                                    checked
     * @param  array  $options  May contain ticket configuration (`ticket`) and
     *                          Hawk configuration (`hawk`)
     * @throws ServerException
     * @return array  Contains the application ticket (`ticket`) and Hawk
     *                artifacts (`artifacts`)
     */
    public function authenticate($request, $encryptionPassword, $checkExpiration = true, $options = []);
}
