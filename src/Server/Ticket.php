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

        if (isset($options['iron']) && $options['iron']) {
            $this->iron->setOptions($options['iron']);
        }
    }

    public function issue($app, $grant)
    {
        if (!$app || !(isset($app['id']) && $app['id'])) {
            throw new ServerException('Invalid application object');
        }

        if (($grant || $grant === []) &&
            (
                !(isset($grant['id']) && $grant['id']) ||
                !(isset($grant['user']) && $grant['user']) ||
                !(isset($grant['exp']) && $grant['exp'])
            )
        ) {
            throw new ServerException('Invalid grant object');
        }

        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        $scope = ($grant && isset($grant['scope']) && $grant['scope'])
            ? $grant['scope']
            : ((isset($app['scope']) && $app['scope']) ? $app['scope'] : []);
        $scopeClass = new Scope;

        $scopeClass->validate($scope);

        if ($grant &&
            (isset($grant['scope']) && $grant['scope']) &&
            (isset($app['scope']) && $app['scope']) &&
            !$scopeClass->isSubset($app['scope'], $grant['scope'])
        ) {
            throw new ServerException('Grant scope is not a subset of the application scope');
        }

        /*
         * Construct ticket
         */

        $exp = (new HawkUtils)->now()
                + ((isset($this->options['ttl']) && $this->options['ttl'])
                    ? $this->options['ttl']
                    : $this->defaults['ticketTTL']);

        if ($grant) {
            $exp = min($exp, $grant['exp']);
        }

        $ticket = [
            'exp' => $exp,
            'app' => $app['id'],
            'scope' => $scope
        ];

        if ($grant) {
            $ticket['grant'] = $grant['id'];
            $ticket['user'] = $grant['user'];
        }

        // Defaults to true
        if (isset($this->options['delegate']) && $this->options['delegate'] === false) {
            $ticket['delegate'] = false;
        }

        return $this->generate($ticket);
    }

    public function reissue($parentTicket, $grant)
    {
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

        if (isset($this->options['delegate']) && $this->options['delegate'] &&
            isset($parentTicket['delegate']) && $parentTicket['delegate'] === false
        ) {
            throw new ForbiddenException('Cannot override ticket delegate restriction');
        }

        if (isset($this->options['issueTo']) && $this->options['issueTo']) {
            if (isset($parentTicket['dlg']) && $parentTicket['dlg']) {
                throw new BadRequestException('Cannot re-delegate');
            }

            // Defaults to true
            if (isset($parentTicket['delegate']) && $parentTicket['delegate'] === false) {
                throw new ForbiddenException('Ticket does not allow delegation');
            }
        }

        if (($grant || $grant === []) &&
            (
                !(isset($grant['id']) && $grant['id']) ||
                !(isset($grant['user']) && $grant['user']) ||
                !(isset($grant['exp']) && $grant['exp'])
            )
        ) {
            throw new ServerException('Invalid grant object');
        }

        if ($grant || (isset($parentTicket['grant']) && $parentTicket['grant'])) {
            if (!$grant ||
                !(isset($parentTicket['grant']) && $parentTicket['grant']) ||
                $parentTicket['grant'] !== $grant['id']
            ) {
                throw new ServerException('Parent ticket grant does not match options.grant');
            }
        }

        /*
         * Construct ticket
         */

        $exp = (new HawkUtils)->now()
                + ((isset($this->options['ttl']) && $this->options['ttl'])
                    ? $this->options['ttl']
                    : $this->defaults['ticketTTL']);

        if ($grant) {
            $exp = min($exp, $grant['exp']);
        }

        $ticket = [
            'exp' => $exp,
            'app' => (isset($this->options['issueTo']) && $this->options['issueTo'])
                ? $this->options['issueTo']
                : $parentTicket['app'],
            'scope' => $optionScope ? $optionScope : $parentScope
        ];

        if (!(isset($this->options['ext']) && $this->options['ext']) &&
            (isset($parentTicket['ext']) && $parentTicket['ext'])
        ) {
            $this->options['ext'] = $parentTicket['ext'];
        }

        if ($grant) {
            $ticket['grant'] = $grant['id'];
            $ticket['user'] = $grant['user'];
        }

        if (isset($this->options['issueTo']) && $this->options['issueTo']) {
            $ticket['dlg'] = $parentTicket['app'];
        } elseif (isset($parentTicket['dlg']) && $parentTicket['dlg']) {
            $ticket['dlg'] = $parentTicket['dlg'];
        }

        // Defaults to true
        if ((isset($this->options['delegate']) && $this->options['delegate'] === false) ||
            (isset($parentTicket['delegate']) && $parentTicket['delegate'] === false)
        ) {
            $ticket['delegate'] = false;
        }

        return $this->generate($ticket);
    }

    public function rsvp($app, $grant)
    {
        if (!$app || !(isset($app['id']) && $app['id'])) {
            throw new ServerException('Invalid application object');
        }

        if (!$grant || !(isset($grant['id']) && $grant['id'])) {
            throw new ServerException('Invalid grant object');
        }

        if (!$this->encryptionPassword) {
            throw new ServerException('Invalid encryption password');
        }

        $this->options['ttl'] = (isset($this->options['ttl']) && $this->options['ttl'])
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
            throw new ServerException($e->getMessage());
        }

        return $result;
    }

    public function generate($ticket)
    {
        /*
         * Generate ticket secret
         */

        $numOfBytes = (isset($this->options['keyBytes']) && $this->options['keyBytes'])
            ? $this->options['keyBytes']
            : $this->defaults['keyBytes'];

        try {
            $random = substr(
                (new HawkUtils)->base64urlEncode(openssl_random_pseudo_bytes($numOfBytes)),
                0,
                $numOfBytes
            );
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage());
        }

        $ticket['key'] = $random;
        $ticket['algorithm'] = (isset($this->options['hmacAlgorithm']) && $this->options['hmacAlgorithm'])
            ? $this->options['hmacAlgorithm']
            : $this->defaults['hmacAlgorithm'];

        /*
         * Process ext data
         */

        if (isset($this->options['ext']) && $this->options['ext']) {
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
            throw new ServerException($e->getMessage());
        }

        $ticket['id'] = $sealed;

        /*
         * Hide private ext data
         */

        if (isset($ticket['ext']) && $ticket['ext']) {
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
            throw new ServerException($e->getMessage());
        }

        $ticket['id'] = $id;

        return $ticket;
    }
}
