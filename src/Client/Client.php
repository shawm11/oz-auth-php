<?php

namespace Shawm11\Oz\Client;

use Shawm11\Hawk\Client\ClientInterface as HawkClientInterface;
use Shawm11\Hawk\Client\Client as HawkClient;

class Client implements ClientInterface
{
    protected $hawkClient;

    public function __construct(HawkClientInterface $hawkClient = null)
    {
        $this->hawkClient = $hawkClient ? $hawkClient : (new HawkClient);
    }

    public function header($uri, $method, $ticket, $options = [])
    {
        $settings = $options;
        $settings['credentials'] = $ticket;
        $settings['app'] = $ticket['app'];
        $settings['dlg'] = $ticket['dlg'];

        try {
            $hawkHeader = $this->hawkClient->header($uri, $method, $settings);
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage());
        }

        return $hawkHeader;
    }
}
