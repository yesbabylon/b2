# User Instance Management System

<!-- TOC -->
* [User Instance Management System](#user-instance-management-system)
  * [Overview](#overview)
    * [Purpose of the Listener](#purpose-of-the-listener)
    * [Functionality of listener.php](#functionality-of-listenerphp)
  * [Installation](#installation)
  * [Routes explanation :](#routes-explanation-)
    * [``/create-user-instance`` :](#create-user-instance-)
      * [Purpose](#purpose)
      * [Script Functionality](#script-functionality)
      * [Usage](#usage)
        * [Example Request](#example-request)
        * [Example Response](#example-response)
        * [Considerations](#considerations)
<!-- TOC -->

## Overview

The User Instance Management System serves as a robust solution for creating and managing user instances with customized
configurations. At its core, the system revolves around a listener component, primarily embodied in the ``listener.php``
script. This listener acts as the gateway, receiving incoming requests and directing them to the appropriate handlers
for processing.

### Purpose of the Listener

The listener, encapsulated within listener.php, acts as the central hub for managing user instances. Its primary
responsibility lies in accepting requests from external sources, deciphering their intent, and orchestrating the
necessary actions to fulfill them. As the heart of the system, the listener ensures seamless communication between users
or external systems and the underlying infrastructure responsible for creating and managing user instances.

### Functionality of listener.php

The listener.php script embodies the core functionality of the User Instance Management System. It serves as the entry
point for all incoming requests, implementing the following key functionalities:

- **Request Routing:** Determines the appropriate action to take based on the requested endpoint.
- **Data Parsing:** Extracts relevant data from incoming requests for further processing.
- **Controller Invocation:** Calls the corresponding controller scripts based on the requested endpoint.
- **Error Handling:** Manages errors and exceptions gracefully, ensuring robustness and reliability.
- **HTTP Response Generation:** Generates appropriate HTTP responses to provide feedback to users or external systems.

## Installation

The service is automatically started when you have executed ths ``install.sh`` script at the root folder of b2
repository.

## Routes explanation :

### ``/create-user-instance`` :

#### Purpose

The ``create-user-instance`` endpoint facilitates the creation of user instances with customizable configurations. This
endpoint is designed to handle POST requests containing the necessary data to configure and initialize user instances.

#### Script Functionality

The ``create_user_instance`` function, defined within the PHP script associated with this endpoint, implements the logic for
creating user instances based on the provided data. The script performs the following tasks:

1. **Request Method Verification:** Ensures that the request method is POST. If not, it throws an exception with a
   status code of 405 (Method Not Allowed).
2. **Flag Generation:** Constructs flags based on specific parameters present in the request body (``$data``). Flags are
   used to customize the behavior of the user instance creation process.
3. **Data Processing:**
   Checks for specific parameters (**symbiose** and **equalpress**) in the request data and appends corresponding flags to the
   ``$flags`` string if they are set to true.
   Removes symbiose and equalpress keys from the request data to prevent duplication when writing to the ``.env`` file.
4. **Environment File Management:**
   Checks for the existence of the ``.env`` file and creates it if it does not exist.
   Clears the contents of the ``.env`` file if it already exists.
5. **Data Persistence:**
   Writes the configuration data (``$data``) to the ``.env`` file, with each key-value pair separated by an equals sign (``=``)
   and appended with a newline character (``\n``).
6. **Initialization Script Execution:**
   Executes the ``init.bash`` script with the appropriate flags generated earlier. This script likely initializes the
   user instance based on the provided configuration.
7. **HTTP Response:**
   Sends an HTTP response with a status code of 200 (OK) and a message indicating successful user instance creation.

#### Usage

To create a user instance using the ``create-user-instance`` endpoint:

Send a POST request to the endpoint with the desired configuration data in the request body.
Ensure that the required parameters (symbiose and equalpress) are correctly set to indicate the desired behavior of the
user instance.
Handle the HTTP response to confirm the success or failure of the user instance creation operation.

##### Example Request

```http request
POST /create-user-instance
Content-Type: application/json

{
  "symbiose": true,
  "equalpress": true,
  "other_param": "value"
}
```

##### Example Response

```http request
HTTP/1.1 200 OK
Content-Type: application/json

{
  "message": "User instance created successfully!"
}
```

##### Considerations

Ensure that the necessary permissions are set for file operations to successfully create and modify the ``.env`` file.
Validate and sanitize user input to prevent security vulnerabilities such as injection attacks.
Monitor the execution of the ``init.bash`` script for any errors or issues during user instance initialization.