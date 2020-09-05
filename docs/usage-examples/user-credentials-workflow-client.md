Client Usage Examples (User Credentials Workflow)
=================================================

These examples are not completely functional, but they should be enough to show
you how to use this package.

Table of Contents
-----------------

- [Make Request with Only Application Ticket](#make-request-with-only-application-ticket)
- [Obtain the Application Ticket Then Make Request Afterwards](#obtain-the-application-ticket-then-make-request-afterwards)
- [Obtain User Ticket](#obtain-user-ticket)
- [Make Request with Stored User Ticket](#request-with-stored-user-ticket)

Make Request with Only Application Ticket
-----------------------------------------

The process of making a request with only an application ticket is the same for
the User Credentials and the RSVP workflows. See the [example of making a
request with only an application ticket when using the RSVP workflow](docs/usage-examples/rsvp-workflows-client.md#make-request-with-stored-user-ticket).

Obtain the Application Ticket and Make Request Afterwards
---------------------------------------------------------

The process of obtaining the application ticket and making a request afterwards
with the obtained ticket is the same  for the User Credentials and RSVP
workflows. See the [example of obtaining the application ticket and making a
request afterwards](docs/usage-examples/rsvp-workflows-client.md#obtain-the-application-ticket-and-make-request-afterwards).

Obtain User Ticket
------------------

In the User Credentials workflow, the user credentials (e.g. username and
password) must be provided when attempting to obtain a user ticket. If the
application Hawk credentials are set in the Oz connection settings, the
`requestUserTicket()` function assumes the User Crendentials workflow is being
used.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

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

    try {
        $response = $ozConnection->requestUserTicket([
            'user' => [
                'username' => 'some_user',
                'password' => 'this password is a secret'
            ]
        ]);
    } catch (OzClientException $e) {
        throw new \Exception('ERROR: ' . $e->getMessage());
    }

    if ($response['code'] !== 200) {
        throw new \Exception('Getting application ticket failed');
    }

    $userTicket = $response['result']; // user ticket as an array

    // Do some more stuff...
}
```

Make Request with Stored User Ticket
------------------------------------

The process of making a request with a stored user ticket is the same for all of
the workflows. See the [example of making a request with a stored user ticket
when using any of the workflows](docs/usage-examples/all-workflows-client.md#make-request-with-stored-user-ticket).
