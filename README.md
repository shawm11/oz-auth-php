Oz Authorization PHP
====================

![Version Number](https://img.shields.io/packagist/v/shawm11/oz-auth.svg)
![PHP Version](https://img.shields.io/packagist/php-v/shawm11/oz-auth.svg)
[![License](https://img.shields.io/github/license/shawm11/oz-auth-php.svg)](LICENSE.md)

A PHP implementation of the 5.0.0 version of the [**Oz**](https://github.com/outmoded/oz)
web authorization protocol.

**NOTICE**: Although the original JavaScript version of [Oz](https://github.com/outmoded/oz)
will not be maintained anymore, **this library will continue to be maintained**.
The original JavaScript version of Oz was complete and only had periodic
documentation and library dependency updates.

Table of Contents
-----------------

<!--lint disable list-item-spacing-->

- [What is Oz?](#what-is-oz)
  - [Oz and OAuth 2.0](#oz-and-oauth-2.0)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Usage Examples](#usage-examples)
  - [Server Usage Examples](#server-usage-examples)
  - [Client Usage Examples](#client-usage-examples)
- [Documentation](#documentation)
  - [API References](#api-references)
- [Security Considerations](#security-considerations)
- [Related Projects](#related-projects)
- [Contributing/Development](#contributingdevelopment)
- [Versioning](#versioning)
- [License](#license)

<!--lint enable list-item-spacing-->

What is Oz?
-----------

According to the [Oz README](https://github.com/outmoded/oz/blob/master/README.md)

> Oz is a web authorization protocol based on industry best practices. Oz
> combines the Hawk authentication protocol with the Iron encryption protocol
> to provide a simple to use and secure solution for granting and authenticating
> third-party access to an API on behalf of a user or an application.

### Oz and OAuth 2.0

Oz is an alternative to OAuth 1.0a and OAuth 2.0. One of the goals of Oz is make
simple to use for the most common use cases needing little knowledge about web
security while being flexible enough for less common use cases that may need
more advanced web security knowledge. Oz does this by providing default options
that are secure for the most common use cases, in other words Oz aims to be
_secure by default_.

Below is table showing the Oz workflow equivalents for the OAuth 2.0 workflows.

| OAuth 2.0 Workflow                  | Oz Workflow                                      |
| ----------------------------------- | ------------------------------------------------ |
| Authorization Code                  | RSVP _(The only offical workflow)_               |
| Implicit/PKCE                       | Implicit _(Not an offical workflow)_             |
| Resource Owner Password Credentials | User Credentials _(Not an offical workflow)_     |
| Client Credentials                  | [Hawk](https://github.com/shawm11/hawk-auth-php) |

Getting Started
---------------

### Prerequisites

- Git 2.9+
- PHP 7.2.0+
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

Workflows
---------

This package includes two workflows that are not part of the
[official Oz web authorization protocol](https://github.com/outmoded/oz). These
two new workflows are the [User Credentials Workflow](docs/user-credentials-workflow.md)
and the [Implicit Workflow](docs/implicit-workflow.md). The standard Oz workflow
that is specifed by the official protocal is referred to as the ["RSVP workflow"](docs/rsvp-workflow-without-delegation.md).

Usage Examples
--------------

### Server Usage Examples

- [RSVP Workflow — Server](docs/usage-examples/rsvp-workflow-server.md)
- [User Credentials Workflow — Server](docs/usage-examples/user-credentials-workflow-server.md)
- [Implicit Workflow — Server](docs/usage-examples/implicit-workflow-server.md)
- [All Workflows — Server](docs/usage-examples/all-workflows-client.md)

### Client Usage Examples

- [RSVP Workflow — Client](docs/usage-examples/rsvp-workflow-client.md)
- [User Credentials Workflow — Client](docs/usage-examples/user-credentials-workflow-client.md)
- [Implicit Workflow — Client](docs/usage-examples/implicit-workflow-client.md)
- [All Workflows — Client](docs/usage-examples/all-workflows-client.md)

Documentation
-------------

<!--lint disable list-item-spacing-->

- [RSVP Workflow (Without Delegation)](docs/rsvp-workflow-without-delegation.md) —
  General overview of the RSVP (standard) workflow when delegation is not being
  used
- [User Credentials Workflow](docs/user-credentials-workflow.md) — General
  overview of the User Credentials workflow
- [Implicit Workflow](docs/implicit-workflow.md) — General overview of the
  Implicit workflow

### API References

- [Server API](docs/api-reference/server-api.md) — API reference for the classes
  in the `Shawm11\Oz\Server` namespace
- [Client API](docs/api-reference/server-api.md) — API reference for the classes
  in the `Shawm11\Oz\Client` namespace
- [Shared Arrays](docs/api-reference/shared-arrays.md) — Details about
  collections of data used in other parts of the API

<!--lint enable list-item-spacing-->

Security Considerations
-----------------------

See the [Security Considerations](https://github.com/outmoded/oz#security-considerations)
section of Oz's README.

Related Projects
----------------

- [Hawk PHP Implementation](https://github.com/shawm11/hawk-auth-php) — PHP
  implementation of Hawk, an HTTP authentication scheme that is alternative
  OAuth 1.0a and OAuth 2.0 two-legged authentication.

- [Iron PHP Implementation](https://github.com/shawm11/iron-crypto-php) — PHP
  implementation of _iron_ (spelled with all lowercase), a cryptographic utility
  for sealing a JSON object into an encapulated token. _iron_ can be considered
  as an alternative to JSON Web Tokens (JWT).

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

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
