User Credentials Workflow
=========================

**NOTICE**: This workflow is not part of the official Oz protocol.

Table of Contents
-----------------

<!--lint disable list-item-spacing-->

- [Steps of the Workflow](#steps-of-the-workflow)
- [Pros and Cons](#pros-and-cons)
  - [Pros](#pros)
  - [Cons](#cons)
- [Usage](#usage)
- [Additional Security Considerations](#additional-security-considerations)
  - [User Credentials Transmission](#user-credentials-transmission)
- [Vocabulary](#vocabulary)
  - [Application](#application)
  - [Resource (User's Resource)](#resource-users-resource)
  - [Server](#server)
  - [User](#user)
  - [User Credentials](#user-credentials)

Steps of the Workflow
---------------------

1. (Before the workflow starts) The application is assigned Hawk credentials,
    which include an application ID and a randomly-generated key.

    - _NOTE: How this is done is not part of the Oz protocol_

1. Application: Make a `POST /oz/app` request to the Server. In this request,
    the Application…

    - Sends its [credentials](api-reference/shared-arrays.md#app)
    - Gets an application [ticket](api-reference/shared-arrays.md#ticket) in
      return
    - _NOTE: This step allows the application to manage its own resources on the
      Server_

1. Application: Make a `POST /oz/reissue` request to the Server. In this
    request, the Application…

    - Sends the scope array (optional) and the application [ticket](api-reference/shared-arrays.md#ticket)
      ID (as a Hawk-authenticated requested using the application ticket)
    - Gets a new application [ticket](api-reference/shared-arrays.md#ticket) in
      return
    - NOTES
      - This keeps the application [ticket](api-reference/shared-arrays.md#ticket)
        fresh, with a new expiration date.
      - This may not be necessary, especially if the application just obtained
        the ticket and it has not expired yet.

1. Application: Ask for the User's credentials

    - _NOTE: How this is done is not part of the Oz protocol_

1. User: Enter credentials

    - This is typically done by the user simply typing in the credentials into
      the Application.
    - _NOTE: How this is done is not part of the Oz protocol_

1. Application: Receive the User's credentials and make a `POST /oz/user`
    request to Server. In the request, the Application…

    - Sends the application [ticket](api-reference/shared-arrays.md#ticket) ID
      (as an authenticated request using the application ticket) and the User
      credentials
    - Gets user [ticket](api-reference/shared-arrays.md#ticket) in return
    - _NOTE: An application should NEVER store a user's credentials. When an
      application obtains the user ticket, it should immediately discard the
      user credentials._

1. Application: Can now use the user [ticket](api-reference/shared-arrays.md#ticket)
    to access the User's resources

1. Application: If the User [ticket](api-reference/shared-arrays.md#ticket)
    expires while the user [grant](api-reference/shared-arrays.md#grant) has not
    expired, renew the [ticket](api-reference/shared-arrays.md#ticket) by making
    a `POST /oz/reissue` request to the Server.

Pros and Cons
-------------

### Pros

- Less complex than the RSVP workflow
- To the user, it looks identical to traditional user authentication systems,
  which typically use cookies. So this flow can be a stateless replacement for
  traditional cookie-based user authentication.
- No redirection
- The application is authenticated, so the server knows who is making the
  requests (if the application credentials were not stolen).
- The application does not need to store the user's credentials, but it does
  need to temporarily handle the user's credentials before giving them to the
  server.

### Cons

- Relies heavily (if not solely) on SSL/TLS for transmission of user credentials
- The application directly receives the user's credentials and handles them
  before giving them to the server. This may be an issue if the application
  cannot be trusted.
- Requires the application to keep a secret (application credentials key) that
  is supposed to be stored in a private place where no other machine can access
  it, so this scheme is not suitable for public applications such as mobile apps
  and standalone JavaScript web apps that do not have a server back-end (also
  known as "single-page apps").

Usage
-----

This flow is best used when...

- The applications using this workflow can be trusted with user credentials
- Creating an official web app (with a server back-end)
- Need simple user authentication along with application authentication
- Need a stateless solution that provides an authentication flow that most users
  are familiar with (traditional cookie-based user authentication)
- Create the user log-in function for the server that the RSVP workflow would
  use

Additional Security Considerations
----------------------------------

The security considerations for the User Credentials Workflow are the same as
the security considerations for the RSVP Workflow, except that there are
additional security considerations for user credentials, and the security
considerations regarding the redirect URI do not apply because there is no
redirection in the User Credentials Workflow.

### User Credentials Transmission

Oz does not provide any mechanism for obtaining or transmitting the user
credentials for the application. Any mechanism the application used to obtain
the user credentials must ensure that these transmissions are protected using
transport-layer mechanisms, such as TLS. However, this is how it is with
traditional cookie-based user authentication.

Vocabulary
----------

### Application

The client. It is the machine that is requesting to use the services provided by
the server.

### Resource (User's Resource)

Data stored on the server that belongs to the user.

### Server

The service provider. The system or machine that stores the user's resources and
provides the services the client uses.

### User

The human who has resources stored on the server and is using the client.

### User Credentials

The set of credentials that are associated with the user that the user uses to
authenticate and identify as him/herself. Typically, it is the user's username
(or email, ID, etc.) and password.

<!--lint enable list-item-spacing-->
