FROM ubuntu:18.04

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y php php-dom php-zip unzip git cpio rpm tar bzip2 make curl nodejs npm && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/* && \
    npm install --global npm@6.7.0

COPY --from=composer:1.8 /usr/bin/composer /usr/bin/composer

WORKDIR /plugin
CMD make docker-run
