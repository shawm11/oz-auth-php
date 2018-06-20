<?php

namespace Shawm11\Oz\Server;

use Shawm11\Iron\IronInterface;

interface TicketInterface
{
    /**
     * @param  string  $encryptionPassword  A password used to generate the
     *                                      ticket encryption key. Must be kept
     *                                      confidential by the server.
     * @param array  $options  Ticket parsing and issuance options. Includes
     *                         `ttl`, `delegate`, `iron`, `keyBytes`,
     *                         `hmacAlgorithm`, and `ext`
     * @param IronInterface  $iron  Optional Iron dependency
     */
    public function __construct($encryptionPassword, $options = [], IronInterface $iron = null);

    /**
     * Issue a new application or user ticket
     *
     * @param  array  $app  Describes the application (client). Includes `id`,
     *                      `scope`, `delegate`, `key`, and `algorithm`
     * @param  array  $grant  Describes the user grant. Includes `id`, `app`,
     *                        `user`, `exp`, and `scope`
     * @throws ServerException
     * @return array  An application or user ticket
     */
    public function issue($app, $grant);

    /**
     * Reissue an application or user ticket
     *
     * @param  array  $parentTicket  Ticket to be reissued
     * @param  array  $grant  Describes the user grant. Includes `id`, `app`,
     *                        `user`, `exp`, and `scope`
     * @throws ServerException
     * @return array  The reissued ticket
     */
    public function reissue($parentTicket, $grant);

    /**
     * Generate an RSVP string representing a user grant
     *
     * @param  array  $app  Describes the application (client). Includes `id`,
     *                      `scope`, `delegate`, `key`, and `algorithm`
     * @param  array  $grant  Describes the user grant. Includes `id`, `app`,
     *                        `user`, `exp`, and `scope`
     * @throws ServerException
     * @return array  A user ticket for the client (application) to use
     */
    public function rsvp($app, $grant);

    /**
     * Add the cryptographic properties to a ticket and prepare the response
     *
     * @param  array  $ticket  An incomplete ticket that includes `exp`, `app`,
     *                         `user`, `scope`, `grant`, and `dlg`
     * @throws ServerException
     * @return array  The completed ticket
     */
    public function generate($ticket);

    /**
     * Decodes a ticket identifier (an iron-sealed string) into a ticket
     *
     * @param  string  $id  Ticket ID (iron-sealed string) which contains the
     * `                    encoded ticket information
     * @throws ServerException
     * @return array  Ticket information that was encoded in $id
     */
    public function parse($id);
}
