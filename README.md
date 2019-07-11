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

- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Usage Examples](#usage-examples)
  - [Server Usage Examples](#server-usage-examples)
  - [Client Usage Examples](#client-usage-examples)
- [Documentation](#documentation)
  - [API References](#api-references)
- [Security Considerations](#security-considerations)
- [Contributing/Development](#contributingdevelopment)
- [Versioning](#versioning)
- [License](#license)

<!--lint enable list-item-spacing-->

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
