# API documentation

The b2 host's api allows to manage the host and its instances.

## Host

### GET _/status_

Get the status of the b2 host.

#### Request Headers

| key          | value            |
|--------------|------------------|
| Content-Type | application/json |

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
  "APP_USERNAME":"root",
  "APP_PASSWORD":"thepassword1234",
  "symbiose": true,
  "equalpress": false
}
```

| key            | required |     default      | values                 | Note                                                                    |
|----------------|:--------:|:----------------:|------------------------|-------------------------------------------------------------------------|
| USERNAME       |   true   |                  |                        | Name of the instance. Must be a valid domain name.                      |
| APP_USERNAME   |   true   |                  |                        | User name for eQual and db accesses.                                    |
| APP_PASSWORD   |   true   |                  |                        | Password for eQual and db accesses. Must be at least 8 characters long. |
| symbiose       |  false   |      false       |                        | If true it installs Symbiose.                                           |
| equalpress     |  false   |      false       |                        | If true it installs eQualPress.                                         |
| CIPHER_KEY     |  false   |                  |                        | The default value is a 32 characters long randomly generated key.       |
| HTTPS_REDIRECT |  false   |    noredirect    | redirect \| noredirect | Noredirect is for HTTP and redirect for HTTPS.                          |
| WP_VERSION     |  false   |       6.4        |                        | Version of WordPress installed. Only required if equalpress is true.    |
| WP_EMAIL       |  false   | root@equal.local |                        | Email address for WordPress. Only required if equalpress is true.       |
| WP_TITLE       |  false   |    eQualPress    |                        | Title of the WordPress app. Only required if equalpress is true.        |
| MEM_LIMIT      |  false   |      1000M       |                        | Memory limit of the equal_svr container.                                |

**Notes**: 

If the _APP_USERNAME_ given isn't `root`, then the eQual login will be _APP_USERNAME_@_USERNAME_.

For instance if _APP_USERNAME_ is `johndoe` and _USERNAME_ is `equal.local` then the eQual login will be `johndoe@equal.local`.

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

### POST _/instance/backup_

Create a backup of an instance.

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

| key       | required | default | values | Note                                            |
|-----------|:--------:|:-------:|--------|-------------------------------------------------|
| instance  |   true   |         |        | Must be a valid instance installed on the host. |
| backup_id |   true   |         |        | Must be a valid backup in export directory.     |

**Notes**:

The `BACKUP_HOST_URL` and `BACKUP_HOST_FTP` env configurations need to be correctly set for the export to work.

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

The `BACKUP_HOST_URL` and `BACKUP_HOST_FTP` env configurations need to be correctly set for the export to work.

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
