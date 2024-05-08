<!-- omit in toc -->
# Shared Arrays

The shared arrays are collections of data repeatedly used throughout various
parts of the API. The arrays are not part of any particular class.

<!-- omit in toc -->
## Table of Contents

- [App](#app)
- [Grant](#grant)
- [Ticket](#ticket)
- [Ticket Options](#ticket-options)

## App

_array_ — Set of credentials that contains the following:

- _string_ `id` — (Required) Unique ID for the application
- _string_ `key` — (Required) Secret key for the client
- _string_ `algorithm` — (Required) Algorithm to be used for HMAC. Must be
  either `sha1` or `sha256`.
- _array_ `scope` — (Optional) Scope of the ticket to be issued
- _boolean_ `delegate` — (Optional) If the application is allowed to delegate a
  ticket to another application. Defaults to `false`.

## Grant

_array_ — If set, the issued ticket is going to be a user ticket, and this grant
is going to be issued with it. If set to `null`, the issued ticket will be an
application ticket.

- _string_ `id` — (Required) Unique ID for the grant
- _string_ `app` — (Required) Application ID
- _string_ `user` — (Required) User ID
- _float_ `exp` — (Required) Grant expiration time in milliseconds since
  January 1, 1970
- _array_ `scope` — (Optional) Scope granted by the user to the application
- _string_ `type` — (Optional) Type of grant. In other words, how the grant was
  obtained. It can be one of the following values:
  - `rsvp` — (Default) Grant was obtained using the [RSVP Workflow](../rsvp-workflow-without-delegation.md)
  - `user_credentials` — Grant was obtained using the [User Credentials](../user-credentials-workflow.md)
  - `implicit` — Grant was obtained using the [Implicit Workflow](../implicit-workflow.md)

## Ticket

_array_ — Ticket and its public properties. A ticket is actually a set of Hawk
credential and artifacts. It contains the following:

- _string_ `id` — Ticket ID used for making authenticated Hawk requests
- _string_ `key` — Secret key (only known by the application and the server)
  used to authenticate
- _string_ `algorithm` — HMAC algorithm used to authenticate.
  Default is `sha256`.
- _float_ `exp` — Ticket expiration time in milliseconds since January 1, 1970
- _string_ `app` — Application id the ticket was issued to
- _string_ `user` — User ID if the ticket represents access to user resources.
  If no user ID is included, the ticket allows the application access to the
  application own resources only.
- _array_ `scope` — Ticket scope. Defaults to `[]` if no scope is specified.
- _array_ `grant` — If `user` is set, includes the grant ID referencing the
  authorization granted by the user to the application. Can be a unique ID or
  string encoding the grant information as long as the server is able to parse
  the information later.
- _boolean_ `delegate` — If `false`, the ticket cannot be delegated regardless
  of the application permissions. Defaults to `true` which means use the
  application permissions to delegate.
- _string_ `dlg` — If the ticket is the result of access delegation, the
  application ID of the delegating application
- _array_ `ext` — Custom server public data attached to the ticket

## Ticket Options

_array_ — Supported ticket parsing and issuance options passed to the [Ticket](server-api.md#ticket-class)
methods. Each [endpoint](server-api.md#endpoints-class) utilizes a different
subset of these options but it is safe to pass one common object to all (it will
ignore unused options). The ticket options contain the following:

- _float_ `ttl` — (Optional) Sets the ticket lifetime in milliseconds when
  generating a ticket. Defaults to `3600000` (1 hour) for tickets and `60000`
  (1 minutes) for RSVPs.
- _boolean_ `delegate` — (Optional) If `false`, the ticket cannot be delegated
  regardless of the application permissions. Defaults to `true` which means use
  the application permissions to delegate.
- _array_ `iron` — (Optional) Overrides the default Iron configuration
- _integer_ `keyBytes` — (Optional) Hawk key length in bytes. Defaults to `32`.
- _string_ `hmacAlgorithm` — (Optional) Hawk HMAC algorithm.
  Defaults to `sha256`.
- _array_ `ext` — (Optional) Used to include custom server data in the ticket
  and response. Contains the following:
  - _array_ `public` — (Optional) Associative array that will be included in the
    response under `ticket.ext` and in the encoded ticket as
    `ticket.ext.public`.
  - _array_ `private` — (Optional) Associative array that will only be included
    in the encoded ticket as `ticket.ext.private`.
