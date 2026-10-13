# Agent guide

## Repository purpose

This repository installs and operates a B2 host on Ubuntu. It contains host
configuration, a PHP listener/API, instance templates and administrative tools.
The production checkout is expected at `/root/b2`.

## Project map

- `install/`: Ubuntu installation entrypoints.
- `conf/`: system, Docker and instance configuration.
- `src/`: PHP listener, controllers and helpers.
- `tools/`: host and instance administration commands. Read `tools/README.md` before invoking one.
- `DOC/`: architecture and API documentation.
- `secrets/`, `keyring/`, `logs/`: runtime or sensitive data; do not inspect,
  modify or disclose their contents unless the task explicitly requires it.

## Working rules

- Preserve unrelated working-tree changes.
- Keep changes narrowly scoped to the requested behavior.
- Do not assume that a development checkout is a disposable B2 host.
- Do not run installers or mutating tools merely to test them.
- Inspect a tool before invoking it and validate its target and arguments.
- Treat commands affecting networking, firewall rules, services, certificates,
  swap, permissions, users or Docker state as privileged and mutating.
- Obtain explicit user authorization before executing a mutating tool on a
  host. Prefer read-only checks when diagnosing.
- Never place credentials, private keys or secret values in tracked files,
  command output, fixtures or documentation.

## Conventions

- Shell tools use Bash and follow
  `<scope>-<resource_name>-<operation>.sh`.
- Supported scopes are `host` and `instance`.
- Instance tools accept the instance username/domain as their first argument
  unless their usage text states otherwise.
- Validate all arguments and required paths before changing system state.
- Quote variable expansions used as paths or command arguments.
- New privileged tools must fail clearly when not run as root.
- Keep the root `README.md` inventory and `tools/README.md` synchronized when a
  tool is added, removed or renamed.

## Verification

Use non-destructive tests or an isolated disposable host for behavior that
changes the operating system. Report any verification that could not safely be
performed.
