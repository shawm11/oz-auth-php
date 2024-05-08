<!-- omit in toc -->
# Client API Reference

<!-- omit in toc -->
## Table of Contents

- [Word Usage](#word-usage)
- [Namespace](#namespace)
- [`Connection` Class](#connection-class)
  - [`Connection` Constructor](#connection-constructor)
  - [`request($path, $ticket, $options)`](#requestpath-ticket-options)
    - [`request` Parameters](#request-parameters)
  - [`app($path, $options)`](#apppath-options)
    - [`app` (`Connection` Class) Parameters](#app-connection-class-parameters)
  - [`reissue($ticket)`](#reissueticket)
    - [`reissue` (`Connection` Class) Parameters](#reissue-connection-class-parameters)
  - [`requestAppTicket()`](#requestappticket)
  - [`requestUserTicket($userCredentials, $flow)`](#requestuserticketusercredentials-flow)
    - [`requestUserTicket` Parameters](#requestuserticket-parameters)
- [`Client` Class](#client-class)
  - [`Client` Constructor](#client-constructor)
  - [`header($uri, $method, $ticket, $options)`](#headeruri-method-ticket-options)
    - [`header` Parameters](#header-parameters)
- [`ClientException` Class](#clientexception-class)

## Word Usage

In this document the words "client" and "application" are interchangeable.

## Namespace

All classes and sub-namespaces are within the `Shawm11\Oz\Client` namespace.

## `Connection` Class

An Oz client connection manager that provides easier access to protected
resources.

### `Connection` Constructor

1. _array_ `$settings` — (Required) Configuration. Includes the following:
   - _array_ `endpoints` — (Optional) Server Oz protocol endpoint paths.
     Includes the following:
     - _string_ `app` — (Optional) Application credentials endpoint.
        Defaults to `/oz/app`.
     - _string_ `reissue` — (Optional) Ticket reissue endpoint. Defaults to
       `/oz/reissue`.
   - _string_ `uri` — (Required) Server full root URI without path (e.g. `https://example.com`)
   - _array_ `credentials` — (Required if not using the [Implicit Workflow](../implicit-workflow.md))
     Application's Hawk credentials, which include the following:
     - _string_ `key` — Secret key for the application
     - _string_ `algorithm` — Algorithm to be used for HMAC. The value must be
       either `sha256` (recommended) or `sha1`.
2. _Shawm11\\Hawk\\Client\\ClientInterface_ `$hawkClient` — (Optional) Hawk
   Client instance to be used

### `request($path, $ticket, $options)`

Request a protected resource.

Returns an array that contains the following:

- _integer_ `code` — HTTP response code
- _array_ `result` — Requested resource (parsed to array if JSON)
- _ticket_ `ticket` — Ticket used to make the request, or a reissued ticket if
  the ticket used to make the request expired

#### `request` Parameters

1. _string_ `$path` — (Required) URL of the request relative to the host (e.g.
    `/resource`)
2. _array_ `$ticket` — (Required) Application or user ticket for the client. If
    the ticket is expired, there will be an attempt to automatically refresh it.
3. _array_ `$options` — (Optional) Configuration. May include the following:
   - _string_ `method` — (Optional) HTTP method. Defaults to `'GET'`.
   - _string_ or _array_ `payload` — (Optional) Request payload. Defaults to no
     payload.

### `app($path, $options)`

Request a protected resource using an application ticket that is automatically
retrieved (if it has not been previously retrieved) using the application
credentials given in the `Connection` settings. **ONLY FOR THE RSVP AND USER
CREDENTIALS FLOWS.**

Returns an array that contains the following:

- _integer_ `code` — HTTP response code
- _array_ `result` — Requested resource (parsed to array if JSON)
- _ticket_ `ticket` — Ticket used to make the request, or a reissued ticket if
  the ticket used to make the request expired

#### `app` (`Connection` Class) Parameters

1. _string_ `$path` — (Required) URL of the request relative to the host (e.g.
    `/resource`)
2. _array_ `$options` — (Optional) Configuration. May include the following:
   - _string_ `method` — (Optional) HTTP method. Defaults to `'GET'`.
   - _string_ or _array_ `payload` — (Optional) Request payload. Defaults to no
     payload.

### `reissue($ticket)`

Reissue (refresh) a ticket.

Returns the reissued [ticket](shared-array.md#ticket) as an array.

#### `reissue` (`Connection` Class) Parameters

1. _array_ `$ticket` — (Required) Ticket being reissued

### `requestAppTicket()`

Request an application ticket using the application credentials given in the
settings when the object instance was created. **ONLY FOR THE RSVP AND USER
CREDENTIAL FLOWS.**

Returns the response as an array that contains the following:

- _integer_ `code` — HTTP status code
- _string_ `result` — Response body. The application ticket if successful.
- _array_ `headers` — Response headers

### `requestUserTicket($userCredentials, $flow)`

Request a user ticket using the given user credentials. **ONLY FOR THE IMPLICIT
AND USER CREDENTIALS FLOWS.**

Returns the response as an array that contains the following:

- _integer_ `code` — HTTP status code
- _string_ `result` — Response body. The user ticket if successful.
- _array_ `headers` — Response headers

#### `requestUserTicket` Parameters

1. _string_ or _array_ `$userCredentials` — (Required) User's credentials
2. _string_ `$flow` — (Optional) Type of Oz flow to use to attempt to retrieve
   a user ticket. Must be one of the following:
   - `auto` — (Default) Automatically determine the flow being used based on
     the application credentials in the settings that were set in the
     [constructor](#connection-constructor). If the application credentials are
     set, then the [User Credentials](../user-credentials-workflow.md) flow will
     be used. If the application credentials are NOT set, then the
     [Implicit flow](../implicit-workflow.md) will be used.
   - `user_credentials` — Attempt to retrieve user ticket with application
     authentication in the [User Credentials](../user-credentials-workflow.md)
   - `implicit` — Attempt to retrieve user ticket WITHOUT application
     authentication in the [Implicit flow](../implicit-workflow.md)

## `Client` Class

Manages the ticket lifecycle and will automatically refresh the ticket when it
expires.

### `Client` Constructor

1. _Shawm11\\Hawk\\Client\\ClientInterface_ `$hawkClient` — (Optional) Hawk
   Client instance to be used

### `header($uri, $method, $ticket, $options)`

Generate the value for an HTTP `Authorization` header for a request to the
server.

Returns an array that contains the following:

- _string_ `header` — Value for the `Authorization` header for the client's
    request to the server.
- _array_ `artifacts` — Components used to construct the request including the
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

#### `header` Parameters

1. _string_ or _array_ `$uri` — (Required) URI (as a string) of the request or
   an array that is the output of PHP's `parse_url()`
2. _string_ `$method` — (Required) HTTP verb of the request (e.g. `GET`, `POST`)
3. _array_ `$ticket` — (Required) Application or user ticket for the client
4. _array_ `$options` — (Required) Hawk attributes that will be integrated
   into the `Authorization` header value. It includes the following:
   - _float_ `timestamp` — (Optional) Timestamp (as milliseconds since
     January 1, 1970)
   - _string_ `nonce` — (Optional) Nonce to be used to create the HMAC
   - _string_ `hash` — (Optional) Payload hash. Only used for payload
     validation.
   - _string_ `payload` — (Optional) UTF-8-encoded request body (or "payload").
     Only used for payload validation.
   - _string_ `contentType` — (Optional) Payload content type. It is usually
     the value of the `Content-Type` header in the request. Only used for
     payload validation.
   - _float_ `localtimeOffsetMsec` — (Optional, default: `0`) Offset (in
     milliseconds) of the client's local time compared to the server's local
     time
   - _string_ `ext` — (Optional) Extra application-specific data

## `ClientException` Class

The exception that is thrown when there is a _client_ Oz error.
