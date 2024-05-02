Implicit Workflow
=================

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
- [Vocabulary](#vocabulary)

Steps of the Workflow
---------------------

1. Application: Ask for the User's credentials

    - _NOTE: How this is done is not part of the Oz protocol_

1. User: Enter credentials

    - This is typically done by the user simply typing in the credentials into
      the Application.
    - _NOTE: How this is done is not part of the Oz protocol_

1. Application: Receive the User's credentials and make a `POST /oz/user`
    request to Server. In the request, the Application…

    - Sends the application [ticket](api-reference/shared-arrays.md#ticket)
      ID (as an authenticated request using the application ticket) and the User
      credentials
    - Gets user [ticket](api-reference/shared-arrays.md#ticket) in return
    - _NOTE: An application should NEVER store a user's credentials. When an
      application obtains the user ticket, it should immediately discard the
      user credentials._

1. Application: Can now use the user [ticket](api-reference/shared-arrays.md#ticket)
    to access the User's resources

1. Application: If the User [ticket](api-reference/shared-arrays#ticket)
    expires while the user [grant](api-reference/shared-arrays.md#grant) has not
    expired, renew the [ticket](api-reference/shared-arrays#ticket) by making a
    `POST /oz/reissue` request to the Server.

Pros and Cons
-------------

### Pros

- Easiest to implement out of all of the workflows
- To the user, it looks identical to traditional user authentication systems,
  which typically use cookies.
- No redirection
- The application does not need to store the user's credentials, but it does
  need to temporarily handle the user's credentials before giving them to the
  server.
- Because there are no application credentials, this workflow is suitable for
  publicly exposed applications such as mobile apps and standalone JavaScript
  web apps that do not have a server back-end.

### Cons

- Relies solely on SSL/TLS for transmission of user credentials
- The application directly receives the user's credentials and handles them
  before giving them to the server. This may be an issue if an application
  cannot be trusted.
- There is no application authentication, so the server does not know who is
  making the requests

Usage
-----

This flow is best used when...

- The applications using this workflow can be trusted with user credentials
- The application cannot keep a secret (e.g. mobile apps, single-page standalone
  JavaScript web apps)
- Need simple user authentication
- Need a solution that provides a flow that most users are familiar with

Additional Security Considerations
----------------------------------

Same as the [User Credentials Workflow](user-credentials-workflow.md). In
short, user credentials must be sent by the client to the server using SSL/TLS.

Vocabulary
----------

Same as the [User Credentials Workflow](user-credentials-workflow.md).

<!--lint enable list-item-spacing-->
