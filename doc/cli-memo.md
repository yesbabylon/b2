# CLI memo (`run.sh`) — routes/actions and expected parameters

Ce mémo résume les actions disponibles via la CLI locale (`./run.sh`), avec les paramètres attendus et leur type.

## Format de commande

```bash
./run.sh --route=<route> [--param=value ...]
```

Exemple:

```bash
./run.sh --route=instance/status --instance=example.com
```

## Règles de parsing (important)

- `--route` est obligatoire.
- Chaque argument `--clé=valeur` devient un paramètre de payload.
- Les valeurs `true` et `false` (exactement) sont converties en booléens.
- Un argument sans valeur (ex: `--flag`) devient `true`.

## Host actions

### `status`
- **But**: état global de l’hôte.
- **Paramètres**:
  - `scope` *(string, optionnel)*: `instant`, `state` ou `config`.
- **Exemple**:
  ```bash
  ./run.sh --route=status --scope=state
  ```

### `instances`
- **But**: liste des instances.
- **Paramètres**:
  - `with_deleted` *(string/bool, optionnel)*: `1`, `0`, `yes`, `no` (ou `true`/`false` via API HTTP).
- **Exemple**:
  ```bash
  ./run.sh --route=instances --with_deleted=1
  ```

### `ip`
- **But**: ajoute l’IP publique sur `veth0`.
- **Paramètres**:
  - `ip_address` *(string, requis)*: IPv4 valide.
  - `subnet` *(string/int, requis)*: CIDR IPv4 `0..32`.
- **Exemple**:
  ```bash
  ./run.sh --route=ip --ip_address=203.0.113.10 --subnet=24
  ```

### `reboot`
- **But**: redémarre l’hôte (différé de 5s).
- **Paramètres**: aucun.
- **Exemple**:
  ```bash
  ./run.sh --route=reboot
  ```

### `backup` *(CLI only)*
- **But**: lance backup + export pour toutes les instances.
- **Paramètres**: aucun.
- **Exemple**:
  ```bash
  ./run.sh --route=backup
  ```

## Instance actions

### `instance/create`
- **Paramètres requis**:
  - `USERNAME` *(string)*: FQDN (nom d’instance).
  - `PASSWORD` *(string)*: 8 à 70 caractères.
- **Paramètres optionnels**:
  - `CIPHER_KEY` *(string)*: exactement 32 caractères.
  - `HTTPS_REDIRECT` *(string)*: `redirect` ou `noredirect`.
  - `MEM_LIMIT` *(string)*: format `\d+[MG]` (ex: `512M`, `2G`).
  - `CPU_LIMIT` *(number/string numérique)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/create --USERNAME=example.com --PASSWORD='StrongPass123' --MEM_LIMIT=1024M --CPU_LIMIT=1
  ```

### `instance/delete`
- **Paramètres**:
  - `instance` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/delete --instance=example.com
  ```

### `instance/status`
- **Paramètres**:
  - `instance` *(string, requis)*.
  - `scope` *(string, optionnel)*: `instant`, `state` ou `config`.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/status --instance=example.com --scope=instant
  ```

### `instance/backup`
- **Paramètres**:
  - `instance` *(string, requis)*.
  - `encrypt` *(bool, optionnel, défaut: `true`)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/backup --instance=example.com --encrypt=true
  ```

### `instance/backups`
- **Paramètres**:
  - `instance` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/backups --instance=example.com
  ```

### `instance/export-backup`
- **Paramètres**:
  - `instance` *(string, requis)*.
  - `backup_id` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/export-backup --instance=example.com --backup_id=20241210091621
  ```

### `instance/import-backup`
- **Paramètres**:
  - `instance` *(string, requis)*.
  - `backup_id` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/import-backup --instance=example.com --backup_id=20241210091621
  ```

### `instance/restore`
- **Paramètres**:
  - `instance` *(string, requis)*.
  - `backup_id` *(string, requis)*.
  - `passphrase` *(string, optionnel)*: requis si backup chiffré.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/restore --instance=example.com --backup_id=20241210091621 --passphrase='your-gpg-passphrase'
  ```

### `instance/enable-maintenance`
- **Paramètres**:
  - `instance` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/enable-maintenance --instance=example.com
  ```

### `instance/disable-maintenance`
- **Paramètres**:
  - `instance` *(string, requis)*.
- **Exemple**:
  ```bash
  ./run.sh --route=instance/disable-maintenance --instance=example.com
  ```

## Notes pratiques

- Les routes HTTP exposées par `listener.php` sont: `status`, `instances`, `ip`, `reboot`, et toutes les routes `instance/*` listées ci-dessus.
- La route `backup` est disponible en CLI (controller direct) mais n’est pas dans la liste des routes HTTP du listener.
- Pour la CLI, `run.sh` passe les arguments à `src/run.php`; le paramètre `--method` existe mais n’est pas utilisé pour choisir le controller.
