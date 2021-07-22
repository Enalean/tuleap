ARG DOCKER_REGISTRY=docker.io
FROM ${DOCKER_REGISTRY}/nixos/nix:2.3.12@sha256:d9bb3b85b846eb0b6c5204e0d76639dff72c7871fb68f5d4edcfbb727f8a5653
RUN mkdir -p /output/store
COPY . /
RUN nix-env --profile /output/profile -i -f build-tools/
RUN cp -va $(nix-store -qR /output/profile) /output/store

FROM scratch
COPY --from=0 /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=0 /output/store /nix/store
COPY --from=0 /output/profile/ /usr/
COPY --from=0 /output/profile/bin/sh /bin/sh
