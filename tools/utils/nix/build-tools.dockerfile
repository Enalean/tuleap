ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix@sha256:a6bcef50c7ca82ca66965935a848c8c388beb78c9a5de3e3b3d4ea298c95c708
RUN mkdir -p /output/store
COPY . /
RUN nix-env --profile /output/profile -i -f build-tools/
RUN cp -va $(nix-store -qR /output/profile) /output/store

FROM scratch
COPY --from=0 /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=0 /output/store /nix/store
COPY --from=0 /output/profile/ /usr/
COPY --from=0 /output/profile/bin/sh /bin/sh
