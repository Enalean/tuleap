ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.8.0@sha256:1d13ae379fb8caf3f859c5ce7ec6002643d60cf8b7b6147b949cc34880c93bac
RUN mkdir -p /output/store
COPY . /

RUN nix-env -q | xargs nix-env --set-flag priority 99 && nix-env -i -f build-tools/ && \
    nix-collect-garbage -d && nix-store --optimise && \
    cachix use tuleap-community
