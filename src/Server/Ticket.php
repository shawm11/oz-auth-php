<?php

namespace Shawm11\Oz\Server;

use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Iron\IronInterface;

class Ticket implements TicketInterface
{
    /**
     * Default ticket configuration
     *
     * @var array
     */
    protected $defaults = [
        'ticketTTL' => 60 * 60 * 1000, // 1 hour
        'rsvpTTL' => 1 * 60 * 1000, // 1 minute
        'keyBytes' => 32, // Ticket secret size in bytes
        'hmacAlgorithm' => 'sha256'
    ];

    protected $grantTypes = ['rsvp', 'user_credentials', 'implicit'];

    protected $iron;

    protected $encryptionPassword;

    protected $options;

    public function __construct($encryptionPassword, $options = [], IronInterface $iron = null)
    {
        $this->encryptionPassword = $encryptionPassword;
        $this->options = $options;

        $this->iron = $iron
            ? $iron
            : (new \Shawm11\Iron\Iron(\Shawm11\Iron\IronOptions::$defaults));

        if (!empty($options['iron'])) {
            $this->iron->setOptions($options['iron']);
        }
    }

    public function issue($app, $grant = null)
    {
        if ($grant) {
            $grant['type'] = (isset($grant['type'])) ? $grant['type'] : 'rsvp';
        }

        if (!(isset($grant['type']) && $grant['type'] === 'implicit') &&
            !($app && !empty($app['id']))
        ) {
            throw new ServerException('Invalid application object');
        }

        if (($grant || $grant === []) &&
            (
                empty($grant['id']) ||
                empty($grant['user']) ||
                empty($grant['exp']) ||
                !in_array($grant['type'], $this->grantTypes)
            )
        ) {
            throw new ServerException('Invalid grant object');
        }

        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        $scope = ($grant && !empty($grant['scope']))
            ? $grant['scope']
            : (empty($app['scope']) ? [] : $app['scope']);
        $scopeClass = new Scope;

        $scopeClass->validate($scope);

        if ($grant &&
            !(isset($grant['type']) && $grant['type'] === 'implicit') &&
            !empty($grant['scope']) &&
            !empty($app['scope']) &&
            !$scopeClass->isSubset($app['scope'], $grant['scope'])
        ) {
            throw new ServerException('Grant scope is not a subset of the application scope');
        }

        /*
         * Construct ticket
         */

        $exp = (new HawkUtils)->now()
                + (empty($this->options['ttl']) ? $this->defaults['ticketTTL'] : $this->options['ttl']);

        if ($grant) {
            $exp = min($exp, $grant['exp']);
        }

        $appId = (isset($grant['type']) && $grant['type'] === 'implicit') ? null : $app['id'];

        $ticket = [
            'exp' => $exp,
            'app' => $appId,
            'scope' => $scope
        ];

        if ($grant) {
            $ticket['grant'] = $grant['id'];
            $ticket['user'] = $grant['user'];
        }

        // Defaults to true
        if ((isset($this->options['delegate']) && $this->options['delegate'] === false) ||
            (isset($grant['type']) && $grant['type'] === 'implicit') // Disable delegation for implicit grants
        ) {
            $ticket['delegate'] = false;
        }

        return $this->generate($ticket);
    }

    public function reissue($parentTicket, $grant = null)
    {
        if ($grant) {
            $grant['type'] = (isset($grant['type'])) ? $grant['type'] : 'rsvp';
        }

        if (!$parentTicket && $parentTicket !== []) {
            throw new ServerException('Invalid parent ticket object');
        }

        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        $scopeClass = new Scope;
        $parentScope = isset($parentTicket['scope']) ? $parentTicket['scope'] : [];
        $optionScope = isset($this->options['scope']) ? $this->options['scope'] : [];

        if ($parentScope) {
            $scopeClass->validate($parentScope);
        }

        if ($optionScope) {
            $scopeClass->validate($optionScope);

            if (!$scopeClass->isSubset($parentScope, $optionScope)) {
                throw new ForbiddenException('New scope is not a subset of the parent ticket scope');
            }
        }

        if (!empty($this->options['delegate']) &&
            isset($parentTicket['delegate']) && $parentTicket['delegate'] === false
        ) {
            throw new ForbiddenException('Cannot override ticket delegate restriction');
        }

        if (!empty($this->options['issueTo'])) {
            if (!empty($parentTicket['dlg'])) {
                throw new BadRequestException('Cannot re-delegate');
            }

            // Defaults to true
            if (isset($parentTicket['delegate']) && $parentTicket['delegate'] === false) {
                throw new ForbiddenException('Ticket does not allow delegation');
            }
        }

        if (($grant || $grant === []) &&
            (
                empty($grant['id']) ||
                empty($grant['user']) ||
                empty($grant['exp']) ||
                !in_array($grant['type'], $this->grantTypes)
            )
        ) {
            throw new ServerException('Invalid grant object');
        }

        if ($grant || !empty($parentTicket['grant'])) {
            if (!$grant ||
                empty($parentTicket['grant']) ||
                $parentTicket['grant'] !== $grant['id']
            ) {
                throw new ServerException('Parent ticket grant does not match options.grant');
            }
        }

        /*
         * Construct ticket
         */

        $exp = (new HawkUtils)->now()
                + (empty($this->options['ttl']) ? $this->defaults['ticketTTL'] : $this->options['ttl']);

        if ($grant) {
            $exp = min($exp, $grant['exp']);
        }

        $ticket = [
            'exp' => $exp,
            'app' => empty($this->options['issueTo']) ? $parentTicket['app'] : $this->options['issueTo'],
            'scope' => $optionScope ? $optionScope : $parentScope
        ];

        if (empty($this->options['ext']) && !empty($parentTicket['ext'])) {
            $this->options['ext'] = $parentTicket['ext'];
        }

        if ($grant) {
            $ticket['grant'] = $grant['id'];
            $ticket['user'] = $grant['user'];
        }

        if (!empty($this->options['issueTo'])) {
            $ticket['dlg'] = $parentTicket['app'];
        } elseif (isset($parentTicket['dlg']) && $parentTicket['dlg']) {
            $ticket['dlg'] = $parentTicket['dlg'];
        }

        // Defaults to true
        if ((isset($this->options['delegate']) && $this->options['delegate'] === false) ||
            (isset($parentTicket['delegate']) && $parentTicket['delegate'] === false) ||
            (isset($grant['type']) && $grant['type'] === 'implicit') // Disable delegation for implicit grants
        ) {
            $ticket['delegate'] = false;
        }

        return $this->generate($ticket);
    }

    public function rsvp($app, $grant)
    {
        if (!$app || empty($app['id'])) {
            throw new ServerException('Invalid application object');
        }

        if (!$grant || empty($grant['id'])) {
            throw new ServerException('Invalid grant object');
        }

        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        $this->options['ttl'] = !empty($this->options['ttl'])
            ? $this->options['ttl']
            : $this->defaults['rsvpTTL'];

        /*
         * Construct envelope
         */

        $envelope = [
            'app' => $app['id'],
            'exp' => (new HawkUtils)->now() + $this->options['ttl'],
            'grant' => $grant['id']
        ];

        /*
         * Stringify and encrypt
         */

        try {
            $result = $this->iron->seal($envelope, $this->encryptionPassword);
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    public function generate($ticket)
    {
        /*
         * Generate ticket secret
         */

        $numOfBytes = !empty($this->options['keyBytes'])
            ? $this->options['keyBytes']
            : $this->defaults['keyBytes'];

        try {
            $random = substr(
                (new HawkUtils)->base64urlEncode(openssl_random_pseudo_bytes($numOfBytes)),
                0,
                $numOfBytes
            );
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $ticket['key'] = $random;
        $ticket['algorithm'] =!empty($this->options['hmacAlgorithm'])
            ? $this->options['hmacAlgorithm']
            : $this->defaults['hmacAlgorithm'];

        /*
         * Process ext data
         */

        if (!empty($this->options['ext'])) {
            $ticket['ext'] = [];

            /*
             * Do an explicit copy to avoid unintentional leaking of private
             * data as public or changes to options object
             */

            if (isset($this->options['ext']['public'])) {
                $ticket['ext']['public'] = $this->options['ext']['public'];
            }

            if (isset($this->options['ext']['private'])) {
                $ticket['ext']['private'] = $this->options['ext']['private'];
            }
        }

        /*
         * Seal ticket
         */
        try {
            $sealed = $this->iron->seal($ticket, $this->encryptionPassword);
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $ticket['id'] = $sealed;

        /*
         * Hide private ext data
         */

        if (!empty($ticket['ext'])) {
            if (isset($ticket['ext']['public'])) {
                $ticket['ext'] = $ticket['ext']['public'];
            } else {
                unset($ticket['ext']);
            }
        }

        return $ticket;
    }

    public function parse($id)
    {
        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        try {
            $ticket = $this->iron->unseal($id, $this->encryptionPassword);
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $ticket['id'] = $id;

        return $ticket;
    }
}
