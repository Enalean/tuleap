ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixpkgs/nix-unstable-static:latest@sha256:c127b0205381f812bf2cc7c5b477d26547709dceef943869ec616ac0f9917324
COPY . /
RUN mv /nix-container.conf /etc/nix/nix.conf && \
    cp -a "$(nix-build --no-out-link ./pinned-nixpkgs.nix -A pkgsStatic.nix)"/bin/nix /bin/nix && \
    mkdir /home_build && chmod -R 777 /home_build && \
    rm -rf /nix && rm /bin/rm /bin/find /bin/xargs /bin/chmod
ENV XDG_CACHE_HOME=/home_build/
ENV TMPDIR=/home_build/
