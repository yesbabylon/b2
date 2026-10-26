FROM squidfunk/mkdocs-material:9.8.0

LABEL Description="Material for MkDocs 9.8 with yq" \
      Maintainer="Cedric Francoys <cedricfrancoys@gmail.com>" \
      License="Apache License 2.0" \
      Version="b2-mkdocs:9.8-r1"

# Install yq (Mike Farah)
RUN wget -q https://github.com/mikefarah/yq/releases/download/v4.54.1/yq_linux_amd64 \
    -O /usr/local/bin/yq \
 && chmod +x /usr/local/bin/yq
