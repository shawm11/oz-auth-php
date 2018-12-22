Server Usage Example (RSVP Workflow)
====================================

Because PHP is a language most commonly used for server logic, the "Server"
usage is more common than the "Client" usage.

These examples are not completely functional, but they should be enough to show
you how to use this package.

Table of Contents
-----------------

- [Handle Request Authenticated Using Ticket](#handle-request-authenticated-using-ticket)
- [Handle `/oz/app` Request](#handle-ozapp-request)
- [Handle Reissue (`/oz/reissue`) Request](#handle-reissue-ozreissue-request)
- [Handle `/oz/user` Request](#handle-ozuser-request)

Handle Request Authenticated Using Ticket
-----------------------------------------

The process of handling a request that was authenticated using a ticket is the
same for all of the workflows. See the [example of handling an authenticated
request when using any of the workflows](docs/usage-examples/all-workflows-server.md#handle-request-authenticated-using-ticket).

Handle `/oz/app` Request
------------------------

The process of handling an `/oz/app` request is the same for the User
Credentials and RSVP workflows. See the [example of handling a request to
reissue a ticket when using any of the workflows](docs/usage-examples/all-workflows-server.md#handle-ozapp-request).

Handle Reissue (`/oz/reissue`) Request
--------------------------------------

The process of handling a request to reissue a ticket is the same for all of the
workflows. See the [example of handling a request to reissue a ticket when using
any of the workflows](docs/usage-examples/all-workflows-server.md#handle-request-authenticated-using-ticket).

Handle `/oz/user` Request
-------------------------

As with the Implicit workflow, use the `user()` function in the
`Shawm11\Oz\Server\Endpoints` class to handle requests to the `/oz/user`
endpoint.

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
		'username' => 'some_user',
        'password' => 'this password is a secret'
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
        $userTicket = (new OzEndpoints)->user($requestData, $requestBody, $options);
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
