FROM ubuntu:19.10

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends -y ca-certificates file php php-dom php-zip unzip git cpio rpm tar bzip2 make curl nodejs npm && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/* && \
    npm install --global npm@6.13.4

COPY --from=composer:1.9 /usr/bin/composer /usr/bin/composer

WORKDIR /plugin
CMD make docker-run
