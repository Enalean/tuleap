#!/usr/bin/env bash

set -ex

set_community_version() {
    if [ ! -f ENTERPRISE_BUILD ]; then
        tools/utils/version_numbers/update-community-version.sh
    fi
}

build_generated_files() {
    CYPRESS_INSTALL_BINARY=0 pnpm install --frozen-lockfile && pnpm run build
    make composer preload MODE=Prod
    tools/utils/generate-mo.sh "$(pwd)"
    make generate-templates
    nix-build tools/rpm/tuleap-source-tarball.nix -o ./result-tarball
    if [ "$1" = "dev" ]; then
        make composer generate-po
    fi
}

configure_composer_github_auth(){
    if [ ! -z "$COMPOSER_GITHUB_AUTH" ]; then
        composer config --global --auth github-oauth.github.com "$COMPOSER_GITHUB_AUTH"
    fi
}

set_community_version
configure_composer_github_auth
build_generated_files $@
