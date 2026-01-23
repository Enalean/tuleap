#! /usr/bin/env sh

set -eux

if [ -z "${CACHE_NAME:-}" ]; then
    >&2 echo "CACHE_NAME environment variable must be defined"
    exit 1
fi

if [ -z "${SIGNING_PUBLIC_KEY:-}" ]; then
    >&2 echo "SIGNING_PUBLIC_KEY environment variable must be defined"
    exit 1
fi

mkdir -p ~/.config/nix/
echo "substituters = https://nix-cache.enalean.com/${CACHE_NAME} https://cache.nixos.org" > ~/.config/nix/nix.conf
echo "trusted-public-keys = ${SIGNING_PUBLIC_KEY} cache.nixos.org-1:6NCHdD59X431o0gWypbMrAURkbJ16ZPMQFGspcDShjY=" >> ~/.config/nix/nix.conf

if [ -z "${AUTH_TOKEN:-}" ]; then
    exit 0
fi

# Create netrc file consumed by Nix
echo "machine nix-cache.enalean.com" > ~/.config/nix/netrc
echo "password ${AUTH_TOKEN}" >> ~/.config/nix/netrc

# Add netrc file to Nix configuration
echo "netrc-file = $(realpath ~/.config/nix/netrc)" >> ~/.config/nix/nix.conf

# Setup credential consumed by Attic
mkdir -p ~/.config/attic/
echo 'default-server = "enalean"' > ~/.config/attic/config.toml
echo '[servers.enalean]' >> ~/.config/attic/config.toml
echo 'endpoint = "https://nix-cache.enalean.com"' >> ~/.config/attic/config.toml
echo "token = \"${AUTH_TOKEN}\"" >> ~/.config/attic/config.toml
