API Reference
=============

Table of Contents
-----------------

-   [Word Usage](#word-usage)

-   [Namespace](#namespace)

-   [`Server\Endpoints` Class](#serverendpoints-class)
    -   [`Server\Endpoints` Constructor](#serverendpoints-constructor)

    -   [`app($request, $options)`](#apprequest-options)
        - [`app` (`Endpoints` Class) Parameters](#app-endpoints-class-parameters)

    -   [`reissue($request, $payload, $options)`](#reissuerequest-payload-options)
        - [`reissue` (`Endpoints` Class) Parameters](#reissue-endpoints-class-parameters)

    -   [`rsvp($request, $payload, $options)`](#rsvprequest-payload-options)
        - [`rsvp` (`Endpoints` Class) Parameters](#rsvp-endpoints-class-parameters)

    -   [`user($request, $payload, $options)`](#userrequest-payload-options)
        - [`user` (`Endpoints` Class) Parameters](#user-endpoints-class-parameters)

-   [`Server\Server` Class](#serverserver-class)
    -   [`Server\Server` Constructor](#serverserver-constructor)

    -   [`authenticate($request, $encryptionPassword, $checkExpiration, $options)`](#authenticaterequest-encryptionpassword-checkexpiration-options)
        - [`authenticate` (`Server` Class) Parameters](#authenticate-server-class-parameters)

-   [`Server\Ticket` Class](#serverticket-class)
    -   [`Server\Ticket` Constructor](#serverticket-constructor)

    -   [`issue($app, $grant)`](#issueapp-grant)
        - [`issue` Parameters](#issue-parameters)

    -   [`reissue($parentTicket, $grant)`](#reissueparentticket-grant)
        - [`reissue` (`Ticket` Class) Parameters](#reissue-ticket-class-parameters)

    -   [`rsvp($app, $grant)`](#rsvpapp-grant)
        - [`rsvp` (`Ticket` Class) Parameters](#rsvp-ticket-class-parameters)

    -   [`generate($ticket)`](#generateticket)
        - [`generate` Parameters](#generate-parameters)

    -   [`parse($id)`](#parseid)
        - [`generate` Parameters](#generate-parameters)

-   [`Server\Scope` Class](#serverscope-class)
    -   [`validate($scope)`](#validatescope)
        - [`validate` Parameters](#validate-parameters)

    -   [`isSubset($scope, $subset)`](#issubsetscope-subset)
        - [`isSubset` Parameters](#issubset-parameters)

    -   [`isEqual($one, $two)`](#isequalone-two)
        - [`isEqual` Parameters](#isequal-parameters)

-   [`Server\ServerException` Class](#serverserverexception-class)

-   [`Server\BadRequestException` Class](#serverbadrequestexception-class)
    - [`getCode()` (`BadRequestException` Class)](#getcode-badrequestexception-class)
    - [`getMessage()` (`BadRequestException` Class)](#getmessage-badrequestexception-class)

-   [`Server\UnauthorizedException` Class](#serverunauthorizedexception-class)
    - [`Server\UnauthorizedException` Constructor](#serverunauthorizedexception-constructor)
    - [`getCode()` (`UnauthorizedException` Class)](#getcode-unauthorizedexception-class)
    - [`getMessage()` (`UnauthorizedException` Class)](#getmessage-unauthorizedexception-class)
    - [`getWwwAuthenticateHeaderAttributes()`](#getwwwauthenticateheaderattributes)
    - [`getWwwAuthenticateHeader()`](#getwwwauthenticateheader)

-   [`Server\ForbiddenException` Class](#serverforbiddenexception-class)
    - [`getCode()` (`ForbiddenException` Class)](#getcode-forbiddenexception-class)
    - [`getMessage()` (`ForbiddenException` Class)](#getmessage-forbiddenexception-class)

-   [`Client\Connection` Class](#clientconnection-class)
    -   [`Client\Connection` Constructor](#clientconnection-constructor)

    -   [`request($path, $ticket, $options)`](#requestpath-ticket-options)
        - [`request` Parameters](#request-parameters)

    -   [`app($path, $options)`](#apppath-options)
        - [`app` (`Connection` Class) Parameters](#app-connection-class-parameters)

    -   [`reissue($ticket)`](#reissueticket)
        - [`reissue` (`Connection` Class) Parameters](#reissue-connection-class-parameters)

    -   [`requestUserTicket($userCredentials, $flow)`](#requestuserticketusercredentials-flow)
        - [`requestUserTicket` Parameters](#requestuserticket-parameters)

-   [`Client\Client` Class](#clientclient-class)
    -   [`Client\Client` Constructor](#clientclient-constructor)

    -   [`header($uri, $method, $ticket, $options)`](#headeruri-method-ticket-options)
        - [`header` Parameters](#header-parameters)

-   [`Client\ClientException` Class](#clientclientexception-class)

-   [Shared Arrays](#shared-arrays)
    - [App](#app)
    - [Grant](#grant)
    - [Ticket](#ticket)
    - [Ticket Options](#ticket-options)

Word Usage
----------

In this document the words "client" and "application" are interchangeable.

Namespace
---------

All classes and sub-namespaces are within the `Shawm11\Oz` namespace.

`Server\Endpoints` Class
------------------------

Contains the endpoint methods that provide a HTTP request handler
implementations which are designed to be plugged into a framework such as
[Laravel](https://laravel.com/) or [CodeIgniter](https://www.codeigniter.com/).

### `Server\Endpoints` Constructor

1.  _Shawm11\\Hawk\\Server\\ServerInterface_ `$hawkServer` — (Optional) Hawk
    Server instance to be used

1.  _Shawm11\\Iron\\IronInterface_ `$iron` — (Optional) Iron instance to be used

### `app($request, $options)`

Authenticate an application request, and if valid, issues an application ticket.

Returns the application [ticket](#ticket) for the client as an array.

#### `app` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:

    -   _string_ `method` — (Required) HTTP method of the request

    -   _string_ `url` — (Optional) URL (without the host and port) the request
        was sent to

    -   _string_ `host` — (Required) Host of the server the request was sent to
        (e.g. example.com)

    -   _integer_ `port` — (Required) Port number the request was sent to

    -   _string_ `authorization` — (Optional) Value of the `Authorization`
        header in the request.

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
        the value of the `Content-Type` header in the request. Only used for
        payload validation.

1.  _array_ `$options` — (Required) Configuration options that include the
    following:

    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `secret` — Password string used for both encrypting the
            object and integrity (HMAC creation and verification)

        OR

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `encryption` — Password string used for encrypting the
            object

        -   _string_ `integrity` — Password string used for HMAC creation and
            verification

    -   _callable_ `loadAppFunc` — (Required) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the application
            that is used to look up the application's credentials.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _string_ `key` — (Required) Secret key for the application

            -   _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
                Must be either `sha1` or `sha256`.

    -   _array_ `ticket` — (Optional) [Ticket options](#ticket-options) used for
        parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:

        -   _string_ `host` — (Optional) Host of the server (e.g. example.com).
            Overrides the `host` in the `$request` parameter.

        -   _integer_ `port` — (Optional) Port number. Overrides the `port` in
            the `$request` parameter.

        -   _integer_ `timestampSkewSec` — (Optional, default: `60`)
            Amount of time (in seconds) the client and server timestamps can
            differ (usually because of network latency)

        -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
            milliseconds) of the server's local time compared to the client's
            local time

        -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
            "payload"). Only used for payload validation.

        -   _callable_ `nonceFunc` — (Optional) Function for checking the
            generated nonce (**n**umber used **once**) that is used to make the
            MAC unique even if given the same data. It must throw an error if
            the nonce check fails.

### `reissue($request, $payload, $options)`

Reissue an existing ticket (the ticket used to authenticate the request).

Returns the reissued [ticket](#ticket) as an array.

#### `reissue` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:

    -   _string_ `method` — (Required) HTTP method of the request

    -   _string_ `url` — (Optional) URL (without the host and port) the request
        was sent to

    -   _string_ `host` — (Required) Host of the server the request was sent to
        (e.g. example.com)

    -   _integer_ `port` — (Required) Port number the request was sent to

    -   _string_ `authorization` — (Required) Value of the `Authorization`
        header in the request.

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
        the value of the `Content-Type` header in the request. Only used for
        payload validation.

1.  _array_ `$payload` — (Optional) Parsed request body that may contain their
    following:

    -   _string_ `issueTo` — (Optional) Application ID that can be different
        than the one of the current application. Used to delegate access between
        applications. Defaults to the current application.

    -   _array_ `scope` — (Optional) Scope strings which must be a subset of the
        given ticket's granted scope. Defaults to the original ticket scope.

1.  _array_ `$options` — (Required) Configuration options that include the
    following:

    -   _string_ or _array_ `encryptionPassword` — (Required) Can be either a
        password string or associative array that contains:

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `secret` — Password string used for both encrypting the
            object and integrity (HMAC creation and verification)

        OR

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `encryption` — Password string used for encrypting the
            object

        -   _string_ `integrity` — Password string used for HMAC creation and
            verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _callable_ `loadAppFunc` — (Optional if using the [Implicit
        Workflow](implicit-workflow.md)) Function for looking up the application
        credentials based on the provided credentials ID. This is often done by
        looking up the application credentials in a database. The function must
        have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the application
            that is used to look up the application's credentials.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _string_ `key` — (Required) Secret key for the application

            -   _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
                Must be either `sha1` or `sha256`.

    -   _callable_ `loadGrantFunc` — (Required) Function for looking up the
        grant. This is often done by looking up the grant in a database. The
        function must have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the grant.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _array_ `grant` — (Required) [Grant array](#grant)

            -   _array_ `ext` — (Optional) Used to include custom server data in
                the ticket and response. Contains the following:

                -   _array_ `public` — (Optional) Associative array that will be
                    included in the response under `ticket.ext` and in the
                    encoded ticket as `ticket.ext.public`.

                -   _array_ `private` — (Optional) Associative array that will
                    only be included in the encoded ticket as
                    `ticket.ext.private`

    -   _array_ `ticket` — (Optional) [Ticket options](#ticket-options) used for
        parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:

        -   _string_ `host` — (Optional) Host of the server (e.g. example.com).
            Overrides the `host` in the `$request` parameter.

        -   _integer_ `port` — (Optional) Port number. Overrides the `port` in
            the `$request` parameter.

        -   _integer_ `timestampSkewSec` — (Optional, default: `60`)
            Amount of time (in seconds) the client and server timestamps can
            differ (usually because of network latency)

        -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
            milliseconds) of the server's local time compared to the client's
            local time

        -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
            "payload"). Only used for payload validation.

        -   _callable_ `nonceFunc` — (Optional) Function for checking the
            generated nonce (**n**umber used **once**) that is used to make the
            MAC unique even if given the same data. It must throw an error if
            the nonce check fails.

### `rsvp($request, $payload, $options)`

Authenticate an application request and if valid and exchange the provided RSVP
with a user ticket.

Returns the user [ticket](#ticket) as an array.

#### `rsvp` (`Endpoints` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:

    -   _string_ `method` — (Required) HTTP method of the request

    -   _string_ `url` — (Optional) URL (without the host and port) the request
        was sent to

    -   _string_ `host` — (Required) Host of the server the request was sent to
        (e.g. example.com)

    -   _integer_ `port` — (Required) Port number the request was sent to

    -   _string_ `authorization` — (Optional) Value of the `Authorization`
        header in the request.

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
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

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `secret` — Password string used for both encrypting the
            object and integrity (HMAC creation and verification)

        OR

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `encryption` — Password string used for encrypting the
            object

        -   _string_ `integrity` — Password string used for HMAC creation and
            verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _callable_ `loadAppFunc` — (Optional if using the [Implicit
        Workflow](implicit-workflow.md)) Function for looking up the application
        credentials based on the provided credentials ID. This is often done by
        looking up the application credentials in a database. The function must
        have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the application
            that is used to look up the application's credentials.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _string_ `key` — (Required) Secret key for the application

            -   _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
                Must be either `sha1` or `sha256`.

    -   _callable_ `loadAppFunc` — (Optional if using the [Implicit
        Workflow](implicit-workflow.md)) Function for looking up the application
        credentials based on the provided credentials ID. This is often done by
        looking up the application credentials in a database. The function must
        have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the grant.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _array_ `grant` — (Required) [Grant array](#grant)

            -   _array_ `ext` — (Optional) Used to include custom server data in
                the ticket and response. Contains the following:

                -   _array_ `public` — (Optional) Associative array that will be
                    included in the response under `ticket.ext` and in the
                    encoded ticket as `ticket.ext.public`.

                -   _array_ `private` — (Optional) Associative array that will
                    only be included in the encoded ticket as
                    `ticket.ext.private`

    -   _array_ `ticket` — (Optional) [Ticket options](#ticket-options) used for
        parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:

        -   _string_ `host` — (Optional) Host of the server (e.g. example.com).
            Overrides the `host` in the `$request` parameter.

        -   _integer_ `port` — (Optional) Port number. Overrides the `port` in
            the `$request` parameter.

        -   _integer_ `timestampSkewSec` — (Optional, default: `60`)
            Amount of time (in seconds) the client and server timestamps can
            differ (usually because of network latency)

        -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
            milliseconds) of the server's local time compared to the client's
            local time

        -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
            "payload"). Only used for payload validation.

        -   _callable_ `nonceFunc` — (Optional) Function for checking the
            generated nonce (**n**umber used **once**) that is used to make the
            MAC unique even if given the same data. It must throw an error if
            the nonce check fails.

### `user($request, $payload, $options)`

Issue a user ticket to the application using the set of user credentials given
in the payload. Only used for the [User Credentials](user-credentials-workflow.md)
and [Implicit](implicit-workflow.md) Oz workflows.

Returns the user [ticket](#ticket) as an array.

#### `user` Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:

    -   _string_ `method` — (Required) HTTP method of the request

    -   _string_ `url` — (Optional) URL (without the host and port) the request
        was sent to

    -   _string_ `host` — (Required) Host of the server the request was sent to
        (e.g. example.com)

    -   _integer_ `port` — (Required) Port number the request was sent to

    -   _string_ `authorization` — (Optional) Value of the `Authorization`
        header in the request.

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
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

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `secret` — Password string used for both encrypting the
            object and integrity (HMAC creation and verification)

        OR

        -   _string_ or _integer_ `id` — Unique identifier (consisting of only
            underscores (`_`), letters, and numbers) for the password for when
            there are multiple possible passwords. Used for password rotation.

        -   _string_ `encryption` — Password string used for encrypting the
            object

        -   _string_ `integrity` — Password string used for HMAC creation and
            verification

    -   _array_ `decryptionPasswords` — (Optional) List of possible passwords
        that could have been used for ticket encryption. If this option is
        given, the `encryptionPassword` must be an array with an `id` value that
        is a key for this array. For password rotation.

    -   _array_ `grant` — (Required) Options used to create the grant. Contains
        the following:

        -   `exp` — (Required) Grant expiration time in milliseconds since
            January 1, 1970.

        -   `scope` — (Optional) Scope granted by the user to the application

    -   _array_ `allowedGrantTypes`— (Optional) List of [grant](#grant) types
        (or Oz workflows) that are allowed. If `user_credentials` is not in this
        array, then the [User Credentials Workflow](user-credentials-workflow.md)
        is disabled. If `implicit` is not in this array, then the [Implicit
        Workflow](implicit-workflow.md) is disabled.
        Default: `['rsvp', 'user_credentials', 'implicit']`

    -   _callable_ `loadAppFunc` — (Required if using the [User Credentials
        Workflow](#user-credentials-workflow.md)) Function for looking up the
        application credentials based on the provided credentials ID. This is
        often done by looking up the application credentials in a database. The
        function must have the following:

        -   Parameter: _string_ `$id` — (Required) Unique ID for the application
            that is used to look up the application's credentials.

        -   Returns: _array_ — (Required) Set of credentials that contains the
            following:

            -   _string_ `key` — (Required) Secret key for the application

            -   _string_ `algorithm` — (Required) Algorithm to be used for HMAC.
                Must be either `sha1` or `sha256`.

    -   _callable_ `verifyUserFunc`— (Required) Function for verifying the user
        using the user credentials. The function must have the following:

        -   Parameter: _string_ or _array_ `$userCredentials` — (Required)
            User's credentials

        -   Returns: _string_ — (Required) User ID

    -   _callable_ `storeGrantFunc` — (Required) Function for storing the grant
        that is created. The function must have the following:

        - Parameter: _string_ `$id` — (Required) Unique ID for the grant.
        - Returns: _string_ — (Required) Grant's unique ID

    -   _array_ `ticket` — (Optional) [Ticket options](#ticket-options) used for
        parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:

        -   _string_ `host` — (Optional) Host of the server (e.g. example.com).
            Overrides the `host` in the `$request` parameter.

        -   _integer_ `port` — (Optional) Port number. Overrides the `port` in
            the `$request` parameter.

        -   _integer_ `timestampSkewSec` — (Optional, default: `60`)
            Amount of time (in seconds) the client and server timestamps can
            differ (usually because of network latency)

        -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
            milliseconds) of the server's local time compared to the client's
            local time

        -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
            "payload"). Only used for payload validation.

        -   _callable_ `nonceFunc` — (Optional) Function for checking the
            generated nonce (**n**umber used **once**) that is used to make the
            MAC unique even if given the same data. It must throw an error if
            the nonce check fails.

`Server\Server` Class
---------------------

Server implementation utilities.

### `Server\Server` Constructor

1. _Shawm11\\Hawk\\Server\\ServerInterface_ `$hawkServer` — (Optional) Hawk
   Server instance to be used

### `authenticate($request, $encryptionPassword, $checkExpiration, $options)`

Validate an incoming request using Hawk and performs additional Oz-specific
validations. If the request is valid, an application ticket is issued.

Returns the following if authentication is successful.

-   _array_ `ticket` — Decoded ticket that was given in the header of the
    request

-   _array_ `artifacts` — Hawk components of the request including the
    `Authorization` HTTP header. It includes the following:

    - _string_ `method` — Request method
    - _string_ `host` — Request host
    - _string_ `port` — Request port
    - _string_ `resource` — URL of the request relative to the host
    - _string_ `ts` — Timestamp (as milliseconds since January 1, 1970)
    - _string_ `nonce` — Nonce used to create the `mac`
    - _string_ `hash` — Payload hash. Only used for payload validation.
    - _string_ `ext` — Extra application-specific data
    - _string_ `app` — Application ID. Only used with [Oz](https://github.com/hueniverse/oz).
    - _string_ `dlg` — 'delegated-by' attribute. Only used with [Oz](https://github.com/hueniverse/oz).
    - _string_ `mac` — HMAC digest of the other items in this array
    - _string_ `id` — Client's unique Hawk ID

#### `authenticate` (`Server` Class) Parameters

1.  _array_ `$request` — (Required) Request data. Contains the following:

    -   _string_ `method` — (Required) HTTP method of the request

    -   _string_ `url` — (Optional) URL (without the host and port) the request
        was sent to

    -   _string_ `host` — (Required) Host of the server the request was sent to
        (e.g. example.com)

    -   _integer_ `port` — (Required) Port number the request was sent to

    -   _string_ `authorization` — (Optional) Value of the `Authorization`
        header in the request. See [`header()` for the `Client` class](#headeruri-method-options).

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
        the value of the `Content-Type` header in the request. Only used for
        payload validation.

1.  _string_ or _array_ `$encryptionPassword` — (Required) Password (as a
    string) used for ticket encryption or a list of possible passwords (as an
    array) that could have been used for ticket encryption (for password
    rotation)

1.  _array_ `$options` — (Required) Configuration options that include the
    following:

    -   _array_ `ticket` — (Optional) [Ticket options](#ticket-options) used for
        parsing and issuance

    -   _array_ `hawk` — (Optional) Hawk options, which include the following:

        -   _string_ `host` — (Optional) Host of the server (e.g. example.com).
            Overrides the `host` in the `$request` parameter.

        -   _integer_ `port` — (Optional) Port number. Overrides the `port` in
            the `$request` parameter.

        -   _integer_ `timestampSkewSec` — (Optional, default: `60`) Amount of
            time (in seconds) the client and server timestamps can differ
            (usually because of network latency)

        -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
            milliseconds) of the server's local time compared to the client's
            local time

        -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
            "payload"). Only used for payload validation.

        -   _callable_ `nonceFunc` — (Optional) Function for checking the
            generated nonce (**n**umber used **once**) that is used to make the
            MAC unique even if given the same data. It must throw an error if
            the nonce check fails.

`Server\Ticket` Class
---------------------

Ticket issuance, parsing, encoding, and re-issuance utilities.

### `Server\Ticket` Constructor

1.  _string_ or _array_ `$encryptionPassword` — (Required) Can be either a
    password string or associative array that contains:

    -   _string_ or _integer_ `id` — Unique identifier (consisting of only
        underscores (`_`), letters, and numbers) for the password for when there
        are multiple possible passwords. Used for password rotation.

    -   _string_ `secret` — Password string used for both encrypting the
        object and integrity (HMAC creation and verification)

    OR

    -   _string_ or _integer_ `id` — Unique identifier (consisting of only
        underscores (`_`), letters, and numbers) for the password for when there
        are multiple possible passwords. Used for password rotation.

    -   _string_ `encryption` — Password string used for encrypting the object

    -   _string_ `integrity` — Password string used for HMAC creation and
        verification

1.  _array_ `$options` — (Optional) [Ticket options](#ticket-options) used for
    parsing and issuance

1.  _Shawm11\\Iron\\IronInterface_ `$iron` — (Optional) Iron instance to be used

### `issue($app, $grant)`

Issue a new application or user ticket.

Returns a new application or user [ticket](#ticket) as an array.

#### `issue` Parameters

1.  _array_ `$app` — (Required if not using the [Implicit
    Workflow](implicit-workflow.md)) [App credentials](#app) of the application
    the application ticket will be issued to

1.  _array_ `$grant` — (Optional) [Grant](#grant) for the application

### `reissue($parentTicket, $grant)`

Reissue an application or user ticket.

Returns the reissued [ticket](#ticket) as an array.

#### `reissue` (`Ticket` Class) Parameters

1.  _array_ `$parentTicket` — (Required) [Ticket](#ticket) to be reissued

1.  _array_ `$grant` — (Optional) [Grant](#grant) for the application the ticket
    is being (re)issued to

### `rsvp($app, $grant)`

Generate an RSVP string representing a user grant.

Returns a user [ticket](#ticket) as an array for the application to use

#### `rsvp` (`Ticket` Class) Parameters

1.  _array_ `$app` — (Required) [App credentials](#app) of the application the
    user ticket will be issued to

1.  _array_ `$grant` — (Required) [Grant](#grant) for the application. The grant
    is not allowed to be `null`.

### `generate($ticket)`

Add the cryptographic properties to a ticket and prepare the ticket response.

Returns the completed [ticket](#ticket) as an array.

#### `generate` Parameters

1. _array_ `$ticket` — (Required) Incomplete [ticket](#ticket) that only
   contains the following:
   - `exp`
   - `app`
   - `user`
   - `scope`
   - `grant`
   - `dlg`

### `parse($id)`

Decode a ticket ID (an iron-sealed string) into a ticket.

Returns the [ticket](#ticket) (as an array) that was encoded in the given
string.

#### `parse` Parameters

1. _string_ `$id` — (Required) Ticket ID which is the encoded ticket

`Server\Scope` Class
--------------------

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

`Server\ServerException` Class
------------------------------

The exception that is thrown when there is a _server_ Oz error.

`Server\BadRequestException` Class
----------------------------------

A type of `Server\ServerException` exception that represents an HTTP
`400 Bad Request` server response.

### `getCode()` (`BadRequestException` Class)

Inherited method from PHP's `Exception` class. Gives HTTP status code, which is
always `400`, as an integer.

### `getMessage()` (`BadRequestException` Class)

Inherited method from PHP's `Exception` class. Gives the error message text.

`Server\UnauthorizedException` Class
------------------------------------

A type of `Server\ServerException` exception that represents an HTTP
`401 Unauthorized` server response.

### `Server\UnauthorizedException` Constructor

1.  _string_ `$message` — (Optional) Exception message to throw. It is also
    included in the `WWW-Authenticate` header.

1.  _array_ `$wwwAuthenticateHeaderAttributes` — (Optional) Associative array of
    keys and values to include in the `WWW-Authenticate`.

1.  _integer_ `$code` — (Optional) HTTP status code that the response should
    have

1.  _Throwable_ `$previous` — (Optional) Previous exception used for exception
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

`Server\Forbidden` Class
------------------------

A type of `Server\ServerException` exception that represents an HTTP
`403 Forbidden` server response.

### `getCode()` (`Forbidden` Class)

Inherited method from PHP's `Exception` class. Gives HTTP status code, which is
always `403`, as an integer.

### `getMessage()` (`Forbidden` Class)

Inherited method from PHP's `Exception` class. Gives the error message text.

`Client\Connection` Class
-------------------------

An Oz client connection manager that provides easier access to protected
resources.

### `Client\Connection` Constructor

1.  _array_ `$settings` — (Required) Configuration. Includes the following:

    -   _array_ `endpoints` — (Optional) Server Oz protocol endpoint paths.
        Includes the following:

        -   _string_ `app` — (Optional) Application credentials endpoint.
           Defaults to `/oz/app`.

        -   _string_ `reissue` — (Optional) Ticket reissue endpoint. Defaults to
            `/oz/reissue`.

    -   _string_ `uri` — (Required) Server full root URI without path (e.g.
        '<https://example.com>')

    -   _array_ `credentials` — (Required if not using the [Implicit
        Workflow](implicit-workflow.md)) Application's Hawk credentials, which
        include the following:

        -   _string_ `key` — Secret key for the application

        -   _string_ `algorithm` — Algorithm to be used for HMAC. Must be an
            algorithm in the [`$algorithms` array property of the `Crypto` class](#algorithms-property).

1.  _Shawm11\\Hawk\\Client\\ClientInterface_ `$hawkClient` — (Optional) Hawk
    Client instance to be used

### `request($path, $ticket, $options)`

Request a protected resource.

Returns an array that contains the following:

-   _integer_ `code` — HTTP response code

-   _array_ `result` — Requested resource (parsed to array if JSON)

-   _ticket_ `ticket` — Ticket used to make the request, or a reissued ticket if
    the ticket used to make the request expired

#### `request` Parameters

1.  _string_ `$path` — (Required) URL of the request relative to the host (e.g.
    `/resource`)

1.  _array_ `$ticket` — (Required) Application or user ticket for the client. If
    the ticket is expired, there will be an attempt to automatically refresh it.

1.  _array_ `$options` — (Optional) Configuration. May include the following:

    -   _string_ `method` — (Optional) HTTP method. Defaults to `'GET'`.

    -   _string_ or _array_ `payload` — (Optional) Request payload. Defaults to
        no payload.

### `app($path, $options)`

Request a protected resource using a shared application ticket.

Returns an array that contains the following:

-   _integer_ `code` — HTTP response code

-   _array_ `result` — Requested resource (parsed to array if JSON)

-   _ticket_ `ticket` — Ticket used to make the request, or a reissued ticket if
    the ticket used to make the request expired

#### `app` (`Connection` Class) Parameters

1.  _string_ `$path` — (Required) URL of the request relative to the host (e.g.
    `/resource`)

1.  _array_ `$options` — (Optional) Configuration. May include the following:

    -   _string_ `method` — (Optional) HTTP method. Defaults to `'GET'`.

    -   _string_ or _array_ `payload` — (Optional) Request payload. Defaults to
        no payload.

### `reissue($ticket)`

Reissue (refresh) a ticket.

Returns the reissued [ticket](#ticket) as an array.

#### `reissue` (`Connection` Class) Parameters

1. _array_ `$ticket` — (Required) Ticket being reissued

### `requestUserTicket($userCredentials, $flow)`

Request a user ticket using the given user credentials.

Returns the response as an array that contains the following:

- _integer_ `statusCode` — Status code
- _string_ `body` — Response body
- _array_ `headers` — Response headers

#### `requestUserTicket` Parameters

1.  _string_ or _array_ `$userCredentials` — (Required) User's credentials

1.  _string_ `$flow` — (Optional) Type of Oz flow to use to attempt to retrieve
    a user ticket. Must be one of the following:

    -   `auto` — (Default) Automatically determine the flow being used based on
        the application credentials in the settings that were set in the
        [constructor](#clientconnection-class). If the application credentials
        are set, then the  [User Credentials](user-credentials-workflow.md) Flow
        will be used. If the application credentials are NOT set, then the
        [Implicit flow](implicit-workflow.md) will be used.

    -   `user_credentials` — Attempt to retrieve user ticket with application
        authentication in the [User Credentials](user-credentials-workflow.md)

    -   `implicit` — Attempt to retrieve user ticket WITHOUT application
        authentication in the [Implicit flow](implicit-workflow.md)

`Client\Client` Class
---------------------

Manages the ticket lifecycle and will automatically refresh the ticket when it
expires.

### `Client\Client` Constructor

1. _Shawm11\\Hawk\\Client\\ClientInterface_ `$hawkClient` — (Optional) Hawk
   Client instance to be used

### `header($uri, $method, $ticket, $options)`

Generate the value for an HTTP `Authorization` header for a request to the
server.

Returns an array that contains the following:

-   _string_ `header` — Value for the `Authorization` header for the client's
    request to the server.

-   _array_ `artifacts` — Components used to construct the request including the
    `Authorization` HTTP header. It includes the following:

    - _string_ `method` — Request method
    - _string_ `host` — Request host
    - _string_ `port` — Request port
    - _string_ `resource` — URL of the request relative to the host
    - _string_ `ts` — Timestamp (as milliseconds since January 1, 1970)
    - _string_ `nonce` — Nonce used to create the `mac`
    - _string_ `hash` — Payload hash. Only used for payload validation.
    - _string_ `ext` — Extra application-specific data
    - _string_ `app` — Application ID. Only used with [Oz](https://github.com/hueniverse/oz).
    - _string_ `dlg` — 'delegated-by' attribute. Only used with [Oz](https://github.com/hueniverse/oz).

#### `header` Parameters

1.  _string_ or _array_ `$uri` — (Required) URI (as a string) of the request or
    an array that is the output of PHP's `parse_url()`

1.  _string_ `$method` — (Required) HTTP verb of the request (e.g. `GET`,
    `POST`)

1.  _array_ `$ticket` — (Required) Application or user ticket for the client

1.  _array_ `$options` — (Required) Hawk attributes that will be integrated
    into the `Authorization` header value. It includes the following:

    -   _float_ `timestamp` — (Optional) Timestamp (as milliseconds since
        January 1, 1970)

    -   _string_ `nonce` — (Optional) Nonce to be used to create the HMAC

    -   _string_ `hash` — (Optional) Payload hash. Only used for payload
        validation.

    -   _string_ `payload` — (Optional) UTF-8-encoded request body (or
        "payload"). Only used for payload validation.

    -   _string_ `contentType` — (Optional) Payload content type. It is usually
        the value of the `Content-Type` header in the request. Only used for
        payload validation.

    -   _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
        milliseconds) of the client's local time compared to the server's local
        time

    -   _string_ `ext` — (Optional) Extra application-specific data

`Client\ClientException` Class
------------------------------

The exception that is thrown when there is a _client_ Oz error.

Shared Arrays
-------------

### App

_array_ — Set of credentials that contains the following:

-   _string_ `id` — (Required) Unique ID for the application

-   _string_ `key` — (Required) Secret key for the client

-   _string_ `algorithm` — (Required) Algorithm to be used for HMAC. Must be
    either `sha1` or `sha256`.

-   _array_ `scope` — (Optional) Scope of the ticket to be issued

-   _boolean_ `delegate` — (Optional) If the application is allowed to delegate
    a ticket to another application. Defaults to `false`.

### Grant

_array_ — If set, the issued ticket is going to be a user ticket, and this grant
is going to be issued with it. If set to `null`, the issued ticket will be an
application ticket.

-   _string_ `id` — (Required) Unique ID for the grant

-   _string_ `app` — (Required) Application ID

-   _string_ `user` — (Required) User ID

-   _float_ `exp` — (Required) Grant expiration time in milliseconds since
    January 1, 1970

-   _array_ `scope` — (Optional) Scope granted by the user to the application

-   _string_ `type` — (Optional) Type of grant. In other words, how the grant
    was obtained. It can be one of the following values:

    - `rsvp` — (Default) Grant was obtained using the [RSVP Workflow](rsvp-workflow-without-delegation.md)
    - `user_credentials` — Grant was obtained using the [User Credentials](user-credentials-workflow.md)
    - `implicit` — Grant was obtained using the [Implicit Workflow](implicit-workflow.md)

### Ticket

_array_ — Ticket and its public properties. It contains the following:

-   _string_ `id` — Ticket ID used for making authenticated Hawk requests

-   _string_ `key` — Secret key (only known by the application and the server)
    used to authenticate

-   _string_ `algorithm` — HMAC algorithm used to authenticate. Default is
    `sha256`.

-   _float_ `exp` — Ticket expiration time in milliseconds since January 1, 1970

-   _string_ `app` — Application id the ticket was issued to

-   _string_ `user` — User ID if the ticket represents access to user resources.
    If no user ID is included, the ticket allows the application access to the
    application own resources only.

-   _array_ `scope` — Ticket scope. Defaults to `[]` if no scope is specified.

-   _array_ `grant` — If `user` is set, includes the grant ID referencing the
    authorization granted by the user to the application. Can be a unique ID or
    string encoding the grant information as long as the server is able to parse
    the information later.

-   _boolean_ `delegate` — If `false`, the ticket cannot be delegated regardless
    of the application permissions. Defaults to `true` which means use the
    application permissions to delegate.

-   _string_ `dlg` — If the ticket is the result of access delegation, the
    application ID of the delegating application

-   _array_ `ext` — Custom server public data attached to the ticket

### Ticket Options

_array_ — Supported ticket parsing and issuance options passed to the [Ticket](#serverticket-class)
methods. Each [endpoint](#serverendpoints-class) utilizes a different subset of
these options but it is safe to pass one common object to all (it will ignore
unused options). The ticket options contain the following:

-   _float_ `ttl` — (Optional) Sets the ticket lifetime in milliseconds when
    generating a ticket. Defaults to `3600000` (1 hour) for tickets and `60000`
    (1 minutes) for RSVPs.

-   _boolean_ `delegate` — (Optional) If `false`, the ticket cannot be delegated
    regardless of the application permissions. Defaults to `true` which means
    use the application permissions to delegate.

-   _array_ `iron` — (Optional) Overrides the default Iron configuration

-   _integer_ `keyBytes` — (Optional) Hawk key length in bytes. Defaults to
    `32`.

-   _string_ `hmacAlgorithm` — (Optional) Hawk HMAC algorithm. Defaults to
    `sha256`.

-   _array_ `ext` — (Optional) Used to include custom server data in the ticket
    and response. Contains the following:

    -   _array_ `public` — (Optional) Associative array that will be included in
        the response under `ticket.ext` and in the encoded ticket as
        `ticket.ext.public`.

    -   _array_ `private` — (Optional) Associative array that will only be
        included in the encoded ticket as `ticket.ext.private`
