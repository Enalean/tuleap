ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixpkgs/nix-unstable-static:latest@sha256:8a9138d663d5a0d4c3ab01ee328e7e30dd236119d542df0a0355d66231049228
COPY . /
RUN mv /nix-container.conf /etc/nix/nix.conf && \
    cp -a "$(nix-build --no-out-link ./pinned-nixpkgs.nix -A pkgsStatic.nix)"/bin/nix /bin/nix && \
    mkdir /home_build && chmod -R 777 /home_build && \
    rm -rf /nix && rm /bin/rm /bin/find /bin/xargs /bin/chmod
ENV XDG_CACHE_HOME=/home_build/
ENV TMPDIR=/home_build/
