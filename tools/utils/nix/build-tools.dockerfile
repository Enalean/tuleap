ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.10.3@sha256:1ec5b4a6bee82fc5bb93f782d08fc58029715dde166139c7164c39fa5db75d23
RUN mkdir -p /output/store
COPY . /

RUN nix-env -q | xargs nix-env --set-flag priority 99 && nix-env -i -f build-tools/ && \
    nix-env -i su-exec && cp -L $(which su-exec) /root/.nix-profile/bin/su-exec-nixdaemon && chmod u+s /root/.nix-profile/bin/su-exec-nixdaemon && \
    nix-collect-garbage -d && nix-store --optimise && \
    cachix use tuleap-community
