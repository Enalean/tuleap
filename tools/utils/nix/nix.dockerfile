ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixpkgs/nix-unstable-static@sha256:39eb56c02f5202a7b206a86810261dcc01566fcb0e351467137b7bfc2bacbde7
COPY . /
RUN mv /nix-container.conf /etc/nix/nix.conf && \
    cp -a "$(nix-build --no-out-link ./pinned-nixpkgs.nix -A pkgsStatic.nix)"/bin/nix /bin/nix && \
    rm -rf /nix && rm /bin/rm /bin/find /bin/xargs /bin/chmod
