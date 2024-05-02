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
        * [Request parameters](#request-parameters)
        * [Example Request](#example-request)
        * [Example Response](#example-response)
        * [Considerations](#considerations)
    * [``/instance/delete`` :](#instancedelete-)
      * [Purpose](#purpose-1)
      * [Script process task](#script-process-task-1)
      * [Usage](#usage-1)
        * [Request parameters](#request-parameters-1)
        * [Example Request](#example-request-1)
        * [Example Response](#example-response-1)
        * [Considerations](#considerations-1)
    * [``/instance/info`` :](#instanceinfo-)
      * [Purpose](#purpose-2)
      * [Script process task](#script-process-task-2)
      * [Usage](#usage-2)
        * [Request parameters](#request-parameters-2)
        * [Example Request](#example-request-2)
        * [Example Response](#example-response-2)
        * [Considerations](#considerations-2)
    * [``/instance/restore`` :](#instancerestore-)
    * [``/instances`` :](#instances-)
      * [Purpose](#purpose-3)
      * [Script process task](#script-process-task-3)
      * [Usage](#usage-3)
        * [Example Request](#example-request-3)
        * [Example Response](#example-response-3)
    * [``/reboot`` :](#reboot-)
      * [Purpose](#purpose-4)
      * [Script process task](#script-process-task-4)
      * [Usage](#usage-4)
        * [Request parameters](#request-parameters-3)
        * [Example Request](#example-request-4)
        * [Example Response](#example-response-4)
    * [``/info`` :](#info-)
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

The ``listener.php`` script embodies the core functionality of the User Instance Management System. It serves as the entry
point for all incoming requests, implementing the following key functionalities:

- **Request Routing:** Determines the appropriate action to take based on the requested endpoint.
- **Data Parsing:** Extracts relevant data from incoming requests for further processing.
- **Controller Invocation:** Calls the corresponding controller scripts based on the requested endpoint.
- **Error Handling:** Manages errors and exceptions gracefully, ensuring robustness and reliability.
- **HTTP Response Generation:** Generates appropriate HTTP responses to provide feedback to users or external systems.

## Installation

The service is automatically started when you have executed the ``install.sh`` script at the root folder of b2
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
   If they are, corresponding flags (``-s`` for **symbiose** and ``-w`` for **equalpress**) are appended to
   the ``$flags``
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

##### Request parameters

| Parameter      | Required | Description                                                                                                                            |
|----------------|:--------:|----------------------------------------------------------------------------------------------------------------------------------------|
| symbiose       |  false   | Flag for installing Symbiose                                                                                                           |
| equalpress     |  false   | Flag for installing eQualPress                                                                                                         |
| USERNAME       |   true   | - Customer directory created in /home </br> - Linux user created with the same name</br> - Docker container created with the same name |
| APP_USERNAME   |   true   | Applications credentials used for eQual, database and eQualPress                                                                       |
| APP_PASSWORD   |   true   | Applications credentials used for eQual, database and eQualPress                                                                       |
| CIPHER_KEY     |   true   | CIPHER KEY for eQual config encryption safety                                                                                          |
| HTTPS_REDIRECT |   true   | Nginx configuration                                                                                                                    |
| WP_VERSION     |  false   | Wordpress version                                                                                                                      |
| WP_EMAIL       |  false   | Wordpress admin email                                                                                                                  |
| WP_TITLE       |  false   | WordPress site title                                                                                                                   |

##### Example Request

```http request
POST /instance/create
Content-Type: application/json

{
  "symbiose": true,
  "equalpress": true,
  "USERNAME": "test.yb.run"
  "APP_USERNAME": "root"
  "APP_PASSWORD": "test"
  "CIPHER_KEY": "xxxxxxxxxxxxxx"
  "HTTPS_REDIRECT": "noredirect"
  "WP_VERSION": "6.4"
  "WP_EMAIL": "root@equal.local"
  "WP_TITLE": "eQualpress"
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

### ``/instance/delete`` :

#### Purpose

The `instance/delete` endpoint facilitates the deletion of user instances and associated resources.
This endpoint is designed
to handle POST requests containing the necessary data to identify and delete the specified user instance.

#### Script process task

The `instance_delete` function, defined within the PHP script associated with this endpoint, implements the logic for
deleting user instances and their associated resources based on the provided data. The script performs the following
tasks:

1. **Validate Instance Identifier:** Checks if the `instance` key exists in the input data array, is a non-empty string,
   and is properly formatted. If not, it returns a status code `400` indicating a bad request.
2. **Change Directory:** Changes the current working directory to the directory of the specified user instance located
   at `/home/$instance`.
3. **Stop and Remove Docker Containers:** Executes the `docker-compose down -v` command to stop and remove any Docker
   containers associated with the user instance.
4. **Rename Instance Directory:** Renames the directory of the specified user instance to `$instance_deleted` to mark it
   for deletion.
5. **Remove Contents of Deleted Directory:** Removes all files and directories within the renamed
   directory (`/home/$instance_deleted`) while keeping the directory itself.
6. **Delete Linux User:** Deletes the Linux user associated with the user instance.
7. **Return Response:** Returns a response array with a status code (`201` indicating successful deletion) and an empty
   message.

#### Usage

To delete a user instance using the `instance/delete` endpoint:

Send a POST request to the endpoint with the identifier of the instance to be deleted (`instance`) in the request body.
Handle the HTTP response to confirm the success or failure of the user instance deletion operation.

##### Request parameters

| Parameter | Required | Description                               |
|-----------|:--------:|-------------------------------------------|
| instance  |   true   | Identifier of the user instance to delete |

##### Example Request

```http request
POST /instance/delete
Content-Type: application/json

{
  "instance": "test.yb.run"
}
```

##### Example Response

```http request
HTTP/1.1 201 OK
Content-Type: application/json
```

##### Considerations

Ensure that the specified instance identifier (`instance`) corresponds to a valid user instance.
Handle errors gracefully and provide appropriate feedback to the user in case of failures during the deletion process.
Monitor the execution of the deletion script for any unexpected behaviors or issues.

Here's the documentation for the `instance_info` script:

### ``/instance/info`` :

#### Purpose

The `instance/info` endpoint facilitates the retrieval of information about a specified Docker instance.
This endpoint is designed to handle POST requests containing the necessary data to identify the instance for which
information is to be retrieved.

#### Script process task

The `instance_info` function, defined within the PHP script associated with this endpoint, implements the logic for
retrieving information about a Docker instance based on the provided data.
The script performs the following tasks:

1. **Retrieve Docker Instance Information:** Executes the `docker stats` command with the specified instance name to
   retrieve information about the Docker instance. The command is executed with the `--no-stream` flag to ensure that
   only a single snapshot of information is retrieved, and the `--format "{{ json . }}"` flag to format the output as
   JSON.
2. **Parse and Validate JSON Output:** Parses the JSON output obtained from the `docker stats` command. If the JSON
   parsing fails (resulting in a `null` value), it sets the status code to `404` to indicate that the specified instance
   was not found.
3. **Format and Return Response:** If the JSON parsing is successful, formats the retrieved information into a readable
   JSON string with pretty-printing and returns it as the message in the response array along with a status code `201`
   indicating successful retrieval.

#### Usage

To retrieve information about a Docker instance using the `instance/info` endpoint:

Send a POST request to the endpoint with the identifier of the instance for which information is to be
retrieved (`instance`) in the request body.
Handle the HTTP response to access the information about the Docker instance.

##### Request parameters

| Parameter | Required | Description                       |
|-----------|:--------:|-----------------------------------|
| instance  |   true   | Identifier of the Docker instance |

##### Example Request

```http request
POST /instance/info
Content-Type: application/json

{
  "instance": "test.yb.run"
}
```

##### Example Response

[//] # Todo: Verify that the result is correct

```http request
HTTP/1.1 201 OK
Content-Type: application/json

{
  "name": "test.yb.run",
  "cpu_usage": "10%",
  "memory_usage": "500MB",
  "network_io": {
    "received": "1GB",
    "transmitted": "500MB"
  }
}
```

##### Considerations

Ensure that the specified instance identifier (`instance`) corresponds to a valid Docker instance.

### ``/instance/restore`` :

In progress...

### ``/instances`` :

#### Purpose

The `/instances` endpoint provides a list of active user instances available on the system.
This endpoint is designed to handle POST requests with an empty JSON body and returns a JSON array containing the names
of active user instances.

#### Script process task

The `instances` function, defined within the PHP script associated with this endpoint, implements the logic for
retrieving the list of active user instances. The script performs the following tasks:

1. **Retrieve Directory Listing:** Uses the `scandir` function to retrieve a list of directories in the `/home`
   directory, which typically represent user instances.
2. **Handle Error Conditions:** Checks if the directory listing operation was successful. If not, it sets the status
   code to `500` indicating an internal server error.
3. **Filter Active Instances:** Removes entries corresponding to system directories (
   e.g., `'.'`, `'..'`, `'ubuntu'`, `'docker'`) and instances marked for deletion (`'_deleted'` suffix).
4. **Return Response:** Returns a response array with a status code (`201` indicating successful operation) and a
   JSON-encoded array containing the names of active user instances.

#### Usage

To retrieve the list of active user instances using the `/instances` endpoint:

Send a POST request to the endpoint with an empty JSON body (`{}`).
Handle the HTTP response containing the JSON array of active user instance names.

##### Example Request

```http request
POST /instances
Content-Type: application/json

{}
```

##### Example Response

```http request
HTTP/1.1 201 OK
Content-Type: application/json

[
  "test1.yb.run",
  "test2.yb.run",
  "test3.yb.run"
]
```

### ``/reboot`` :

#### Purpose

The `reboot` endpoint initiates a system reboot.
This endpoint is designed to handle POST requests triggering a reboot operation on the system.

#### Script process task

The `reboot` function, defined within the PHP script associated with this endpoint, initiates the system reboot based on
the provided data. The script performs the following task:

1. **Reboot System:** Executes a command to reboot the system in detached mode, allowing the function to return
   immediately.
    - Uses the `exec` function to run the command `nohup sh -c "sleep 5 && reboot" > /dev/null 2>&1 &`, which initiates
      a system reboot after a delay of 5 seconds.

#### Usage

To initiate a system reboot using the `reboot` endpoint:

Send a POST request to the endpoint.
Handle the HTTP response to confirm the success of the reboot operation.

##### Request parameters

No request parameters are required for this endpoint.

##### Example Request

```http request
POST /reboot
Content-Type: application/json

{}
```

##### Example Response

```http request
HTTP/1.1 201 OK
Content-Type: application/json
```

### ``/info`` :

In progress...