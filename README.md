Oz Authorization PHP
====================

![Version Number](https://img.shields.io/packagist/v/shawm11/oz-auth.svg)
![PHP Version](https://img.shields.io/packagist/php-v/shawm11/oz-auth.svg)
[![License](https://img.shields.io/github/license/shawm11/oz-auth-php.svg)](https://github.com/shawm11/oz-auth-php/blob/master/LICENSE.md)

A PHP implementation of the 5.x version of the
[**Oz**](https://github.com/hueniverse/oz) web authorization protocol.

Table of Contents
-----------------

-   [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)

-   [Usage Examples](#usage-examples)
    - [Server](#server)
    - [Client](#client)

-   [Documentation](#documentation)

-   [Security Considerations](#security-considerations)

-   [Contributing/Development](#contributingdevelopment)

-   [Versioning](#versioning)

-   [License](#license)

Getting Started
---------------

### Prerequisites

- Git 2.9+
- PHP 5.6.0+
- OpenSSL PHP Extension
- JSON PHP Extension
- cURL PHP Extension (Only if using the Oz client)
- [Composer](https://getcomposer.org/)
- Node 6.9.0+ (Only for development)

### Installation

Download and install using [Composer](https://getcomposer.org/):

```shell
composer require shawm11/oz-auth-php
```

Usage Examples
--------------

The examples in this section are not functional, but should be enough to show
you how to use this package.

### Server

Because PHP is a language most commonly used for server logic, the "Server"
usage is more common than the "Client" usage.

```php
<?php

use Shawm11\Oz\Server\Endpoints as OzEndpoints;
use Shawm11\Oz\Server\Server as OzServer;
use Shawm11\Oz\Server\ServerException as OzServerException;
use Shawm11\Oz\Server\BadRequestException as OzBadRequestException;
use Shawm11\Oz\Server\UnauthorizedException as OzUnauthorizedException;
use Shawm11\Oz\Server\ForbiddenException as OzForbiddenException;
use Shawm11\Oz\Server\InternalException as OzInternalException;

/*
 * A fictional function that handles an authenticated request to a resource
 */
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

/*
 * A fictional function that handle requests to /oz/app
 */
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

/*
 * A fictional function that handles requests to /oz/reissue
 */
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

/*
 * A fictional function that handles requests to /oz/rsvp
 */
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

### Client

```php
<?php

use Shawm11\Oz\Client\Connection as OzConnection;
use Shawm11\Oz\Client\ClientException as OzClientException;

/*
 * A fictional function that makes an authenticated request to the server
 */
function makeRequest() {
    $response = [];
	$options = [
		'uri' => 'http://example.com/resource?a=b',
		// Client's application Hawk credentials
		'credentials' => [
			'id' => 'dh37fgj492je',
            'key' => 'aoijedoaijsdlaksjdl',
            'algorithm' => 'sha256'
		]
	];

    try {
		// Send request & wait for response
        $response = (new OzConnection($options))->app($requestData);
    } catch (OzClientException $e) {
        echo 'ERROR: ' . $e->getMessage();
        return;
    }

	$result = $response['result']; // an array if the response body is JSON, otherwise a string
	$code = $response['code']; // integer
	$ticket = $response['ticket']; // array

    // Do some more stuff
}
```

Documentation
-------------

-   [API Reference](docs/api-reference.md) — Details about the API

-   [Oz Workflow (Without Delegation)](docs/oz-workflow-without-delegation.md) —
    General overview of the Oz workflow when delegation is not being used

Security Considerations
-----------------------

See the [Security Considerations](https://github.com/hueniverse/oz#security-considerations)
section of Oz's README.

Contributing/Development
------------------------

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on coding style, Git
commit message guidelines, and other development information.

Versioning
----------

This project using [SemVer](http://semver.org/) for versioning. For the versions
available, see the tags on this repository.

License
-------

This project is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).
