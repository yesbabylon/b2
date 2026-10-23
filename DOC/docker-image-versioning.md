# B2 Docker image versioning

## Purpose and scope

B2 uses Docker images to provide application runtime environments, including
eQual and WordPress. This document defines how those images are named,
described, released, promoted, deployed and retained.

The policy has five goals:

- **Consistency:** instances on the same application version use the same
  runtime environment.
- **Traceability:** each image revision identifies a known set of software
  components and configuration.
- **Maintainability:** runtime components can be updated without creating a
  new application version.
- **Simplicity:** B2 avoids redundant versioning mechanisms and limits the
  number of image variants in circulation.
- **Recoverability:** an instance or backup can be associated with the image
  revision that it used.

The same principles apply to eQual, WordPress and any other application
framework supported by B2.

## Image identity

### Naming convention

B2 image tags use this format:

```text
b2-{application}:{version}-r{revision}
```

For example:

```text
b2-equal:2.1-r3
b2-wordpress:7.1-r2
```

A registry hostname or namespace may be prepended when the image is stored in
a remote registry; it does not change the B2 identity described here.

### Application version

`version` identifies the major and minor application version for which the
image's system requirements are validated. It is a compatibility target, not
an assertion about the exact application patch installed in a container. For
example:

- `b2-equal:2.1` is the image family for eQual 2.1.
- `b2-wordpress:7.1` is the image family for WordPress 7.1.x.

A family can contain several runtime revisions and several patch releases of
the application framework. Application patch numbers are therefore omitted
from the image family and from the application component in the manifest. A
change to the reference major or minor application version starts a new image
family.

### Image revision

The `rN` suffix identifies a published build of the Docker runtime. Revisions
are numbered independently within each image family.

Any change to the runtime components or image configuration requires a new
revision. A published revision is immutable: an existing `-rN` tag must never
be reused for different image content.

Image source directories should follow the same identity, for example:

```text
conf/docker/images/equal/2.1-r3/
```

## Selecting upstream images

B2 images are generally built from official Docker images. The upstream tag
must be as specific as the upstream project allows so that rebuilding does not
silently select a different runtime.

### PHP images

An environment built directly from the official PHP image must use:

```text
php:{major}.{minor}.{patch}-apache-{debian_codename}
```

For example:

```dockerfile
FROM php:8.3.36-apache-bookworm
```

Both of the following rules are mandatory:

1. Specify all three PHP version components. Tags such as `php:8.3`,
   `php:8.3-apache` and `php:8.3-apache-bookworm` are not allowed because a
   later build could silently use another PHP patch release.
2. Specify the Debian codename. Tags such as `php:8.3.36` and
   `php:8.3.36-apache` are not allowed because their Debian base may change
   over time.

Common Debian codenames are:

| Codename | Distribution | Major version |
| --- | --- | --- |
| `bullseye` | Debian | 11 |
| `bookworm` | Debian | 12 |
| `trixie` | Debian | 13 |

The codename selects the Debian release line, not its exact patch release. The
full Debian version must be read from the built image and recorded in its
manifest.

Even a fully qualified PHP tag can technically be changed in a Docker
registry. This convention reduces implicit changes but does not by itself
guarantee a byte-for-byte reproducible build.

### WordPress images

Official WordPress images use a different convention:

```text
wordpress:{wordpress_version}-php{php_major}.{php_minor}-apache
```

For example:

```dockerfile
FROM wordpress:7.1.3-php8.3-apache
```

These tags can select the full WordPress version, the PHP major and minor
release, and the HTTP server variant. They generally do not expose the PHP
patch release, Debian codename or Debian patch release.

B2 therefore uses the most specific official WordPress tag available. The
manifest records the exact runtime versions found in the resulting image, but
records WordPress itself as the compatible major and minor target, such as
`7.1`. The upstream tag's lower precision does not alter the B2 revision rule:
any runtime change requires a new `rN` revision.

### Rules common to all upstream images

Regardless of the upstream image:

- select upstream and runtime component versions as precisely as possible;
- document the runtime versions actually present in the built image and the
  application `major.minor` compatibility target;
- create a new B2 revision for every runtime change; and
- never rebuild and replace a published revision under the same tag.

B2 instance configuration does not require SHA-256 digest references. The
immutable B2 revision tag is the operational image reference.

## Runtime component versioning

A B2 image provides a coherent set of runtime components, including the Linux
distribution, PHP, Apache, PHP extensions, Apache modules and system packages.
It also declares the major and minor eQual or WordPress version whose system
requirements are satisfied by that runtime.

### Record complete runtime versions

Record the complete version of each principal runtime component whenever it is
available. The manifest describes the built runtime, not merely the versions
suggested by its upstream tags. Application components are different: their
`version` identifies a `major.minor` compatibility target and deliberately
omits the patch number.

| Component | Example | Meaning |
| --- | --- | --- |
| Linux | Debian `13.7` | Version present in the image |
| PHP | `8.3.36` | Version present in the image |
| Apache | `2.4.65` | Version present in the image |
| eQual | `2.1` | Compatible application family |
| WordPress | `7.1` | Compatible application family |

All values in this document are illustrative unless stated otherwise. Runtime
values in a release manifest must be verified in the built image. Application
targets must be supported by compatibility testing against their declared
major and minor version.

### Linux version

The manifest always identifies the operating system component as `linux`,
regardless of its distribution:

```json
{
  "linux": {
    "distribution": "debian",
    "version": "13.7"
  }
}
```

`version` contains the complete distribution version. For Debian, inspect
`/etc/os-release` in the image and prefer `DEBIAN_VERSION_FULL` when it is
available:

```text
PRETTY_NAME="Debian GNU/Linux 13 (trixie)"
VERSION_ID="13"
VERSION_CODENAME=trixie
DEBIAN_VERSION_FULL=13.7
ID=debian
```

In this example, `distribution` is `debian` and `version` is `13.7`, not `13`.
This is the distribution version contained in the image. It is not the Linux
kernel version, which is supplied by the Docker host.

Use the equivalent authoritative release information for other
distributions.

### Changes that require a revision

Every software component or image configuration change creates a new image
revision.

| Change | Result |
| --- | --- |
| PHP `8.3.35` to `8.3.36` | New `rN` revision |
| PHP `8.3` to `8.4` | New `rN` revision after suitable validation |
| Apache `2.4.64` to `2.4.65` | New `rN` revision |
| Debian `13.6` to `13.7` | New `rN` revision |
| Debian `12` to `13` | New `rN` revision after suitable validation |
| PHP extension update | New `rN` revision |
| Apache module update | New `rN` revision |
| System package update | New `rN` revision |
| Image configuration change | New `rN` revision |
| Application patch update included in the image | New `rN` revision |
| Application major or minor target change | New image family |

A single revision may include several changes. Major or minor runtime upgrades
require appropriate compatibility testing, but do not automatically require a
new application version.

## Current revision and environment consistency

For each reference application version, B2 designates exactly one image
revision as current. That revision is the reference runtime for all instances
in the family.

For example:

| Image | Debian | PHP | Apache | Status |
| --- | --- | --- | --- | --- |
| `b2-equal:2.1-r1` | 13.5 | 8.3.34 | 2.4.64 | Historical |
| `b2-equal:2.1-r2` | 13.6 | 8.3.35 | 2.4.64 | Historical |
| `b2-equal:2.1-r3` | 13.7 | 8.3.36 | 2.4.65 | Current |

These versions are illustrative. In this example, all eQual 2.1 instances
should converge on `b2-equal:2.1-r3`.

Publishing a revision does not make it current automatically. A revision may
be built, verified and published before it is promoted. Promotion starts the
process of aligning the affected instances.

Different revisions may coexist temporarily during a rollout or rollback,
but this must not become a permanent configuration. Individual instances do
not select their Linux, PHP or Apache versions separately; the selected B2
image revision determines the complete runtime.

## Image manifest

Every B2 application image has a `manifest.json`. Its schema is defined in
[`conf/docker/images/manifest.schema.json`](../conf/docker/images/manifest.schema.json).

The manifest describes the runtime software and conventional paths present in
the built image. It also guarantees that this runtime satisfies the system
requirements of the declared eQual or WordPress `major.minor` target. It does
not guarantee which application patch is installed in a particular container.
It complements the Dockerfile; it does not duplicate build instructions or
create a second image versioning mechanism.

The manifest does not contain:

- the Docker image name or tag;
- the B2 `rN` revision;
- the upstream base image name;
- an image digest;
- a manifest schema version; or
- the installed patch version of eQual or WordPress.

### Structure

The manifest has three top-level sections:

| Section | Purpose |
| --- | --- |
| `description` | Optional description of the runtime |
| `components` | Software components and their characteristics |
| `paths` | Conventional paths used by B2 |

### eQual example

The following illustrative manifest could describe `b2-equal:2.1-r3`:

```json
{
  "description": "B2 runtime for eQual",
  "components": {
    "linux": {
      "distribution": "debian",
      "version": "13.7",
      "packages": {
        "msodbcsql": "17"
      }
    },
    "php": {
      "version": "8.3.36",
      "extensions": [
        "dom",
        "ftp",
        "gd",
        "intl",
        "mbstring",
        "mysqli",
        "opcache",
        "pdo_sqlsrv",
        "sqlsrv",
        "zip"
      ]
    },
    "apache": {
      "version": "2.4.65",
      "modules": [
        "expires",
        "rewrite"
      ]
    },
    "equal": {
      "version": "2.1"
    }
  },
  "paths": {
    "application": "/var/www/html",
    "public": "/var/www/html/public",
    "bin": "/opt/bin"
  }
}
```

### WordPress example

```json
{
  "description": "B2 runtime for WordPress",
  "components": {
    "linux": {
      "distribution": "debian",
      "version": "13.7"
    },
    "php": {
      "version": "8.3.36"
    },
    "apache": {
      "version": "2.4.65"
    },
    "wordpress": {
      "version": "7.1"
    }
  },
  "paths": {
    "application": "/var/www/html",
    "public": "/var/www/html"
  }
}
```

The same structure applies to other application frameworks.

### Component convention

`components` is a dictionary keyed by component name. Every component must
have a `version` property. Additional properties are component-specific. The
current schema defines, among others:

- `linux.distribution`: Linux distribution identifier;
- `linux.packages`: significant system packages and their versions;
- `php.extensions`: installed PHP extensions; and
- `apache.modules`: enabled Apache modules.

Application components such as `equal` and `wordpress` also require a
`version`, but use it differently. Their value is the `major.minor` application
family whose system requirements have been validated against the image. It is
not the installed application version, and its patch number is intentionally
omitted because compatibility is defined at the application-family level.

The dictionary can be extended with other components, such as Node.js, Python
or Nginx, without changing the general manifest structure.

### Path convention

`paths` contains optional conventional locations used by B2:

| Property | Meaning |
| --- | --- |
| `application` | Application code root |
| `public` | Root exposed by the HTTP server |
| `bin` | Additional executable directory |

`application` and `public` may be identical or different. Docker runtime
settings such as `ENTRYPOINT`, `CMD`, `WORKDIR` and volumes remain in the
Dockerfile and instance configuration.

### Maintainer responsibilities

The image maintainer must:

- select runtime versions and the application compatibility target;
- record the exact runtime versions in the manifest;
- verify that the runtime values match the built image;
- validate that the runtime satisfies the declared application's system
  requirements; and
- assign a new revision to every runtime change.

The manifest does not need an automatic component-discovery mechanism, though
automated build checks may supplement the maintainer's verification.

## File responsibilities

| File | Responsibility |
| --- | --- |
| `Dockerfile` | Select the upstream image and install and configure components |
| `manifest.json` | Describe components, versions and conventional paths |
| `build.sh` | Build the image and assign its B2 tag; release tooling may also publish it |
| `docker-compose.yml` | Select an image revision and define instance-specific runtime configuration |

The release process and image tag carry the image identity and revision. The
manifest is descriptive and must not duplicate that identity.

## Release workflow

A new image revision follows this sequence:

1. **Select:** choose the component versions to include.
2. **Prepare:** update the Dockerfile and related image files.
3. **Document:** update `manifest.json` with the expected characteristics.
4. **Revise:** choose the next unused `rN` value in the image family.
5. **Build:** build the image under the new B2 tag.
6. **Verify:** inspect the resulting component versions, validate the manifest
   and test the runtime.
7. **Publish:** publish the new immutable tag without changing an existing
   revision.
8. **Validate:** test the revision with the affected applications.
9. **Promote:** designate the revision as current.
10. **Deploy:** progressively align existing instances with the current
    revision.

### Version inspection

Run checks against the built image before publication. Relevant commands
include:

```bash
# Linux distribution
cat /etc/os-release

# PHP
php -r 'echo PHP_VERSION, PHP_EOL;'

# Apache
apache2 -v

# PHP extensions
php -m

# Apache modules
apache2ctl -M
```

These checks are evidence for the manifest values; they do not replace
functional and application compatibility tests.

## Instance deployment

An instance references the application version and exact image revision:

```yaml
services:
  application:
    image: b2-equal:2.1-r3
```

No additional instance settings select PHP, Apache or Debian versions. New
instances use the current revision of their image family. After a revision is
promoted, existing instances are progressively updated to it.

Historical revisions remain available while an active instance, rollback
procedure, backup or restore procedure still depends on them.

## Retention and cleanup

B2 minimizes the number of image variants in circulation. Each image family
has one current revision. Historical revisions may be retained temporarily
for instances awaiting migration, rollback operations, or backup and restore
requirements.

A historical revision may be removed only after confirming that no instance,
backup or recovery procedure requires it. Retaining every historical revision
indefinitely is not a goal.

## Policy summary

1. An application reference version defines one B2 image family.
2. Each family has exactly one current revision.
3. Each revision represents one determined runtime environment.
4. Every runtime or image configuration change requires a new revision.
5. A published revision must never be replaced with different content.
6. Official PHP base tags include the complete PHP version and Debian
   codename.
7. WordPress images use the most specific official tag available, while the
   manifest records WordPress as a `major.minor` compatibility target.
8. The manifest records complete Linux, PHP and Apache versions and the
   significant characteristics of other components.
9. eQual and WordPress component versions omit the patch number and do not
   guarantee the application version installed in a container.
10. The same manifest structure applies to every application framework.
11. Instances in one image family converge on a homogeneous runtime.
12. Instances select a B2 image revision rather than individual runtime
    component versions.
13. Historical revisions are retained only for as long as they are needed.
