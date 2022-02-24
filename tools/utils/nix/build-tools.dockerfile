ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.6.1@sha256:2e810dab4086d318d8559b3bd5dce46ce4af3cde48a190af9527e0228aa9580b
RUN mkdir -p /output/store
COPY . /

RUN nix-env -q | xargs nix-env --set-flag priority 99 && nix-env -i -f build-tools/ && nix-collect-garbage -d && nix-store --optimise
