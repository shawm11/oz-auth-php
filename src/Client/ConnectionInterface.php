<?php

namespace Shawm11\Oz\Client;

use Shawm11\Hawk\Client\ClientInterface as HawkClientInterface;

interface ConnectionInterface
{
    /**
     * @param array  $settings  Includes `endpoints`, `uri`, and `credentials`
     * @param HawkClientInterface  $hawkClient  Optional Hawk Client dependency
     */
    public function __construct($settings, HawkClientInterface $hawkClient = null);

    /**
     * Request a protected resource
     *
     * @param  string  $path  URL of the request relative to the host (e.g.
     *                        `/resource`)
     * @param  array  $ticket  Application or user ticket for the client. If the
     *                         ticket is expired, there will be an attempt to
     *                         automatically refresh it.
     * @param  array  $options  Optional configuration. May include `method` and
     *                          `payload`
     * @throws ClientException
     * @return array  The requested resource (parsed to array if JSON), HTTP
     *                response code, and the ticket used to make the request
     */
    public function request($path, $ticket, $options = []);

    /**
     * Request a protected resource using a shared application ticket
     *
     * @param  string  $path  URL of the request relative to the host (e.g.
     *                        `/resource`)
     * @param  array  $options  Optional configuration. May include `method` and
     *                          `payload`
     * @throws ClientException
     * @return array  The requested resource (parsed to array if JSON), HTTP
     *                response code, and the ticket used to make the request
     */
    public function app($path, $options = []);

    /**
     * Reissue (refresh) a ticket
     *
     * @param  array  $ticket  Ticket being reissued
     * @throws ClientException
     * @return array  Reissued ticket
     */
    public function reissue($ticket);
}
