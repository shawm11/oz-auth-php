Client Usage Example (All Workflows)
------------------------------------

These examples are not completely functional, but they should be enough to show
you how to use this package. They are applicable to any of the 3 workflows
(RSVP, User Credentials, and Implicit).

Table of Contents
-----------------

- [Make Request with Stored User Ticket](#request-with-stored-user-ticket)

Make Request with Stored User Ticket
------------------------------------

Use the `request()` function to make a request using an application ticket or a
user ticket.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

function makeRequestWithUserTicket() {
    $response = [];
    $ozConnection = new OzConnection([
        // Base URI for all requests
        'uri' => 'http://example.com/api',
        // The application credentials are not necesssary for this, but you can
        // include them anyway
        'credentials' => []
    ]);
    // Somehow get the user ticket that was stored somewhere safe.
    // The getStoreUserTicket() is a fictional function.
    $storedUserTicket = getStoreUserTicket();

    try {
        // GET /resource?a=b
        $response = $ozConnection->request('/resource?a=b', $storedUserTicket);
    } catch (OzClientException $e) {
        echo 'ERROR: ' . $e->getMessage();
        return;
    }

    $result = $response['result']; // an array if the response body is JSON, otherwise a string
    $code = $response['code']; // HTTP status code as an integer
    $ticket = $response['ticket']; // (possibly reissued) user ticket as an array

    // Do some stuff with user ticket and server response...
}
```
