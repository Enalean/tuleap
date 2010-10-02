#!/bin/bash
#
# msgfmt.sh
#
# Recompiles binary MO files for all languages
#
# @author Christopher Han <xiphux@gmail.com>
# @copyright Copyright (c) 2010 Christopher Han
# @package GitPHP
# @package util
#

LOCALEDIR="locale"
BUNDLE="gitphp"

for i in "$LOCALEDIR"/*; do
	if [ -d "$i" ]; then
		if [ -e "${i}/${BUNDLE}.po" ]; then
			echo "Building ${i}..."
			rm -f "${i}/${BUNDLE}.mo"
			msgfmt -v -o "${i}/${BUNDLE}.mo" "${i}/${BUNDLE}.po"
		fi
	fi
done
