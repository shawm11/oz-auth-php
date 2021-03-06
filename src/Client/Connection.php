<?php

namespace Shawm11\Oz\Client;

use Shawm11\Hawk\Client\ClientInterface as HawkClientInterface;
use Shawm11\Hawk\Client\Client as HawkClient;

class Connection implements ConnectionInterface
{
    /**
     * Hawk client dependency
     *
     * @var HawkClientInterface
     */
    protected $hawkClient;

    /**
     * Default settings for the connection
     *
     * @var array
     */
    protected $defaults = [
        'endpoints' => [
            'app' => '/oz/app',
            'reissue' => '/oz/reissue',
            'user' => '/oz/user'
        ]
    ];

    /**
     * Connection settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Application ticket
     *
     * @var array|null
     */
    protected $appTicket = null;

    /**
     * User ticket
     *
     * @var array|null
     */
    protected $userTicket = null;

    public function __construct($settings, HawkClientInterface $hawkClient = null)
    {
        $this->settings = array_merge($this->defaults, $settings);
        $this->hawkClient = $hawkClient ? $hawkClient : (new HawkClient);
    }

    /**
     * {@inheritdoc}
     */
    public function request($path, $ticket, $options = [])
    {
        $method = isset($options['method']) ? $options['method'] : 'GET';
        $payload = isset($options['payload']) ? $options['payload'] : null;

        $response = $this->makeRequest($method, $path, $payload, $ticket);
        $code = $response['code'];
        $result = $response['result'];

        if ($code !== 401 || !$result) {
            // No need to reissue ticket
            return [
                'code' => $code,
                'result' => $result,
                'ticket' => $ticket
            ];
        }

        /*
         * Try to reissue ticket
         */

        $reissued = $this->reissue($ticket);

        // Try resource again and pass back the ticket reissued (when not app)
        $response = $this->makeRequest($method, $path, $payload, $reissued);

        return [
            'code' => $response['code'],
            'result' => $response['result'],
            'ticket' => $reissued
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function app($path, $options = [])
    {
        $this->setAppTicket();

        $response = $this->request($path, $this->appTicket, $options);
        $this->appTicket = $response['ticket']; // In case ticket was refreshed

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function reissue($ticket)
    {
        $response = $this->makeRequest('POST', $this->settings['endpoints']['reissue'], null, $ticket);
        $reissued = $response['result'];

        if ($response['code'] !== 200) {
            throw new ClientException($reissued['message']);
        }

        return $reissued;
    }

    /**
     * {@inheritdoc}
     */
    public function requestAppTicket()
    {
        $uri = $this->settings['uri'] . $this->settings['endpoints']['app'];

        try {
            $header = (new Client($this->hawkClient))->header(
                $uri,
                'POST',
                $this->settings['credentials']
            );
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        $response = $this->httpRequest('POST', $uri, ['Authorization' => $header]);

        if ($response['code'] === 200) {
            $this->appTicket = $response['body'];
        }

        return [
            'code' => $response['code'],
            'result' => $response['body'],
            'headers' => $response['headers']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function requestUserTicket($userCredentials, $flow = 'auto')
    {
        $uri = $this->settings['uri'] . $this->settings['endpoints']['user'];
        $headers = [];

        /*
         * Application authentication
         */

        $this->settings['credentials'] = isset($this->settings['credentials'])
            ?  $this->settings['credentials']
            : null;

        if (($this->settings['credentials'] && $flow !== 'implicit') ||
            $flow === 'user_credentials'
        ) {
            $this->setAppTicket();

            // Set authorization header using application ticket
            try {
                $headers['Authorization'] = (new Client($this->hawkClient))->header(
                    $uri,
                    'POST',
                    $this->appTicket
                );
            } catch (\Exception $e) {
                throw new ClientException($e->getMessage(), $e->getCode(), $e);
            }
        }

        /*
         * Make request
         */

        $response = $this->httpRequest('POST', $uri, $headers, ['user' => $userCredentials]);

        if ($response['code'] === 200) {
            $this->userTicket = $response['body'];
        }

        return [
            'code' => $response['code'],
            'result' => $response['body'],
            'headers' => $response['headers']
        ];
    }

    /**
     * Check if the application ticket is set. If not set, request an
     * application ticket using the application credentials
     *
     * @return void
     * @throws ClientException
     */
    protected function setAppTicket()
    {
        if (!$this->appTicket) {
            $appTicketResponse = $this->requestAppTicket();

            if ($appTicketResponse['code'] !== 200) {
                throw new ClientException('Client registration failed with unexpected response');
            }

            $this->appTicket = $appTicketResponse['result'];
        }
    }

    /**
     * Make a request to the server using the given ticket
     *
     * @param  string  $method  HTTP method of the request
     * @param  string  $path  URL of the request relative to the host (e.g.
     *                        `/resource`)
     * @param  string|array|null  $payload  Request body
     * @param  array  $ticket
     * @return array  The requested resource (parsed to array if JSON), HTTP
     *                response code, and the ticket used to make the request
     */
    protected function makeRequest($method, $path, $payload, $ticket)
    {
        $body = ($payload !== null) ? $payload : null;
        $uri = $this->settings['uri'] . $path;
        $headers = [];

        if (gettype($payload) === 'array') {
            $headers['Content-Type'] = 'application/json';
            json_encode($payload);
        }

        try {
            $headerOutput = (new Client($this->hawkClient))->header($uri, $method, $ticket);
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        $headers['Authorization'] = $headerOutput['header'];
        // Make the request
        $response = $this->httpRequest($method, $uri, $headers, $body);

        try {
            $this->hawkClient->authenticate(
                $response['headers'],
                $ticket,
                $headerOutput['artifacts']
            );
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }

        return [
            'code' => $response['code'],
            'result' => $response['body']
        ];
    }

    /**
     * Make an HTTP request
     *
     * @param  string  $method  HTTP method of the request
     * @param  string  $uri  URI the request should be made to
     * @param  array  $headers  Request headers
     * @param  array  $payload  Request body
     * @return array  The response, which contains the status code, response
     *                body, and headers
     */
    protected function httpRequest($method, $uri, $headers = [], $payload = null)
    {
        if (gettype($payload) === 'array') {
            $headers['Content-Type'] = 'application/json';
            $payload = json_encode($payload);
        }

        $response = \Httpful\Request::init($method)
                                    ->uri($uri)
                                    ->addHeaders($headers)
                                    ->body($payload)
                                    ->autoParse()
                                    ->send();

        return [
            'code' => $response->code,
            'body' => $response->body,
            'headers' => $response->headers->toArray()
        ];
    }
}
