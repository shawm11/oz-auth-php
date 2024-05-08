<!-- omit in toc -->
# Client Usage Examples (User Credentials Workflow)

These examples are not completely functional, but they should be enough to show
you how to use this package.

<!-- omit in toc -->
## Table of Contents

- [Obtain User Ticket](#obtain-user-ticket)
- [Make Request with Stored User Ticket](#make-request-with-stored-user-ticket)

## Obtain User Ticket

In the Implicit workflow, the user credentials (e.g. username and password) must
be provided _in plain text_ when attempting to obtain a user ticket. If the
application Hawk credentials are _not_ set in the Oz connection settings, the
`requestUserTicket()` function assumes the Implicit workflow is being used.

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

function makeRequestWithUserTicket() {
    $appTicketResponse = [];
    $response = [];
    $ozConnection = new OzConnection([
        // Base URI for all requests
        'uri' => 'http://example.com/api'
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

## Make Request with Stored User Ticket

The process of making a request with a stored user ticket is the same for all of
the workflows. See the [example of making a request with a stored user ticket
when using any of the workflows](docs/usage-examples/all-workflows-client.md#make-request-with-stored-user-ticket).
