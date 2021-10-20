ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.3.12@sha256:d9bb3b85b846eb0b6c5204e0d76639dff72c7871fb68f5d4edcfbb727f8a5653
RUN mkdir -p /output/store
COPY . /

RUN nix-env --set-flag priority 99 nix && nix-env -i -f build-tools/ && nix-collect-garbage -d && nix-store --optimise
