<?php

namespace Shawm11\Oz\Client;

use Shawm11\Hawk\Client\ClientInterface as HawkClientInterface;

interface ClientInterface
{

    /**
     * @param HawkClientInterface  $hawkClient  Optional Hawk Client dependency
     */
    public function __construct(HawkClientInterface $hawkClient = null);

    /**
     * Generate the value for an HTTP `Authorization` header for a request to
     * the server
     *
     * @param  string|array  $uri  URI of the request or an array from
     *                             `parse_url()`
     * @param  string  $method  HTTP verb of the request (e.g. 'GET', 'POST')
     * @param  array  $ticket  Set of Hawk credentials used by the client to
     *                         access protected resources. Separate from the
     *                         client's Hawk credentials.
     * @param  array  $options  Hawk options that will be integrated in to the
     *                          `Authorization` header value. Includes
     *                          `credentials`, `ext`, `timestamp`, `nonce`,
     *                          `localtimeOffsetMsec`, `payload`, `contentType`,
     *                          `hash`, `app`, and `dlg`
     * @throws ClientException
     * @return array  Contains the `header` (the string the HTTP `Authorization`
     *                header should be set to) and the `artifacts` (the
     *                components used to construct the `header`)
     */
    public function header($uri, $method, $ticket, $options = []);
}
