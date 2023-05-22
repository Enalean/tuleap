#!/usr/bin/env bash

set -ex

TULEAP_BUILD_TMP_FOLDER='/home_build'

ROOT_DIR=$(readlink -f "$(dirname $0)/../../..")

create_tuleap_build_folders() {
    mkdir -p "$TULEAP_BUILD_TMP_FOLDER"
}

build_generated_files() {
    # Setting the HOME environment variable is crappy but it seems that is the
    # only way to prevent npm and node-gyp to put their files everywhere
    TMPDIR="$TULEAP_BUILD_TMP_FOLDER" TMP="$TULEAP_BUILD_TMP_FOLDER" HOME="$TULEAP_BUILD_TMP_FOLDER" XDG_RUNTIME_DIR="$TULEAP_BUILD_TMP_FOLDER" OS=${OS:-centos7} make -C "$ROOT_DIR/tools/rpm" tarball
    if [ "$1" = "dev" ]; then
        TMPDIR="$TULEAP_BUILD_TMP_FOLDER" TMP="$TULEAP_BUILD_TMP_FOLDER" HOME="$TULEAP_BUILD_TMP_FOLDER" XDG_RUNTIME_DIR="$TULEAP_BUILD_TMP_FOLDER" make composer generate-po
    fi
}

configure_composer_github_auth(){
    if [ ! -z "$COMPOSER_GITHUB_AUTH" ]; then
        HOME="$TULEAP_BUILD_TMP_FOLDER" TMPDIR="$TULEAP_BUILD_TMP_FOLDER" composer config --global --auth github-oauth.github.com "$COMPOSER_GITHUB_AUTH"
    fi
}

copy_tarball_to_output_dir() {
    cp "$TULEAP_BUILD_TMP_FOLDER"/rpmbuild/SOURCES/*.tar.gz "$ROOT_DIR"
}

create_tuleap_build_folders
configure_composer_github_auth
build_generated_files $@
copy_tarball_to_output_dir
