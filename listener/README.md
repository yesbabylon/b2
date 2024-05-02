# User Instance Management System

<!-- TOC -->
* [User Instance Management System](#user-instance-management-system)
  * [Overview](#overview)
    * [Purpose of the Listener](#purpose-of-the-listener)
    * [Functionality of listener.php](#functionality-of-listenerphp)
  * [Installation](#installation)
  * [Routes explanation :](#routes-explanation-)
    * [``/instance/create`` :](#instancecreate-)
      * [Purpose](#purpose)
      * [Script process task](#script-process-task)
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

### ``/instance/create`` :

#### Purpose

The ``instance/create`` endpoint facilitates the creation of user instances with customizable configurations.
This endpoint is designed
to handle POST requests containing the necessary data to configure and initialize user instances.

#### Script process task

The ``instance_create`` function, defined within the PHP script associated with this endpoint, implements the logic for
creating user instances based on the provided data. The script performs the following tasks:

1. **Set Default Flags:** Initializes a variable ``$flags`` with an empty string.
2. **Check Request Data:** Checks if certain keys (``symbiose`` and ``equalpress``) exist in the input data array and if
   they are set to true.
   If they are, corresponding flags (``-s`` for **symbiose** and ``-w`` for **equalpress**) are appended to the ``$flags``
   string.
3. **Remove Specific Keys:** Removes the keys symbiose and equalpress from the input data array.
4. **Create or Clear ``.env`` File:** Checks if a ``.env`` file exists at a specified path.
   If it doesn't exist, it creates one using ``touch()`` and sets its permissions.
   If it does exist, it clears its contents using ``file_put_contents()``.
5. **Write Data to ``.env`` File:** Writes key-value pairs from the input data array to the ``.env`` file, each on a new
   line.
6. **Execute ``init.bash`` Script:** Executes a Bash script named ``init.bash`` located at a specific path (
   ``/root/b2/equal/init.bash``) with the flags obtained earlier.
7. **Return Response:** Returns a response array with a status code (**201 indicating successful creation**) and an
   empty message.

#### Usage

To create a user instance using the ``instance/create`` endpoint:

Send a POST request to the endpoint with the desired configuration data in the request body.
Ensure that the required parameters (``symbiose`` and ``equalpress``)
are correctly set to indicate the desired behavior of the user instance.
Handle the HTTP response to confirm the success or failure of the user instance creation operation.

##### Example Request

```http request
POST /instance/create
Content-Type: application/json

{
  "symbiose": true, // Optional key for Symbiose installation
  "equalpress": true, // Optional key for eQualPress installation
  
  # Customer directoy created in /home
  # Linux user created with the same name
  # Docker container created with the same name
  USERNAME: 'test.yb.run'
  
  # Applications credentials used for eQual, database and eQualPress
  APP_USERNAME: 'root'
  APP_PASSWORD: 'test'
  
  # CIPHER KEY for eQual config encryption safety
  CIPHER_KEY: 'xxxxxxxxxxxxxx'
  
  # Nginx configuration
  HTTPS_REDIRECT: 'noredirect'
  
  # Below are the variables that are used for an eQualPress installation
  # Wordpress version
  WP_VERSION: '6.4'
  
  # Wordpress admin email
  WP_EMAIL: 'root@equal.local'
  
  # WordPress site title
  WP_TITLE: 'eQualpress'
}
```

##### Example Response

```http request
HTTP/1.1 201 OK
Content-Type: application/json
```

##### Considerations

Ensure that the necessary permissions are set for file operations to successfully create and modify the ``.env`` file.
Validate and sanitize user input to prevent security vulnerabilities such as injection attacks.
Monitor the execution of the ``init.bash`` script for any errors or issues during user instance initialization.
