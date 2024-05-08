<!-- omit in toc -->
# Server Usage Example (RSVP Workflow)

Because PHP is a language most commonly used for server logic, the "Server"
usage is more common than the "Client" usage.

These examples are not completely functional, but they should be enough to show
you how to use this package.

<!-- omit in toc -->
## Table of Contents

- [Handle Request Authenticated Using Ticket](#handle-request-authenticated-using-ticket)
- [Handle `/oz/app` Request](#handle-ozapp-request)
- [Handle Reissue (`/oz/reissue`) Request](#handle-reissue-ozreissue-request)
- [Handle RSVP (`/oz/rsvp`) Request](#handle-rsvp-ozrsvp-request)

## Handle Request Authenticated Using Ticket

The process of handling a request that was authenticated using a ticket is the
same for all of the workflows. See the [example of handling an authenticated
request when using any of the workflows](docs/usage-examples/all-workflows-server.md#handle-request-authenticated-using-ticket).

## Handle `/oz/app` Request

Use the `app()` function in the `Shawm11\Oz\Server\Endpoints` class to handle
requests to the `/oz/app` endpoint.

```php
<?php

use Shawm11\Oz\Server\Endpoints as OzEndpoints;
use Shawm11\Oz\Server\ServerException as OzServerException;

function handleAppRequest() {
    $appTicket = [];
    // Pretend to somehow get the request data
    $requestData = [
        'method' => 'GET',
        'url' => '/resource/4?a=1&b=2',
        'host' => 'example.com',
        'port' => 8080,
        'authorization' => 'Hawk id="dh37fgj492je", ts="1353832234", nonce="j4h3g2", ext="some-app-ext-data", mac="6R4rV5iE+NPoym+WwjeHzjAGXUtLNIxmo1vpMofpLAE="'
    ];
    $options = [
        'encryptionPassword' => 'some_separate_password_only_known_to_the_server_that_is_at_least_32_characters',
        // Function for retrieving app credentials
        'loadAppFunc' => function ($id) { // This is required
            // Pretend to somehow retrieve the app credentials using the given ID ($id)
            $appCredentials = [
                'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
                'algorithm' => 'sha256'
            ];

            return $appCredentials;
        }
    ];

    try {
        $appTicket = (new OzEndpoints)->app($requestData, $options);
    } catch (OzServerException $e) {
        $httpStatusCode = $e->getCode();
        // Send error response...
        send($httpStatusCode, $e->getMessage()); // Fictional function
        return;
    }

    // Maybe do some other stuff before sending the response

    // A fictional function that sends a response containing the ticket with an
    // HTTP code of 200
    send(200, $ticket);
}
```

## Handle Reissue (`/oz/reissue`) Request

The process of handling a request to reissue a ticket is the same for all of the
workflows. See the [example of handling a request to reissue a ticket when using
any of the workflows](docs/usage-examples/all-workflows-server.md#handle-request-authenticated-using-ticket).

## Handle RSVP (`/oz/rsvp`) Request

Use the `rsvp()` function in the `Shawm11\Oz\Server\Endpoints` class to handle
requests to the `/oz/rsvp` endpoint.

```php
<?php

use Shawm11\Oz\Server\Endpoints as OzEndpoints;
use Shawm11\Oz\Server\ServerException as OzServerException;

function handleRsvpRequest() {
    $userTicket = [];
    // Pretend to somehow get the request data
    $requestData = [
        'method' => 'GET',
        'url' => '/resource/4?a=1&b=2',
        'host' => 'example.com',
        'port' => 8080,
        'authorization' => 'Hawk id="Fe26.2**some-app-ticket-id", ts="1353832234", nonce="j4h3g2", ext="some-app-ext-data", mac="6R4rV5iE+NPoym+WwjeHzjAGXUtLNIxmo1vpMofpLAE="'
    ];
    // Pretend to somehow get the request body
    $requestBody = [
        'rsvp' => 'some_iron_string_that_was_issued_by_the_server_when_the_user_approved_the_scope'
    ];

    $options = [ // This is required
        'encryptionPassword' => 'some_separate_password_only_known_to_the_server_that_is_at_least_32_characters',
        // Function for retrieving app credentials
        'loadAppFunc' => function ($id) {
            // Pretend to somehow retrieve the credentials using the given ID ($id)
            $appCredentials = [
                'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
                'algorithm' => 'sha256'
            ];

            return $appCredentials;
        },
        // Function for retrieving grant
        'loadGrantFunc' => function ($id) {  // This is required
            // Pretend to somehow retrieve grant using the given ID ($id)
            $grant = [
                'id' => $id,
                'app' => '123',
                'user' => '456',
                'exp' => 1352535473414,
                'scope' => ['b']
            ]
        }
    ];

    try {
        $userTicket = (new OzEndpoints)->rsvp($requestData, $requestBody, $options);
    } catch (OzServerException $e) {
        $httpStatusCode = $e->getCode();
        // Send error response...
        send($httpStatusCode, $e->getMessage()); // Fictional function
        return;
    }

    // Maybe do some other stuff before sending the response

    // A fictional function that sends a response containing the ticket with an
    // HTTP code of 200
    send(200, $ticket);
}
```
