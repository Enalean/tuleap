#!/usr/bin/env bash

whatrequires() {
    echo $@
    for pkg in $(rpm -q --qf "%{NAME}\n" --whatrequires $@ | sed -e 's/^no package requires.*//'); do
	whatrequires $pkg
    done
}

whatrequires $@ | sort -u
