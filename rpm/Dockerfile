FROM ubuntu:20.04

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y php php-dom php-zip unzip git cpio rpm tar bzip2 make curl nodejs npm && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/* && \
    npm install --global npm@6.14.9

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

WORKDIR /plugin
CMD make docker-run
