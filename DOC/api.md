# API documentation

The B2 host API allows the host and its instances to be managed. When the
listener is installed, it binds to `PRIVATE_IP:8000`, where `PRIVATE_IP` is
configured in `/root/b2/.env` and assigned to the host's `ens4` interface.
`PRIVATE_IP` is required by the Ubuntu 24.04 installer. With Ubuntu 26.04 it is
optional, and the listener is not installed when the variable is omitted.

## Authentication

The installers create `/root/b2/host.secrets`, so HTTP requests normally use
Basic authentication with the user `root` and the root password supplied at
installation time. For example:

```bash
curl --user root:'<root-password>' http://<private-ip>:8000/status
```

If `host.secrets` is deliberately absent or unreadable, the listener does not
enforce authentication. This fallback is intended for controlled recovery or
development situations, not for exposing an unauthenticated listener.

## Host

### GET _/status_

Get the status of the b2 host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Params

| key   | required | default | values                    | Note                                      |
|-------|:--------:|:-------:|---------------------------|-------------------------------------------|
| scope |  false   |         | instant \| state \| config | Return only the selected status category. |

#### Response

Without `scope`, the response contains `instant`, `state`, `config` and the
legacy top-level `type`. The same value is also available as `config.type`.
With `scope=instant`, only `instant` is returned.

### POST _/ip_

Set the public interface ip address.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "ip_address":"192.168.1.15",
  "subnet":"24"
}
```

| key        | required | default | values | Note                          |
|------------|:--------:|:-------:|--------|-------------------------------|
| ip_address |   true   |         |        | Must be a valid ipv4 address. |
| subnet     |   true   |         |        | Must be a valid ipv4 subnet.  |

### POST _/reboot_

Reboot the b2 host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{}
```

### GET _/instances_

Get the list of instances present on the b2 host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Params

```
/instances?with_deleted=true
```

| key          | required | default | values | Note                                                        |
|--------------|:--------:|:-------:|--------|-------------------------------------------------------------|
| with_deleted |  false   |  false  |        | When set to true, includes instances that have been deleted |

### GET _/images_

Return the Docker image references installed on the host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

### GET _/stacks_

Return the Docker image references used by each instance stack.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

## Instance

### POST _/instance/create_

Create a new instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "USERNAME":"equal.local",
  "PASSWORD":"thepassword1234",
  "INSTANCE_TYPE":"equal"
}
```

| key            | required |  default   | values                                              | Note                                                                                                      |
|----------------|:--------:|:----------:|-----------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| USERNAME       |   true   |            |                                                     | Name of the instance. Must be a valid domain name and cannot exceed 32 characters (Linux username limit). |
| PASSWORD       |   true   |            |                                                     | Password for all accesses (Must be at least 8 characters long).                                           |
| INSTANCE_TYPE  |  false   |   equal    | equal \| wordpress \| equalpress \| symbiose \| fmt \| mkdocs | Defines which instance stack template to use.                                                    |
| CIPHER_KEY     |  false   |            |                                                     | The default value is a 32 characters long randomly generated key.                                         |
| HTTPS_REDIRECT |  false   | noredirect | redirect \| noredirect                              | Noredirect is for HTTP and redirect for HTTPS.                                                            |
| MEM_LIMIT      |  false   |   1000M    |                                                     | Memory limit of the equal_svr container.                                                                  |
| CPU_LIMIT      |  false   |      1     |                                                     | CPU limit applied to the application container.                                                           |
| EQ_MEM_FREE_LIMIT | false |    256M    |                                                     | Memory reserve passed to eQual-compatible templates.                                                      |
| INIT            |  false  |    false   | true \| false                                      | Start the selected template's `init.sh` in the background after creation.                                 |


fmt additional options :

| key                 | required (fmt) | default | values           | Note                                                                                                |
|---------------------|:--------------:|:-------:|------------------|-----------------------------------------------------------------------------------------------------|
| INSTANCE_SUBTYPE    |     false      | agency  | agency \| global | The type of FMT instance.                                                                           |
| INSTANCE_UUID       |     false      |         |                  | The UUID of the new agency instance. (required if INSTANCE_SUBTYPE is agency)                       |
| GLOBAL_ACCESS_TOKEN |     false      |         |                  | The access token of the new agency instance to global API. (required if INSTANCE_SUBTYPE is agency) |
| GLOBAL_URL          |     false      |         |                  | The url of the global instance API. (required if INSTANCE_SUBTYPE is agency)                        |

FMT credentials must not be included in the API payload, including as Base64-encoded data. They must be installed manually on the B2 host in `/root/b2/secrets/fmt.json`; `conf/instance/fmt/prepare.php` reads this optional file during initialization. See [Instance secrets](../README.md#instance-secrets) for its format, permissions, validation behavior, and repository rules.



#### Init script safety behavior (`conf/instance/<type>/init.sh`)

Each type has its own `init.sh` stored next to its compose/config/script assets (no `template/` subdirectory).

- Refuses to run when `USERNAME` is missing.
- Refuses to run when `/home/$USERNAME/.initialized` exists, unless `--force` is provided.
- Refuses to run when `/home/$USERNAME/www` already contains data, unless `--force` is provided.
- Uses a lockfile (`/home/$USERNAME/.init.lock`) to prevent concurrent init runs.
- The lock is written on start and always deleted on exit/interruption via `trap` (`EXIT`, `INT`, `TERM`).


### POST _/instance/delete_

Delete an existing instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{"instance":"equal.local"}
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |

### GET _/instance/status_

Get the status of an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Params

```
/instance/status?instance=equal.local
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |
| scope    |  false   |         | instant \| state \| config | Return only the selected status category. |

#### Response

Without `scope`, the response contains `instant`, `state` and `config`.
`config.php.build_date` is an ISO 8601 string or `null` when PHP information
cannot be read from the container. `config.framework` is `equal`, `wordpress`
or `null` for an unsupported stack type. `config.version` is the installed
framework version when it can be determined. For eQual, it is read from
`/var/www/html/VERSION`. Source paths use their paths inside the application
container. `repository` is the URL of the Git remote `origin`, without embedded
HTTP credentials. Repository and branch values are `null` when the source
directory is not the root of a Git repository. Framework and source
information is read from the targeted application container.

```json
{
  "instant": {
    "dsk_use": "2.07%",
    "cpu_use": "0.01%",
    "ram_use": "5.46%",
    "total_proc": "12"
  },
  "state": {
    "up": true,
    "pid": "12345",
    "maintenance": false,
    "containers": ["example.org", "sql.example.org"],
    "net": "89MB / 19MB",
    "dsk": "84.8MB / 4.1kB"
  },
  "config": {
    "id": "76197eca3602",
    "image": "b2-equal:2.1-r1",
    "type": "equal",
    "framework": "equal",
    "version": "2.0.1",
    "sources": [
      {
        "path": "/var/www/html",
        "repository": "https://github.com/equalframework/equal.git",
        "branch": "2.0.1"
      },
      {
        "path": "/var/www/html/packages",
        "repository": "https://github.com/example/packages.git",
        "branch": "main"
      }
    ],
    "php": {
      "version": "8.5.12",
      "build_date": "2026-07-30T15:39:15+00:00"
    },
    "mem_limit": 1000,
    "cpu_limit": 1
  }
}
```

### POST _/instance/backup_

Create a backup of an instance.

The related controller will create 3 files: `backup.sql.gz`, `config.tar`, `filestore.tar.gz`; and place them in a tarball in the `export` folder of the corresponding instance:

```
/home/$instance/export/{$instance}_$timestamp.tar
```

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "encrypt":true
}
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |
| encrypt  |  false   |  true   |        | If true the backup will be encrypted gpg.       |

**encrypt**: not mandatory, default value `true`

### Docker stack backup workflow

Docker stack backups are separate from instance data backups. They preserve the
Docker images used by an instance and follow this workflow:

1. `/instance/backup-stack` creates the archive in the instance's `export` directory.
2. `/instance/export-backup-stack` sends it to the configured backup host.
3. `/instance/import-backup-stack` retrieves it into the target instance's `import`
   directory.
4. `/instance/integrate-backup-stack` loads only image tags that are not already
   returned by `get_images()` on the target host.

A stack backup is named:

```text
{instance}_{backup_id}_stack.tar.gz
```

`backup_id` contains exactly 13 digits: the date in `YYYYMMDD` format followed by
the zero-padded number of seconds elapsed since midnight (`SSSSS`).

The compressed outer archive contains one Docker archive per image directly at its
root and does not contain a `stack.json` file. Each filename is the image reference
encoded with `rawurlencode()`:

```text
mysql%3A5.7.44.tar
phpmyadmin%2Fphpmyadmin%3A5.2.1.tar
equalframework%2Fequal%3A2.0.0.tar
```

Each inner `.tar` is produced independently by `docker save`, allowing missing
images to be extracted and loaded without passing already installed image tags to
Docker.

### POST _/instance/backup-stack_

Create a compressed backup containing the Docker images used by an instance stack.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local"
}
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |

The archive is created in `/home/{instance}/export` and its filename is returned in
`result`.

### POST _/instance/export-backup_

Export the backup of an instance to the configured backup host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"20241210091621"
}
```



| key       | required | default | values | Note                                                         |
| --------- | :------: | :-----: | ------ | ------------------------------------------------------------ |
| instance  |   true   |         |        | Must be a valid instance installed on the host.              |
| backup_id |   true   |         |        | Must be a valid backup in export directory, i.e.  `{instance_name}_{backup_id}.tar`<br/>or<br/>`{instance_name}_{backup_id}.tar.gpg` |

**Notes**:

The `BACKUP_HOST` env var needs to be correctly set for the export to work.

### POST _/instance/export-backup-stack_

Export a stack backup from the instance's `export` directory to the configured
backup host. The local archive is deleted after a successful transfer.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"2026101300001"
}
```

| key       | required | default | values | Note                                              |
|-----------|:--------:|:-------:|--------|---------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host.   |
| backup_id |   true   |         |        | Must contain exactly 13 digits.                   |

The `BACKUP_HOST` environment variable must be configured.

  

### POST _/instance/import-backup_

Import a backup from the configured backup host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"20241210091621"
}
```

| key       | required | default | values | Note                                                |
|-----------|:--------:|:-------:|--------|-----------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host.     |
| backup_id |   true   |         |        | Must be a valid backup existing on the backup host. |

**Notes**:

The `BACKUP_HOST` env var needs to be correctly set for the import to work.

### POST _/instance/import-backup-stack_

Import a stack backup from the configured backup host into the instance's `import`
directory. Importing the archive does not load its images into Docker.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"2026101300001"
}
```

| key       | required | default | values | Note                                              |
|-----------|:--------:|:-------:|--------|---------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host.   |
| backup_id |   true   |         |        | Must contain exactly 13 digits.                   |

The `BACKUP_HOST` environment variable must be configured.

### POST _/instance/integrate-backup-stack_

Add the missing Docker images from a stack backup previously imported into the
instance's `import` directory. The image reference is decoded from each inner `.tar`
filename and references without an explicit tag are normalized to `:latest`.

Image references already returned by `get_images()` are skipped and their inner
archives are never sent to Docker. Each missing archive is streamed independently to
`docker image load`, then the resulting tags are verified with `get_images()`.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"2026101300001"
}
```

| key       | required | default | values | Note                                                   |
|-----------|:--------:|:-------:|--------|--------------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host.        |
| backup_id |   true   |         |        | Must contain exactly 13 digits and identify an imported stack backup. |

Example response:

```json
{
  "result": "backup_stack_integrated",
  "integrated": [
    "equalframework/equal:2.0.0"
  ],
  "skipped": [
    "mysql:5.7.44",
    "phpmyadmin/phpmyadmin:5.2.1"
  ]
}
```

### GET _/instance/backups_

Get the list of backups of an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Params

```
/instance/backups?instance=equal.local
```

| key       | required | default | values | Note                                            |
|-----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host. |

### POST _/instance/restore_

Restore an instance state with a backup.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{
  "instance":"equal.local",
  "backup_id":"20241210091621",
  "passphrase":"thepassword1234"
}
```

| key        |  required  | default | values | Note                                                                    |
|------------|:----------:|:-------:|--------|-------------------------------------------------------------------------|
| instance   |    true    |         |        | Must be a valid instance installed on the host.                         |
| backup_id  |    true    |         |        | Must be a valid backup existing in either export or import directories. |
| passphrase | true/false |         |        | Only required if the backup is encrypted.                               |

### POST _/instance/start_

Start the Docker Compose services of an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{"instance":"equal.local"}
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |

### POST _/instance/stop_

Stop the Docker Compose services of an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{"instance":"equal.local"}
```

| key      | required | default | values | Note                                            |
|----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance |   true   |         |        | Must be a valid instance installed on the host. |

### POST _/instance/enable-maintenance_

Enable maintenance mode for an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{"instance":"equal.local"}
```

| key        |  required  | default | values | Note                                            |
|------------|:----------:|:-------:|--------|-------------------------------------------------|
| instance   |    true    |         |        | Must be a valid instance installed on the host. |

### POST _/instance/disable-maintenance_

Disable maintenance mode for an instance.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

#### Body

```json
{"instance":"equal.local"}
```

| key        |  required  | default | values | Note                                            |
|------------|:----------:|:-------:|--------|-------------------------------------------------|
| instance   |    true    |         |        | Must be a valid instance installed on the host. |
