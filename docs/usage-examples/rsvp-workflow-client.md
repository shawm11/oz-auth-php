Client Usage Examples (RSVP Workflow)
=====================================

These examples are not completely functional, but they should be enough to show
you how to use this package.

Table of Contents
-----------------

- [Make Request with Only Application Ticket](#make-request-with-only-application-ticket)
- [Obtain the Application Ticket and Make Request Afterwards](#obtain-the-application-ticket-and-make-request-afterwards)
- [Obtain User Ticket Using RSVP](#obtain-user-ticket-using-rsvp)
- [Make Request with Stored User Ticket](#request-with-stored-user-ticket)

Make Request with Only Application Ticket
-----------------------------------------

The application ticket is automatically obtained when making a request (not as a
user) using the `app()` function.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

/*
 * A fictional function that makes an authenticated request to the server using
 * only an application ticket.
 */
function makeRequestUsingAppTicket() {
    $response = [];
    $ozConnection = new OzConnection([
        // Base URI for all requests
        'uri' => 'http://example.com/api',
        // Client's application Hawk credentials previously issued by the server
        'credentials' => [
            'id' => 'dh37fgj492je',
            'key' => 'aoijedoaijsdlaksjdl',
            'algorithm' => 'sha256'
        ]
    ]);

    try {
        // Obtain an application ticket (if one has not been obtained) then
        // request resource (GET /resource?a=b)
        $response = $ozConnection->app('/resource?a=b');
    } catch (OzClientException $e) {
        echo 'ERROR: ' . $e->getMessage();
        return;
    }

    $result = $response['result']; // an array if the response body is JSON, otherwise a string
    $code = $response['code']; // HTTP status code as an integer
    $ticket = $response['ticket']; // application ticket as an array

    // Do some more stuff with application ticket and server response...
}
```

Obtain the Application Ticket and Make Request Afterwards
---------------------------------------------------------

In some cases, it is better to obtain the application ticket then make a request
using that application ticket later.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

/*
 * A fictional function that makes an authenticated request to the server using
 * only a user ticket that was previously obtained and stored.
 */
function makeRequestWithUserTicket() {
    $appTicketResponse = [];
    $response = [];
    $ozConnection = new OzConnection([
        // Base URI for all requests
        'uri' => 'http://example.com/api',
        // Client's application Hawk credentials previously issued by the server
        'credentials' => [
            'id' => 'dh37fgj492je',
            'key' => 'aoijedoaijsdlaksjdl',
            'algorithm' => 'sha256'
        ]
    ]);

    /*
     * Obtain an application ticket
     */
    try {
        $appTicketResponse = $ozConnection->requestAppTicket();
    } catch (OzClientException $e) {
        throw new \Exception('ERROR: ' . $e->getMessage());
    }

    if ($appTicketResponse['code'] !== 200) {
        throw new \Exception('Getting application ticket failed');
    }

    $appTicket = $appTicketResponse['body']; // application ticket as an array

    // Do some stuff before making a request...

    /*
     * Make request with application ticket
     */
    try {
        $response = $ozConnection->request('/resource?a=b', $appTicket, [
            'method' => 'POST',
            'payload' => [
                'foo' => 'bar'
            ]
        ]);
    } catch (OzClientException $e) {
        throw new \Exception('ERROR: ' . $e->getMessage());
    }

    $result = $response['result']; // an array if the response body is JSON, otherwise a string
    $code = $response['code']; // HTTP status code as an integer
    $appTicket = $response['ticket']; // (possibly reissued) application ticket as an array

    // Do some more stuff...
}
```

Obtain User Ticket Using RSVP
-----------------------------

Note how the code is very similar to the code in the [Request with Only
Application Ticket](#request-with-only-application-ticket) example.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

/*
 * A fictional function that obtains a user ticket using a previously obtained
 * RSVP.
 */
function getUserTicket() {
    $response = [];
    $ozConnection = new OzConnection([
        // Base URI for all requests
        'uri' => 'http://example.com/api',
        // Client's application Hawk credentials previously issued by the server
        'credentials' => [
            'id' => 'dh37fgj492je',
            'key' => 'aoijedoaijsdlaksjdl',
            'algorithm' => 'sha256'
        ]
    ])

    try {
        // Obtain an application ticket (if one has not been obtained) then
        // request user ticket using RSVP
        $response = $ozConnection->app('/oz/rsvp', [
            'method' => 'POST',
            'payload' => [
                'rvsp' => 'some_rsvp_that_was_somehow_given_by_the_user'
            ]
        ]);
    } catch (OzClientException $e) {
        echo 'ERROR: ' . $e->getMessage();
        return;
    }

    $result = $response['result']; // user ticket as an array
    $code = $response['code']; // HTTP status code as an integer
    $ticket = $response['ticket']; // application ticket as an array

    // Set the user ticket so it can used later
    $ozConnection->setUserTicket($result);

    // Store the user ticket somewhere...

    // Do some more stuff...
}
```

Make Request with Stored User Ticket
------------------------------------

The process of making a request with a stored user ticket is the same for all of
the workflows. See the [example of making a request with a stored user ticket
when using any of the workflows](docs/usage-examples/all-workflows-client.md#make-request-with-stored-user-ticket).
