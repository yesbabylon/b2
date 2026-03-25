# Folder structure improvement proposals

## Why this proposal

This repository is generally readable, but two recurrent pain points appear:

1. **Configuration paths are very deep** in `conf/docker/images/...`, which slows down navigation.
2. **Some naming and placement are inconsistent**, especially between README and real paths (example: `utils/` mentioned in docs while scripts are in `scripts/`).

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
- instance templates (`instance/create/template/...`)
- service units (`*.service`)

This makes `conf/` a catch-all instead of a clear domain folder.

### 3) Script location drift
README still documents `utils/` while the repository has `scripts/`.

### 4) Naming inconsistencies
- `doc/` vs `docs/` (industry standard is often `docs/`)
- typo-like folder: `docked-wordpresss` (triple `s`)
- mixed kebab/snake patterns across shell scripts

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
│   ├── admin/
│   ├── security/
│   ├── backups/
│   └── setup/
├── docs/
│   ├── api.md
│   ├── cli-memo.md
│   └── architecture/
└── README.md
```

---

## Quick wins (low-risk, high impact)

1. **Align docs with reality now**
   - replace `utils/` mentions with `scripts/`.
2. **Normalize `docked-wordpresss` naming**
   - rename to `wordpress` (or `docked-wordpress` if you want to keep prefix pattern).
3. **Introduce aliases/symlinks during transition**
   - keep backward compatibility for scripts/automation while paths migrate.
4. **Add one architecture map file**
   - a short `docs/architecture/tree.md` with "where to put what" rules.

---

## Migration plan (safe and progressive)

### Phase 1 — Documentation & guardrails
- Update README structure section to current paths.
- Add naming rules (kebab-case for scripts, singular/plural conventions).
- Add a small CI check preventing new folders above an agreed depth.

### Phase 2 — Non-breaking moves
- Move folders with compatibility wrappers/symlinks.
- Update path references in shell scripts and PHP entrypoints.
- Validate with smoke tests (`install.sh`, core routes, backup flow).

### Phase 3 — Cleanup
- Remove legacy aliases after one release cycle.
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

1. Fix docs vs real folders (`utils` → `scripts`).
2. Simplify Docker image path naming (`docked-*` cleanup + typo fix).
3. Split `conf/` into dedicated top-level domains (`infra/security/nginx/systemd/templates`).

These three changes will reduce cognitive load significantly without changing product behavior.
