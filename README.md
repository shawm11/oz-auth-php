<!-- omit in toc -->
# Oz Authorization PHP

![Version Number](https://img.shields.io/packagist/v/shawm11/oz-auth.svg)
![PHP Version](https://img.shields.io/packagist/php-v/shawm11/oz-auth.svg)
[![License](https://img.shields.io/github/license/shawm11/oz-auth-php.svg)](LICENSE.md)

A PHP implementation of the 5.0.0 version of the [**Oz**](https://github.com/outmoded/oz)
web authorization protocol.

> [!IMPORTANT]
> Oz is one of those rare projects that can be considered "complete". This means
> that changes to this repository be infrequent because only the development
> dependencies may need to be updated once every few years.
>
> If there is a bug or error in the documentation, please create an
> [issue](https://github.com/shawm11/oz-auth-php/issues). The issue will
> receive a response or be resolved as soon as possible.

<!-- omit in toc -->
## Table of Contents

- [What is Oz?](#what-is-oz)
  - [Oz and OAuth 2.0](#oz-and-oauth-20)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Workflows](#workflows)
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

## What is Oz?

According to the
[Oz README](https://github.com/outmoded/oz/blob/master/README.md):

> Oz is a web authorization protocol based on industry best practices. Oz
> combines the Hawk authentication protocol with the Iron encryption protocol
> to provide a simple to use and secure solution for granting and authenticating
> third-party access to an API on behalf of a user or an application.

### Oz and OAuth 2.0

Oz is an alternative to OAuth 1.0a and OAuth 2.0 three-legged authorization. One
of the goals of Oz is to be simple to use for the most common use cases without
needing to be a web security expert while being flexible enough for less common
use cases that may need more advanced web security knowledge. Oz does this by
providing default options that are secure for the most common use cases, in
other words Oz aims to be _secure by default_.

All of the official three-legged OAuth 2.0 grant types have an equivalent Oz
workflow. Below is table showing the Oz workflow equivalents for the OAuth 2.0
grant types.

| OAuth 2.0 Grant Type                                                                               | Oz Workflow                                      |
| -------------------------------------------------------------------------------------------------- | ------------------------------------------------ |
| [Authorization Code](https://oauth.net/2/grant-types/authorization-code/)                          | RSVP                                             |
| [Implicit/PKCE](https://oauth.net/2/pkce/)                                                         | Implicit _(Not an official workflow)_            |
| [Resource Owner Password Credentials](https://datatracker.ietf.org/doc/html/rfc6749#section-1.3.3) | User Credentials _(Not an official workflow)_    |
| [Client Credentials](https://oauth.net/2/grant-types/client-credentials/)                          | [Hawk](https://github.com/shawm11/hawk-auth-php) |

## Getting Started

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

## Workflows

This package includes two workflows that are not part of the
[official Oz web authorization protocol](https://github.com/outmoded/oz). These
two new workflows are the [User Credentials Workflow](docs/user-credentials-workflow.md)
and the [Implicit Workflow](docs/implicit-workflow.md). The standard Oz workflow
that is specified by the official protocol is referred to as the
["RSVP workflow"](docs/rsvp-workflow-without-delegation.md).

## Usage Examples

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

## Documentation

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

## Security Considerations

See the [Security Considerations](https://github.com/outmoded/oz#security-considerations)
section of Oz's README.

## Related Projects

- [Hawk PHP Implementation](https://github.com/shawm11/hawk-auth-php) — Hawk is
  an HTTP authentication scheme that is an alternative to OAuth 1.0a and OAuth
  2.0 two-legged authentication.
- [Iron PHP Implementation](https://github.com/shawm11/iron-crypto-php) — _iron_
  (spelled with all lowercase), a cryptographic utility for sealing a JSON
  object into an encapsulated token. _iron_ can be considered as an alternative
  to JSON Web Tokens (JWT).

## Contributing/Development

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on coding style, Git
commit message guidelines, and other development information.

## Versioning

This project using [SemVer](http://semver.org/) for versioning. For the versions
available, see the tags on this repository.

## License

This project is open-sourced software licensed under the
[MIT license](https://opensource.org/licenses/MIT).
