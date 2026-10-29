# CLI memo (`run.sh`) — available routes/actions and expected parameters

This memo summarizes the actions you can run with the local CLI (`./run.sh`), including expected parameters and value types.

## Command format

```bash
./run.sh --route=/<route> [--param=value ...]
```

Example:

```bash
./run.sh --route=/instance/status --instance=example.com
```

## Parsing rules (important)

- `--route` is required.
- Every `--key=value` argument is passed as payload data.
- The exact values `true` and `false` are converted to booleans.
- A flag without value (example: `--flag`) becomes `true`.

## Host actions

### `/status`
- **Purpose**: get global host status.
- **Parameters**:
  - `scope` *(string, optional)*: `instant`, `state`, or `config`.
- **Example**:
  ```bash
  ./run.sh --route=/status --scope=state
  ```

### `/instances`
- **Purpose**: list instances.
- **Parameters**:
  - `with_deleted` *(string/bool, optional)*: accepted values include `1`, `0`, `yes`, `no` (and `true`/`false` in HTTP/API usage).
- **Example**:
  ```bash
  ./run.sh --route=/instances --with_deleted=1
  ```

### `/images`

- **Purpose**: list Docker image references installed on the host.
- **Parameters**: none.
- **Example**:
  ```bash
  ./run.sh --route=/images
  ```

### `/stacks`

- **Purpose**: list the Docker image references used by each instance stack.
- **Parameters**: none.
- **Example**:
  ```bash
  ./run.sh --route=/stacks
  ```

### `/ip`
- **Purpose**: add the public IP on `veth0`.
- **Parameters**:
  - `ip_address` *(string, required)*: valid IPv4 address.
  - `subnet` *(string/int, required)*: IPv4 CIDR prefix `0..32`.
- **Example**:
  ```bash
  ./run.sh --route=/ip --ip_address=203.0.113.10 --subnet=24
  ```

### `/reboot`
- **Purpose**: reboot the host (with a 5-second delay).
- **Parameters**: none.
- **Example**:
  ```bash
  ./run.sh --route=/reboot
  ```

### `/backup` *(CLI only)*
- **Purpose**: run backup + export for all instances.
- **Parameters**: none.
- **Example**:
  ```bash
  ./run.sh --route=/backup
  ```

### `/logrotate` *(CLI only)*

- **Purpose**: rotate logs for all instances according to their `INSTANCE_TYPE`.
- **Parameters**: none.
- **Current support**: `equal` instances are handled by `logrotate_equal()`,
  which runs `tools/instance-eq-logrotate.sh`. Other types return
  `logrotate_not_supported` without stopping the remaining instances.
- **Effect**: the default limit is 100 MiB (`100 * 1024 * 1024` bytes). Above
  that limit, the action keeps the latest complete JSONL lines in `equal.log`,
  up to roughly 100 MiB. The new archive is likewise capped at roughly 100 MiB
  and retains the lines closest to the active log; older excess data is
  discarded. Existing oversized archives are truncated on the next run as
  well, even if the active log is below the limit. Five archives are kept by
  default.
- **Example**:
  ```bash
  ./run.sh --route=/logrotate
  ```

The same controller runs automatically every day at `01:00` in the host's local
time. The system crontab invokes `src/cron.php` every minute; `src/cron.php`
decides when the controller is due.

## Instance actions

### `/instance/create`
- **Required parameters**:
  - `USERNAME` *(string)*: instance FQDN (max 32 characters due to Linux username limit).
  - `PASSWORD` *(string)*: 8 to 70 characters.
- **Optional parameters**:
  - `CIPHER_KEY` *(string)*: exactly 32 characters.
  - `HTTPS_REDIRECT` *(string)*: `redirect` or `noredirect`.
  - `MEM_LIMIT` *(string)*: format `\d+[MG]` (examples: `512M`, `2G`).
  - `CPU_LIMIT` *(number / numeric string)*.
  - `EQ_MEM_FREE_LIMIT` *(string, default: `256M`)*.
  - `INIT` *(bool, default: `false`)*: start the template's `init.sh` in the background after creation.
- **Example**:
  ```bash
  ./run.sh --route=/instance/create --USERNAME=example.com --PASSWORD='StrongPass123' --MEM_LIMIT=1024M --CPU_LIMIT=1
  ```

### `/instance/delete`
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/delete --instance=example.com
  ```

### `/instance/status`
- **Parameters**:
  - `instance` *(string, required)*.
  - `scope` *(string, optional)*: `instant`, `state`, or `config`.
- **Example**:
  ```bash
  ./run.sh --route=/instance/status --instance=example.com --scope=instant
  ```

Without `scope`, the result contains `instant`, `state` and `config`.
`config.php` contains `version` and `build_date`; `build_date` is an ISO 8601
string or `null`. `config.framework` identifies the installed framework,
`config.version` reports its version when available, and `config.sources`
lists the source paths inside the application container with their Git
repository and branch.

### `/instance/backup`
- **Parameters**:
  - `instance` *(string, required)*.
  - `encrypt` *(bool, optional, default: `true`)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/backup --instance=example.com --encrypt=true
  ```

### `/instance/backup-stack`
- **Purpose**: create a `.tar.gz` backup containing one independent Docker archive
  per image used by the instance stack.
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/backup-stack --instance=example.com
  ```

The returned filename uses `{instance}_{backup_id}_stack.tar.gz`, where `backup_id`
is a 13-digit value. The outer archive contains URL-encoded image references such as
`mysql%3A5.7.44.tar` directly at its root; it does not contain `stack.json`.

### `/instance/backups`
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/backups --instance=example.com
  ```

### `/instance/export-backup`
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/export-backup --instance=example.com --backup_id=20241210091621
  ```

### `/instance/export-backup-stack`
- **Purpose**: send a stack backup to the configured backup host and remove the local
  export after a successful transfer.
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*: exactly 13 digits.
- **Example**:
  ```bash
  ./run.sh --route=/instance/export-backup-stack --instance=example.com --backup_id=2026101300001
  ```

### `/instance/import-backup`
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/import-backup --instance=example.com --backup_id=20241210091621
  ```

### `/instance/import-backup-stack`
- **Purpose**: retrieve a stack backup into the instance's `import` directory without
  loading its images.
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*: exactly 13 digits.
- **Example**:
  ```bash
  ./run.sh --route=/instance/import-backup-stack --instance=example.com --backup_id=2026101300001
  ```

### `/instance/integrate-backup-stack`
- **Purpose**: load only stack-backup image tags that are absent from `get_images()`.
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*: exactly 13 digits.
- **Example**:
  ```bash
  ./run.sh --route=/instance/integrate-backup-stack --instance=example.com --backup_id=2026101300001
  ```

The result contains `integrated` and `skipped` image-reference lists. References
without an explicit tag are compared as `:latest`.

### `/instance/restore`
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*.
  - `passphrase` *(string, optional)*: required when restoring an encrypted backup.
- **Example**:
  ```bash
  ./run.sh --route=/instance/restore --instance=example.com --backup_id=20241210091621 --passphrase='your-gpg-passphrase'
  ```

### `/instance/start`

- **Purpose**: start the instance's Docker Compose services.
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/start --instance=example.com
  ```

### `/instance/stop`

- **Purpose**: stop the instance's Docker Compose services.
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/stop --instance=example.com
  ```

### `/instance/enable-maintenance`
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/enable-maintenance --instance=example.com
  ```

### `/instance/disable-maintenance`
- **Parameters**:
  - `instance` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/disable-maintenance --instance=example.com
  ```

## Practical notes

- HTTP routes exposed by `listener.php` include `/status`, `/instances`,
  `/images`, `/stacks`, `/ip`, `/reboot`, and all `/instance/*` routes listed
  above.
- `/backup` and `/logrotate` are available in CLI (direct controller execution), but they are not part of the HTTP listener route list.
- `run.sh` forwards arguments to `src/run.php`; `--method` exists in the parser but is not used to choose the controller.
