RSVP Workflow (Without Delegation)
==================================

Table of Contents
-----------------

<!--lint disable list-item-spacing-->

- [Steps of the Workflow](#steps-of-the-workflow)
- [Pros and Cons](#pros-and-cons)
  - [Pros](#pros)
  - [Cons](#cons)
- [Usage](#usage)
- [Vocabulary](#vocabulary)
  - [Application](#application)
  - [Application Authorization](#application-authorization)
  - [Resource (User's Resource)](#resource-users-resource)
  - [Server](#server)
  - [User](#user)
  - [User Authentication](#user-authentication)

Steps of the Workflow
---------------------

The steps of the workflow assume that the default Oz options are used.

1.  (Before the workflow starts) The application is assigned Hawk credentials,
    which include an application ID and a randomly-generated key.

    - _NOTE: How this is done is not part of the Oz protocol_

1.  Application: Make a `POST /oz/app` request to the Server. In this request,
    the Application…

    - Sends its [credentials](api-reference/shared-arrays.md#app)
    - Gets an application [ticket](api-reference/shared-arrays.md#ticket) in
      return
    - _NOTE: This step allows the application to manage its own resources on the
      Server_

1.  Application: Make a `POST /oz/reissue` request to the Server. In this
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

1.  Application: Direct user to the server (possibly by redirecting). In this
    step, the Application…

    - Sends the scope, application [ticket](api-reference/shared-arrays.md#ticket)
      ID, and (possibly) the callback URL (URL back to app)
    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  User: Log in to the server

    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  Server: Display scope (sent by the application in Step 2) and prompt user to
	approve the scope

    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  User: Approve scope

    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  Server: Receive approval from user

    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  Server: Generate RSVP. In this step, the Server…

    - Gets an application ID from the request data. It is extracted from the
      [ticket](api-reference/shared-arrays.md#ticket) the application used to
      authenticate.
    - Creates a [grant](api-reference/shared-arrays.md#grant)
    - Creates an RSVP using application ID and [grant](api-reference/shared-arrays.md#grant)
      ID

1.  User: Receive RSVP from server

    - _NOTE: The method in which this is done is not part of the Oz protocol_
    - If the application will receive the RSVP from the server on behalf of the
      user (by redirecting back to the application), then this step is not
      necessary.

1.  User: Give RSVP to application

    - _NOTE: The method in which this is done is not part of the Oz protocol_

1.  Application: Make `POST /oz/rsvp` request to Server. In this request, the
    Application…

    - Sends the RSVP
    - Get the user [ticket](api-reference/shared-arrays.md#ticket) in return

1.  Application: Can now use the user [ticket](api-reference/shared-arrays.md#ticket)
    to access user resources

1.  Application: If the user [ticket](api-reference/shared-arrays.md#ticket)
    expires while the user [grant](api-reference/shared-arrays.md#grant) has not
    expired, renew the [ticket](api-reference/shared-arrays.md#ticket) by making
    a `POST /oz/reissue` request to the Server.

Pros and Cons
-------------

### Pros

- Access to the user's resources can be delegated from one application to
  another without the application passing the user's credentials to the other
  application.
- An application does not need the user's credentials to access the user's
  resources.
- The application is authenticated, so the server knows who is making the
  requests (if the application credentials were not stolen).

### Cons

- More complex compared to other authentication or authorization schemes (e.g.
  HTTP Basic with SSL, HTTP Digest), so it is more difficult to implement
- Requires redirection, so it is not very suitable for mobile apps
- The scheme assumes that the server has some sort of UI. This may not be the
  case if the server is just an HTTP REST API, which would not have any
  user-friendly visual interface.
- Requires the application to keep a secret (application credentials key) that
  is supposed to be stored in a private place where no other machine can access
  it, so this scheme is not suitable for public applications such as mobile apps
  and standalone JavaScript web apps that do not have a server back-end (also
  known as "single-page apps").

Usage
-----

The RSVP workflow is for [**application authorization**](#application-authorization),
not [**user authentication**](#user-authentication). The most common use of this
workflow is to provide a "Log in with {APP NAME HERE}" capability.

This flow is best used when...

- The server has some sort of UI that users can interact with.
- Third-parties (applications) need (possibly limited) access to a user's
  resources that are stored on and/or provided by a service (server).
- The applications can securely store their Hawk credentials in a private,
  secure location (e.g. a web app's back-end server) and can securely retrieve
  these credentials.

Vocabulary
----------

### Application

The client. It is the machine that is requesting to use the services provided by
the [server](#server).

### Application Authorization

Permission for the application to access a resource.

### Resource (User's Resource)

Data stored on the [server](#server) that belongs to or associated with the
[user](#user).

### Server

The service provider. The system or machine that stores the user's resources and
provides the services the [client](#application) uses.

### User

The human who has resources stored on the [server](#server) and is using the
[client](#application).

### User Authentication

Verifying that a user's identity, typically by asking the user to "log in" or
"sign in"

<!--lint enable list-item-spacing-->
