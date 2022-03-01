ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.7.0@sha256:3a2c7a7e5ca8b7f4c128174e3fe018811640e7a549cd1aed4b1f1a20ed7786a5
RUN mkdir -p /output/store
COPY . /

RUN nix-env -q | xargs nix-env --set-flag priority 99 && nix-env -i -f build-tools/ && nix-collect-garbage -d && nix-store --optimise
