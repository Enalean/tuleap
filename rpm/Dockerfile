FROM ubuntu:16.04

RUN apt-get update -y && \
    apt-get install -y curl git make apt-transport-https rpm tar bzip2 cpio && \
    curl --silent https://deb.nodesource.com/gpgkey/nodesource.gpg.key | apt-key add - && \
    echo "deb https://deb.nodesource.com/node_6.x xenial main" > /etc/apt/sources.list.d/nodesource.list && \
    apt-get update -y && apt-get install -y nodejs && \
    npm install --global npm@5.4.2

WORKDIR /plugin
CMD make docker-run
