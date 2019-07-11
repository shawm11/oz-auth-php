Server API Reference
====================

Table of Contents
-----------------

<!--lint disable list-item-spacing-->

- [Word Usage](#word-usage)
- [Namespace](#namespace)
- [`Endpoints` Class](#endpoints-class)
  - [`Endpoints` Constructor](#endpoints-constructor)
  - [`app($request, $options)`](#apprequest-options)
    - [`app` (`Endpoints` Class) Parameters](#app-endpoints-class-parameters)
  - [`reissue($request, $payload, $options)`](#reissuerequest-payload-options)
    - [`reissue` (`Endpoints` Class) Parameters](#reissue-endpoints-class-parameters)
  - [`rsvp($request, $payload, $options)`](#rsvprequest-payload-options)
    - [`rsvp` (`Endpoints` Class) Parameters](#rsvp-endpoints-class-parameters)
  - [`user($request, $payload, $options)`](#userrequest-payload-options)
    - [`user` (`Endpoints` Class) Parameters](#user-endpoints-class-parameters)
- [`Server` Class](#server-class)
  - [`Server` Constructor](#server-constructor)
  - [`authenticate($request, $encryptionPassword, $checkExpiration, $options)`](#authenticaterequest-encryptionpassword-checkexpiration-options)
    - [`authenticate` (`Server` Class) Parameters](#authenticate-server-class-parameters)
- [`Ticket` Class](#ticket-class)
  - [`Ticket` Constructor](#ticket-constructor)
  - [`issue($app, $grant)`](#issueapp-grant)
    - [`issue` Parameters](#issue-parameters)
  - [`reissue($parentTicket, $grant)`](#reissueparentticket-grant)
    - [`reissue` (`Ticket` Class) Parameters](#reissue-ticket-class-parameters)
  - [`rsvp($app, $grant)`](#rsvpapp-grant)
    - [`rsvp` (`Ticket` Class) Parameters](#rsvp-ticket-class-parameters)
  - [`generate($ticket)`](#generateticket)
    - [`generate` Parameters](#generate-parameters)
  - [`parse($id)`](#parseid)
    - [`generate` Parameters](#generate-parameters)
- [`Scope` Class](#scope-class)
  - [`validate($scope)`](#validatescope)
    - [`validate` Parameters](#validate-parameters)
  - [`isSubset($scope, $subset)`](#issubsetscope-subset)
    - [`isSubset` Parameters](#issubset-parameters)
  - [`isEqual($one, $two)`](#isequalone-two)
    - [`isEqual` Parameters](#isequal-parameters)
- [`ServerException` Class](#serverexception-class)
- [`BadRequestException` Class](#badrequestexception-class)
  - [`getCode()` (`BadRequestException` Class)](#getcode-badrequestexception-class)
  - [`getMessage()` (`BadRequestException` Class)](#getmessage-badrequestexception-class)
- [`UnauthorizedException` Class](#unauthorizedexception-class)
  - [`UnauthorizedException` Constructor](#unauthorizedexception-constructor)
  - [`getCode()` (`UnauthorizedException` Class)](#getcode-unauthorizedexception-class)
  - [`getMessage()` (`UnauthorizedException` Class)](#getmessage-unauthorizedexception-class)
  - [`getWwwAuthenticateHeaderAttributes()`](#getwwwauthenticateheaderattributes)
  - [`getWwwAuthenticateHeader()`](#getwwwauthenticateheader)
- [`ForbiddenException` Class](#forbiddenexception-class)
  - [`getCode()` (`ForbiddenException` Class)](#getcode-forbiddenexception-class)
  - [`getMessage()` (`ForbiddenException` Class)](#getmessage-forbiddenexception-class)

Word Usage
----------

In this document the words "client" and "application" are interchangeable.

Namespace
---------

All classes and sub-namespaces are within the `Shawm11\Oz\Server` namespace.

`Endpoints` Class
-----------------

Contains the endpoint methods that provide a HTTP request handler
implementations which are designed to be plugged into a framework such as
[Laravel](https://laravel.com/) or [CodeIgniter](https://www.codeigniter.com/).

### `Endpoints` Constructor

1. _Shawm11\\Hawk\\Server\\ServerInterface_ `$hawkServer` — (Optional) Hawk
   Server instance to be used.. By default, an instance of the `Shawm11\Hawk\Server`
   class is created and used.
1. _Shawm11\\Iron\\IronInterface_ `$iron` — (Optional) Iron instance to be used.
   By default, an instance of the `Shawm11\Iron\Iron` class with the default
   options is created and used.

### `app($request, $options)`

Authenticate an application request, and if valid, issues an application ticket.

Returns the application [ticket](shared-arrays.md#ticket) for the client as an
array.

#### `app` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:
    - _string_ `method` — (Required) HTTP method of the request
    - _string_ `url` — (Optional) URL (without the host and port) the request
      was sent to
    - _string_ `host` — (Required) Host of the server the request was sent to
      (e.g. example.com)
    - _integer_ `port` — (Required) Port number the request was sent to
    - _string_ `authorization` — (Optional) Value of the `Authorization` header
      in the request.
    - _string_ `contentType` — (Optional) Payload content type. It is usually
      the value of the `Content-Type` header in the request. Only used for
      payload validation.

1.  _array_ `$options` — (Required) Configuration options that include the
    following:
    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:
        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `secret` — Password string used for both encrypting the
          object and integrity (HMAC creation and verification)

        OR

        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `encryption` — Password string used for encrypting the object
        - _string_ `integrity` — Password string used for HMAC creation and
          verification

    -   _callable_ `loadAppFunc` — (Required) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the application
          that is used to look up the application's credentials.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _string_ `key` — (Required) Secret key for the application
          - _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
            Must be either `sha1` or `sha256`.

    -   _array_ `ticket` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
        used for parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:
        - _string_ `host` — (Optional) Host of the server (e.g. example.com).
          Overrides the `host` in the `$request` parameter.
        - _integer_ `port` — (Optional) Port number. Overrides the `port` in the
         `$request` parameter.
        - _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of
          time (in seconds) the client and server timestamps can differ (usually
          because of network latency)
        - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
          milliseconds) of the server's local time compared to the client's
          local time
        - _string_ `payload` — (Optional) UTF-8-encoded request body (or
          "payload"). Only used for payload validation.
        - _callable_ `nonceFunc` — (Optional) Function for checking the
          generated nonce (**n**umber used **once**) that is used to make the
          MAC unique even if given the same data. It must throw an error if the
          nonce check fails.

### `reissue($request, $payload, $options)`

Reissue an existing ticket (the ticket used to authenticate the request).

Returns the reissued [ticket](shared-arrays.md#ticket) as an array.

#### `reissue` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:
    - _string_ `method` — (Required) HTTP method of the request
    - _string_ `url` — (Optional) URL (without the host and port) the request
      was sent to
    - _string_ `host` — (Required) Host of the server the request was sent to
      (e.g. example.com)
    - _integer_ `port` — (Required) Port number the request was sent to
    - _string_ `authorization` — (Required) Value of the `Authorization`
      header in the request.
    - _string_ `contentType` — (Optional) Payload content type. It is usually
      the value of the `Content-Type` header in the request. Only used for
      payload validation.

1.  _array_ `$payload` — (Optional) Parsed request body that may contain their
    following:
    - _string_ `issueTo` — (Optional) Application ID that can be different than
      the one of the current application. Used to delegate access between
      applications. Defaults to the current application.
    - _array_ `scope` — (Optional) Scope strings which must be a subset of the
      given ticket's granted scope. Defaults to the original ticket scope.

1.  _array_ `$options` — (Required) Configuration options that include the
    following:
    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:
        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `secret` — Password string used for both encrypting the
          object and integrity (HMAC creation and verification)

        OR

        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `encryption` — Password string used for encrypting the object
        - _string_ `integrity` — Password string used for HMAC creation and
          verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _callable_ `loadAppFunc` — (Optional if using the [Implicit
        Workflow](../implicit-workflow.md)) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the application
          that is used to look up the application's credentials.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _string_ `key` — (Required) Secret key for the application
          - _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
            Must be either `sha1` or `sha256`.

    -   _callable_ `loadGrantFunc` — (Required) Function for looking up the
        grant. This is often done by looking up the grant in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the grant.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _array_ `grant` — (Required) [Grant array](shared-arrays.md#grant)
          - _array_ `ext` — (Optional) Used to include custom server data in
            the ticket and response. Contains the following:
            - _array_ `public` — (Optional) Associative array that will be
              included in the response under `ticket.ext` and in the encoded
              ticket as `ticket.ext.public`.
            - _array_ `private` — (Optional) Associative array that will only be
              included in the encoded ticket as `ticket.ext.private`

    -   _array_ `ticket` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
        used for parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:
        - _string_ `host` — (Optional) Host of the server (e.g. example.com).
          Overrides the `host` in the `$request` parameter.
        - _integer_ `port` — (Optional) Port number. Overrides the `port` in the
          `$request` parameter.
        - _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of
          time (in seconds) the client and server timestamps can differ (usually
          because of network latency)
        - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
          milliseconds) of the server's local time compared to the client's
          local time
        - _string_ `payload` — (Optional) UTF-8-encoded request body (or
          "payload"). Only used for payload validation.
        - _callable_ `nonceFunc` — (Optional) Function for checking the
          generated nonce (**n**umber used **once**) that is used to make the
          MAC unique even if given the same data. It must throw an error if the
          nonce check fails.

### `rsvp($request, $payload, $options)`

Authenticate an application request and if valid and exchange the provided RSVP
with a user ticket.

Returns the user [ticket](shared-arrays.md#ticket) as an array.

#### `rsvp` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:
    - _string_ `method` — (Required) HTTP method of the request
    - _string_ `url` — (Optional) URL (without the host and port) the request
      was sent to
    - _string_ `host` — (Required) Host of the server the request was sent to
      (e.g. example.com)
    - _integer_ `port` — (Required) Port number the request was sent to
    - _string_ `authorization` — (Optional) Value of the `Authorization` header
      in the request.
    - _string_ `contentType` — (Optional) Payload content type. It is usually
      the value of the `Content-Type` header in the request. Only used for
      payload validation.

1.  _array_ `$payload` — (Required) Parsed request body that may contain their
    following:
    - _string_ `rsvp` — (Required) RSVP provided to the user to bring back to
      the application after granting authorization

1.  _array_ `$options` — (Required) Configuration options that include the
    following:
    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:
        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `secret` — Password string used for both encrypting the
          object and integrity (HMAC creation and verification)

        OR

        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `encryption` — Password string used for encrypting the object
        - _string_ `integrity` — Password string used for HMAC creation and
          verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _callable_ `loadAppFunc` — (Required) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the application
          that is used to look up the application's credentials.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _string_ `key` — (Required) Secret key for the application
          - _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
            Must be either `sha1` or `sha256`.

    -   _callable_ `loadGrantFunc` — (Required) Function for looking up the
        grant. This is often done by looking up the grant in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the grant.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _array_ `grant` — (Required) [Grant array](shared-arrays.md#grant)
          - _array_ `ext` — (Optional) Used to include custom server data in the
            ticket and response. Contains the following:
            - _array_ `public` — (Optional) Associative array that will be
              included in the response under `ticket.ext` and in the encoded
              ticket as `ticket.ext.public`.
            - _array_ `private` — (Optional) Associative array that will only be
              included in the encoded ticket as `ticket.ext.private`

    -   _array_ `ticket` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
        used for parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:
        - _string_ `host` — (Optional) Host of the server (e.g. example.com).
          Overrides the `host` in the `$request` parameter.
        - _integer_ `port` — (Optional) Port number. Overrides the `port` in the
          `$request` parameter.
        - _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of
          time (in seconds) the client and server timestamps can differ (usually
          because of network latency)
        - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
          milliseconds) of the server's local time compared to the client's
          local time
        - _string_ `payload` — (Optional) UTF-8-encoded request body (or
          "payload"). Only used for payload validation.
        - _callable_ `nonceFunc` — (Optional) Function for checking the
          generated nonce (**n**umber used **once**) that is used to make the
          MAC unique even if given the same data. It must throw an error if the
          nonce check fails.

### `user($request, $payload, $options)`

Issue a user ticket to the application using the set of user credentials given
in the payload. Only used for the [User Credentials](../user-credentials-workflow.md)
and [Implicit](../implicit-workflow.md) Oz workflows.

Returns the user [ticket](shared-arrays.md#ticket) as an array.

#### `user` Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:
    - _string_ `method` — (Required) HTTP method of the request
    - _string_ `url` — (Optional) URL (without the host and port) the request
      was sent to
    - _string_ `host` — (Required) Host of the server the request was sent to
      (e.g. example.com)
    - _integer_ `port` — (Required) Port number the request was sent to
    - _string_ `authorization` — (Optional) Value of the `Authorization` header
      in the request.
    - _string_ `contentType` — (Optional) Payload content type. It is usually
      the value of the `Content-Type` header in the request. Only used for
      payload validation.

1.  _array_ `$payload` — (Required) Parsed request body that may contain their
    following:
    - _any_ `user` — (Required) User credentials. Usually an array (parsed JSON)
      or a string. Example: `['username' => 'john', 'password' => 'p4$$w0rd']`

1.  _array_ `$options` — (Required) Configuration options that include the
    following:
    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:
        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `secret` — Password string used for both encrypting the
          object and integrity (HMAC creation and verification)

        OR

        - _string_ or _integer_ `id` — Unique identifier (consisting of only
          underscores (`_`), letters, and numbers) for the password for when
          there are multiple possible passwords. Used for password rotation.
        - _string_ `encryption` — Password string used for encrypting the object
        - _string_ `integrity` — Password string used for HMAC creation and
          verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _array_ `grant` — (Required) Options used to create the grant. Contains
        the following:
        - `exp` — (Required) Grant expiration time in milliseconds since
            January 1, 1970.
        - `scope` — (Optional) Scope granted by the user to the application

    -   _array_ `allowedGrantTypes`— (Optional) List of [grant](shared-arrays.md#grant)
        types (or Oz workflows) that are allowed. If `user_credentials` is not
        in this array, then the [User Credentials Workflow](../user-credentials-workflow.md)
        is disabled. If `implicit` is not in this array, then the [Implicit
        Workflow](../implicit-workflow.md) is disabled. Default: `['rsvp', 'user_credentials', 'implicit']`

    -   _callable_ `loadAppFunc` — (Required if using the [User Credentials
        Workflow](../user-credentials-workflow.md)) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:
        - Parameter: _string_ `$id` — (Required) Unique ID for the application
          that is used to look up the application's credentials.
        - Returns: _array_ — (Required) Set of credentials that contains the
          following:
          - _string_ `key` — (Required) Secret key for the application
          - _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
            Must be either `sha1` or `sha256`.

    -   _callable_ `verifyUserFunc`— (Required) Function for verifying the user
        using the user credentials. The function must have the following:
        - Parameter: _string_ or _array_ `$userCredentials` — (Required) User's
          credentials
        - Returns: _string_ — (Required) User ID

    -   _callable_ `storeGrantFunc` — (Required) Function for storing the grant
        that is created. The function must have the following:
        - Parameter: _array_ `$grant` — (Required) [Grant](shared-arrays.md#grant)
          to store
        - Returns: _string_ — (Required) Grant's unique ID created by the server

    -   _array_ `ticket` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
        used for parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:
        - _string_ `host` — (Optional) Host of the server (e.g. example.com).
          Overrides the `host` in the `$request` parameter.
        - _integer_ `port` — (Optional) Port number. Overrides the `port` in the
          `$request` parameter.
        - _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of
          time (in seconds) the client and server timestamps can differ (usually
          because of network latency)
        - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
          milliseconds) of the server's local time compared to the client's
          local time
        - _string_ `payload` — (Optional) UTF-8-encoded request body (or
          "payload"). Only used for payload validation.
        - _callable_ `nonceFunc` — (Optional) Function for checking the
          generated nonce (**n**umber used **once**) that is used to make the
          MAC unique even if given the same data. It must throw an error if the
          nonce check fails.

`Server` Class
--------------

Server implementation utilities.

### `Server` Constructor

1. _Shawm11\\Hawk\\Server\\ServerInterface_ `$hawkServer` — (Optional) Hawk
   Server instance to be used

<!--lint disable maximum-heading-length-->

### `authenticate($request, $encryptionPassword, $checkExpiration, $options)`

<!--lint enable maximum-heading-length-->

Validate an incoming request using Hawk and performs additional Oz-specific
validations. If the request is valid, an application ticket is issued.

Returns the following if authentication is successful.

- _array_ `ticket` — Decoded ticket that was given in the header of the request
- _array_ `artifacts` — Hawk components of the request including the
  `Authorization` HTTP header. It includes the following:
  - _string_ `method` — Request method
  - _string_ `host` — Request host
  - _string_ `port` — Request port
  - _string_ `resource` — URL of the request relative to the host
  - _string_ `ts` — Timestamp (as milliseconds since January 1, 1970)
  - _string_ `nonce` — Nonce used to create the `mac`
  - _string_ `hash` — Payload hash. Only used for payload validation.
  - _string_ `ext` — Extra application-specific data
  - _string_ `app` — Application ID
  - _string_ `dlg` — 'delegated-by' attribute. Only used with delegation.
  - _string_ `mac` — HMAC digest of the other items in this array
  - _string_ `id` — Client's unique Hawk ID

#### `authenticate` (`Server` Class) Parameters

1. _array_ `$request` — (Required) Request data. Contains the following:
   - _string_ `method` — (Required) HTTP method of the request
   - _string_ `url` — (Optional) URL (without the host and port) the request was
     sent to
   - _string_ `host` — (Required) Host of the server the request was sent to
     (e.g. example.com)
   - _integer_ `port` — (Required) Port number the request was sent to
   - _string_ `authorization` — (Optional) Value of the `Authorization` header
     in the request. See [`header()` for the `Client` class](client-api.md#headeruri-method-ticket-options).
   - _string_ `contentType` — (Optional) Payload content type. It is usually the
     value of the `Content-Type` header in the request. Only used for payload
     validation.
1. _string_ or _array_ `$encryptionPassword` — (Required) Password (as a string)
   used for ticket encryption or a list of possible passwords (as an array) that
   could have been used for ticket encryption (for password rotation)
1. _array_ `$options` — (Required) Configuration options that include the
   following:
   - _array_ `ticket` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
     used for parsing and issuance
   - _array_ `hawk` — (Optional) Hawk options, which include the following:
     - _string_ `host` — (Optional) Host of the server (e.g. example.com).
       Overrides the `host` in the `$request` parameter.
     - _integer_ `port` — (Optional) Port number. Overrides the `port` in the
       `$request` parameter.
     - _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of time
       (in seconds) the client and server timestamps can differ (usually because
       of network latency)
     - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
       milliseconds) of the server's local time compared to the client's local
       time
     - _string_ `payload` — (Optional) UTF-8-encoded request body (or
       "payload"). Only used for payload validation.
     - _callable_ `nonceFunc` — (Optional) Function for checking the generated
       nonce (**n**umber used **once**) that is used to make the MAC unique even
       if given the same data. It must throw an error if the nonce check fails.

`Ticket` Class
--------------

Ticket issuance, parsing, encoding, and re-issuance utilities.

### `Ticket` Constructor

1.  _string_ or _array_ `$encryptionPassword` — (Required) Can be either a
    password string or associative array that contains:
    - _string_ or _integer_ `id` — Unique identifier (consisting of only
      underscores (`_`), letters, and numbers) for the password for when there
      are multiple possible passwords. Used for password rotation.
    - _string_ `secret` — Password string used for both encrypting the
      object and integrity (HMAC creation and verification)

    OR

    - _string_ or _integer_ `id` — Unique identifier (consisting of only
      underscores (`_`), letters, and numbers) for the password for when there
      are multiple possible passwords. Used for password rotation.
    - _string_ `encryption` — Password string used for encrypting the object
    - _string_ `integrity` — Password string used for HMAC creation and
      verification

1.  _array_ `$options` — (Optional) [Ticket options](shared-arrays.md#ticket-options)
    used for parsing and issuance

1.  _Shawm11\\Iron\\IronInterface_ `$iron` — (Optional) Iron instance to be used

### `issue($app, $grant)`

Issue a new application or user ticket.

Returns a new application or user [ticket](shared-arrays.md#ticket) as an array.

#### `issue` Parameters

1. _array_ `$app` — (Required if not using the [Implicit Workflow](../implicit-workflow.md))
   [App credentials](shared-arrays.md#app) of the application the ticket will be
   issued to
1. _array_ `$grant` — (Optional) [Grant](shared-arrays.md#grant) for the
   application

### `reissue($parentTicket, $grant)`

Reissue an application or user ticket.

Returns the reissued [ticket](shared-arrays.md#ticket) as an array.

#### `reissue` (`Ticket` Class) Parameters

1. _array_ `$parentTicket` — (Required) [Ticket](shared-arrays.md#ticket) to be
   reissued
1. _array_ `$grant` — (Optional) [Grant](shared-arrays.md#grant) for the
   application the ticket is being (re)issued to

### `rsvp($app, $grant)`

Generate an RSVP string representing a user grant.

Returns a user [ticket](shared-arrays.md#ticket) as an array for the application
to use

#### `rsvp` (`Ticket` Class) Parameters

1. _array_ `$app` — (Required) [App credentials](shared-arrays.md#app) of the
   application the user ticket will be issued to
1. _array_ `$grant` — (Required) [Grant](shared-arrays.md#grant) for the
   application. The grant is not allowed to be `null`.

### `generate($ticket)`

Add the cryptographic properties to a ticket and prepare the ticket response.

Returns the completed [ticket](shared-arrays.md#ticket) as an array.

#### `generate` Parameters

1. _array_ `$ticket` — (Required) Incomplete [ticket](shared-arrays.md#ticket)
   that only contains the following:
   - `exp`
   - `app`
   - `user`
   - `scope`
   - `grant`
   - `dlg`

### `parse($id)`

Decode a ticket ID (an iron-sealed string) into a ticket.

Returns the [ticket](shared-arrays.md#ticket) (as an array) that was encoded in
the given string.

#### `parse` Parameters

1. _string_ `$id` — (Required) Ticket ID which is the encoded ticket

`Scope` Class
-------------

Scope manipulation utilities.

### `validate($scope)`

Validate a scope for proper structure (an array of unique strings).

#### `validate` Parameters

1. _array_ `$scope` — (Required) Scope being validated

### `isSubset($scope, $subset)`

Check if one scope is a subset of another.

Returns a boolean that indicates if `$subset` is fully contained with `$scope`.

#### `isSubset` Parameters

1. _array_ `$scope` — (Required) Superset scope
1. _array_ `$subset` — (Required) Subset scope

### `isEqual($one, $two)`

Check if two scope arrays are the same.

Returns a boolean that indicates if `$one` is equal to `$two`.

#### `isEqual` Parameters

1. _array_ `$one` — (Required) First of the two scopes being compared
1. _array_ `$two` — (Required) Second of the two scopes being compared

`ServerException` Class
-----------------------

The exception that is thrown when there is a _server_ Oz error.

`BadRequestException` Class
---------------------------

A type of `ServerException` exception that represents an HTTP `400 Bad Request`
server response.

### `getCode()` (`BadRequestException` Class)

Inherited method from PHP's `Exception` class. Gives HTTP status code, which is
always `400`, as an integer.

### `getMessage()` (`BadRequestException` Class)

Inherited method from PHP's `Exception` class. Gives the error message text.

`UnauthorizedException` Class
-----------------------------

A type of `ServerException` exception that represents an HTTP `401 Unauthorized`
server response.

### `UnauthorizedException` Constructor

1. _string_ `$message` — (Optional) Exception message to throw. It is also
   included in the `WWW-Authenticate` header.
1. _array_ `$wwwAuthenticateHeaderAttributes` — (Optional) Associative array of
   keys and values to include in the `WWW-Authenticate`.
1. _integer_ `$code` — (Optional) HTTP status code that the response should have
1. _Throwable_ `$previous` — (Optional) Previous exception used for exception
   chaining

### `getCode()` (`UnauthorizedException` Class)

Inherited method from PHP's `Exception` class. Gives HTTP status code, which is
always `401`, as an integer.

### `getMessage()` (`UnauthorizedException` Class)

Inherited method from PHP's `Exception` class. Gives the error message text.

### `getWwwAuthenticateHeaderAttributes()`

Get the associative array of keys and values included in the HTTP
`WWW-Authenticate` header should be set to in the server's response.

### `getWwwAuthenticateHeader()`

Get the value the HTTP `WWW-Authenticate` header should be set to in the
server's response.

`Forbidden` Class
-----------------

A type of `ServerException` exception that represents an HTTP `403 Forbidden`
server response.

### `getCode()` (`Forbidden` Class)

Inherited method from PHP's `Exception` class. Gives HTTP status code, which is
always `403`, as an integer.

### `getMessage()` (`Forbidden` Class)

Inherited method from PHP's `Exception` class. Gives the error message text.

<!--lint enable list-item-spacing-->
