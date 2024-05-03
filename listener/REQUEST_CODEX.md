# Requests codex

## ``/instance/create``

```bash
curl -X POST \
http://0.0.0.0:8000/instance/create \
-H 'Content-Type: application/json' \
-d '{
  "symbiose": true,
  "equalpress": true,
  "USERNAME": "test2.run",
  "APP_USERNAME": "root",
  "APP_PASSWORD": "test",
  "CIPHER_KEY": "xxxxxxxxxxxxxx",
  "HTTPS_REDIRECT": "noredirect",
  "WP_VERSION": "6.4",
  "WP_EMAIL": "root@equal.local",
  "WP_TITLE": "eQualpress"
}'
```

## ``/instance/delete``

```bash
curl -X POST \
http://0.0.0.0:8000/instance/delete \
-H 'Content-Type: application/json' \
-d '{
  "instance": "test1.run"
}'
```

## ``/instance/info``

```bash
curl -X POST \
http://0.0.0.0:8000/instance/info \
-H 'Content-Type: application/json' \
-d '{
  "instance": "test2.run"
}'
```

## ``/instances``

```bash
curl -X POST \
http://0.0.0.0:8000/instances \
-H 'Content-Type: application/json' \
-d '{}'
```

## ``/instance/reboot``

```bash
curl -X POST \
http://0.0.0.0:8000/reboot \
-H 'Content-Type: application/json' \
-d '{}'
```
