<?php

namespace Shawm11\Oz\Client;

use Shawm11\Hawk\Client\ClientInterface as HawkClientInterface;
use Shawm11\Hawk\Client\Client as HawkClient;

class Client implements ClientInterface
{
    /**
     * Hawk client dependency
     *
     * @var HawkClientInterface
     */
    protected $hawkClient;

    public function __construct(HawkClientInterface $hawkClient = null)
    {
        $this->hawkClient = $hawkClient ? $hawkClient : (new HawkClient);
    }

    /**
     * {@inheritdoc}
     */
    public function header($uri, $method, $ticket, $options = [])
    {
        $options['credentials'] = $ticket;
        $options['app'] = isset($ticket['app']) ? $ticket['app'] : null;
        $options['dlg'] = isset($ticket['dlg']) ? $ticket['dlg'] : null;

        try {
            $hawkHeader = $this->hawkClient->header($uri, $method, $options);
        } catch (\Exception $e) {
            throw new ClientException($e->getMessage());
        }

        return $hawkHeader;
    }
}
