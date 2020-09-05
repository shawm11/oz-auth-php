Server Usage Example (All Workflows)
====================================

Because PHP is a language most commonly used for server logic, the "Server"
usage is more common than the "Client" usage.

These examples are not completely functional, but they should be enough to show
you how to use this package. They are applicable to any of the 3 workflows
(RSVP, User Credentials, and Implicit).

Table of Contents
-----------------

- [Handle Request Authenticated Using Ticket](#handle-request-authenticated-using-ticket)
- [Handle Reissue (`/oz/reissue`) Request](#handle-reissue-ozreissue-request)

Handle Request Authenticated Using Ticket
-----------------------------------------

A fictional function that handles a request to a resource where a ticket was
used to authenticate.

```php
<?php

use Shawm11\Oz\Server\Server as OzServer;

function handleAuthRequest() {
    // Pretend to somehow get the request data
    $requestData = [
        'method' => 'GET',
        'url' => '/resource/4?a=1&b=2',
        'host' => 'example.com',
        'port' => 8080,
        'authorization' => 'Hawk id="Fe26.2**some-user-ticket-id", ts="1353832234", nonce="j4h3g2", ext="some-app-ext-data", mac="6R4rV5iE+NPoym+WwjeHzjAGXUtLNIxmo1vpMofpLAE="'
    ];
    $encryptionPassword = 'some_separate_password_only_known_to_the_server_that_is_at_least_32_characters';

    $ticket = (new OzServer)->authenticate($requestData, $encryptionPassword);

    // Authentication successful! Now do some stuff with the ticket...
}
```

Handle Reissue (`/oz/reissue`) Request
--------------------------------------

Use the `reissue()` function in the `Shawm11\Oz\Server\Endpoints` class to
handle requests to the `/oz/reissue` endpoint.

```php
<?php

use Shawm11\Oz\Server\Endpoints as OzEndpoints;
use Shawm11\Oz\Server\ServerException as OzServerException;

function handleReissueRequest() {
    $reissuedTicket = [];
    // Pretend to somehow get the request data
    $requestData = [
        'method' => 'GET',
        'url' => '/resource/4?a=1&b=2',
        'host' => 'example.com',
        'port' => 8080,
        'authorization' => 'Hawk id="Fe26.2**some-ticket-id", ts="1353832234", nonce="j4h3g2", ext="some-app-ext-data", mac="6R4rV5iE+NPoym+WwjeHzjAGXUtLNIxmo1vpMofpLAE="'
    ];
    $options = [
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
        'loadGrantFunc' => function ($id) {
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
        $reissuedTicket = (new OzEndpoints)->reissue($requestData, [], $options);
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
