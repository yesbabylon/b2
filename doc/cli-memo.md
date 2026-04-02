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

### `/instance/backup`
- **Parameters**:
  - `instance` *(string, required)*.
  - `encrypt` *(bool, optional, default: `true`)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/backup --instance=example.com --encrypt=true
  ```

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

### `/instance/import-backup`
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*.
- **Example**:
  ```bash
  ./run.sh --route=/instance/import-backup --instance=example.com --backup_id=20241210091621
  ```

### `/instance/restore`
- **Parameters**:
  - `instance` *(string, required)*.
  - `backup_id` *(string, required)*.
  - `passphrase` *(string, optional)*: required when restoring an encrypted backup.
- **Example**:
  ```bash
  ./run.sh --route=/instance/restore --instance=example.com --backup_id=20241210091621 --passphrase='your-gpg-passphrase'
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

- HTTP routes exposed by `listener.php` include `/status`, `/instances`, `/ip`, `/reboot`, and all `/instance/*` routes listed above.
- `/backup` is available in CLI (direct controller execution), but it is not part of the HTTP listener route list.
- `run.sh` forwards arguments to `src/run.php`; `--method` exists in the parser but is not used to choose the controller.
