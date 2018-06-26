FROM ubuntu:18.04

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y git cpio rpm tar bzip2 make curl nodejs npm && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/* && \
    npm install --global npm@6.1.0

WORKDIR /plugin
CMD make docker-run
