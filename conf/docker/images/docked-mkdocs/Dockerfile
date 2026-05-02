FROM squidfunk/mkdocs-material

LABEL Description="MkDocs + yq + Apache UID/GID injection" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="1.1"

ARG apache_uid
ARG apache_gid

# Install yq (Mike Farah)
RUN wget -q https://github.com/mikefarah/yq/releases/latest/download/yq_linux_amd64 \
    -O /usr/local/bin/yq \
 && chmod +x /usr/local/bin/yq

