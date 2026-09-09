# Folder structure improvement proposals

## Why this proposal

This repository is generally readable, but two recurrent pain points appear:

1. **Configuration paths are very deep** in `conf/docker/images/...`, which slows down navigation.
2. **Some naming and placement have historically been inconsistent**, especially across operator scripts and between the README inventory and the files actually present in `scripts/`.

The goal here is to keep behavior unchanged while improving discoverability.

---

## Concrete issues observed

### 1) Docker image path depth is high
Current examples:

- `conf/docker/images/docked-nginx/...`
- `conf/docker/images/docked-equal/...`
- `conf/docker/images/docked-wordpresss/...`

This is a depth of 4-5 levels before reaching the files you edit most often.

### 2) Mixed concerns in `conf/`
`conf/` currently mixes:

- system configs (`etc/...`)
- TLS material (`default.crt`, `default.key`, `dhparam.pem`)
- Docker runtime assets (`docker/...`)
- instance templates (`instance/<type>/...`)
- service units (`*.service`)

This makes `conf/` a catch-all instead of a clear domain folder.

### 3) Script inventory maintenance
The README script inventory must remain synchronized with the files present in
`scripts/`.

### 4) Historical naming inconsistencies
- `doc/` vs `docs/` (industry standard is often `docs/`)
- typo-like folder: `docked-wordpresss` (triple `s`)
- before normalization, shell scripts used several incompatible naming orders, such as
  `get_php_version.sh`, `docker-export-images.sh` and
  `ssl_certificate-export.sh`
- underscores and hyphens did not have a single documented meaning

---

## Operator script naming convention

Operator scripts use the following structure:

```text
<scope>-<resource_name>-<action>.sh
```

Each separator has a distinct meaning:

- hyphens separate the scope, resource and action
- underscores join words belonging to the same resource name
- the action is always a single lowercase verb placed at the end

The initial supported scopes are:

- `host`: changes or inspects the host, its network, services or shared
  infrastructure
- `instance`: changes or inspects one hosted instance identified by its user or
  domain name

Examples:

```text
host-ssl_certificates-export.sh
host-docker_images-import.sh
host-private_ip-set.sh
host-public_ip_firewall-enable.sh
host-b2_listener-disable.sh
instance-wp_version-get.sh
instance-php_version-get.sh
instance-eq_logs-rotate.sh
```

Prefer a small, stable action vocabulary such as `get`, `set`, `add`, `remove`,
`enable`, `disable`, `import`, `export` and `rotate`. A new scope or action
should be introduced only when none of the existing terms describes the
operation accurately.

The scope describes what the script operates on, not where the related data
originated. For example, certificate import and export are `host` operations
because they modify the shared NGINX certificate store on the host, even though
the selected certificates belong to an instance.

### Script rename mapping

The repository uses the canonical names below. The legacy-name column is kept
to help migrate external automation and operator documentation.

| Legacy name | Canonical name |
| --- | --- |
| `b2_listener-disable.sh` | `host-b2_listener-disable.sh` |
| `b2_listener-enable.sh` | `host-b2_listener-enable.sh` |
| `docker-export-images.sh` | `host-docker_images-export.sh` |
| `docker-import-images.sh` | `host-docker_images-import.sh` |
| `fail2ban-disable.sh` | `host-fail2ban-disable.sh` |
| `fail2ban-enable.sh` | `host-fail2ban-enable.sh` |
| `flush_eq_logs.sh` | `instance-eq_logs-rotate.sh` |
| `get_eq_version.sh` | `instance-eq_version-get.sh` |
| `get_php_version.sh` | `instance-php_version-get.sh` |
| `get_wp_version.sh` | `instance-wp_version-get.sh` |
| `public_ip-add.sh` | `host-public_ip-add.sh` |
| `public_ip-remove.sh` | `host-public_ip-remove.sh` |
| `public_ip_firewall-disable.sh` | `host-public_ip_firewall-disable.sh` |
| `public_ip_firewall-enable.sh` | `host-public_ip_firewall-enable.sh` |
| `set_hostname.sh` | `host-hostname-set.sh` |
| `set_private_ip.sh` | `host-private_ip-set.sh` |
| `ssl_certificate-export.sh` | `host-ssl_certificates-export.sh` |
| `ssl_certificate-import.sh` | `host-ssl_certificates-import.sh` |

---

## Recommended target structure (v2)

```text
b2/
├── apps/
│   └── listener/                 # PHP API/listener source
│       ├── bootstrap/            # boot.lib.php, init code
│       ├── controllers/
│       ├── services/             # former helpers with business logic
│       └── bin/                  # listener.php, run.php, cron.php, send.php
├── infra/
│   ├── docker/
│   │   ├── images/
│   │   │   ├── nginx/
│   │   │   ├── equal/
│   │   │   └── wordpress/
│   │   └── stacks/
│   │       └── nginx-proxy/docker-compose.yml
│   ├── systemd/
│   │   ├── b2-listener.service
│   │   └── portainer.service
│   ├── nginx/
│   │   ├── nginx.conf
│   │   └── vhost.d/default
│   ├── security/
│   │   ├── fail2ban/
│   │   └── tls/
│   └── instance-templates/
│       └── create/
├── scripts/
│   ├── host-<resource_name>-<action>.sh
│   └── instance-<resource_name>-<action>.sh
├── docs/
│   ├── api.md
│   ├── cli-memo.md
│   └── architecture/
└── README.md
```

---

## Quick wins (low-risk, high impact)

1. **Align docs with reality now**
   - keep the README script inventory aligned with `scripts/`.
2. **Normalize `docked-wordpresss` naming**
   - rename to `wordpress` (or `docked-wordpress` if you want to keep prefix pattern).
3. **Apply the script convention to every new script**
   - use `<scope>-<resource_name>-<action>.sh` for every new operator script.
4. **Audit external automation after renames**
   - update cron jobs, deployment tooling and operator commands that use legacy names.
5. **Add one architecture map file**
   - a short `docs/architecture/tree.md` with "where to put what" rules.

---

## Migration plan (safe and progressive)

### Phase 1 — Documentation & guardrails
- Update README structure section to current paths.
- Document and enforce the `<scope>-<resource_name>-<action>.sh` convention.
- Restrict script scopes initially to `host` and `instance`.
- Add a small CI check preventing new folders above an agreed depth.

### Phase 2 — Non-breaking moves
- Keep the completed script rename mapping available for external consumers.
- Move folders with compatibility wrappers/symlinks where needed.
- Update path references in shell scripts and PHP entrypoints.
- Validate with smoke tests (`install.sh`, core routes, backup flow).

### Phase 3 — Cleanup
- Remove any folder-level legacy aliases after one release cycle.
- Freeze final structure in contributor docs.

---

## Suggested placement rules (to avoid future drift)

- **Runtime app code**: `apps/...`
- **Infra config (docker/systemd/nginx/fail2ban/tls)**: `infra/...`
- **Operator scripts**: `scripts/...`
- **Documentation**: `docs/...`
- **No folder deeper than 4 levels** without explicit exception in docs.

---

## Priority recommendation

If you only do three actions now:

1. Enforce `<scope>-<resource_name>-<action>.sh` for new scripts.
2. Audit external automation for references to legacy script names.
3. Keep the README script inventory synchronized with the real `scripts/` folder.

These three changes establish a predictable operator interface without breaking
existing automation.
