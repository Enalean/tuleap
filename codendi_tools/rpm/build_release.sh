#!/bin/sh

set -e

substitute() {
  if [ -f $1 ]; then
    # $1: filename, $2: string to match, $3: replacement string
    perl -pi -e "s%$2%$3%g" $1
  fi
}

# Find where the script is
if echo "$0" | egrep -e '^/' 2>&1 >/dev/null; then
    bindir=$(dirname "$0")
else
    bindir=$(cd $(dirname "$0"); pwd)
fi

# Assume the script is in the "tools" subdirectory of the whole config
rootdir=$(cd $bindir/../../; pwd)

# Docbook tools
docbook_tools="$DOCBOOK_TOOLS_DIR"
export SAXON_HOME="$docbook_tools/saxon"
export FOP_HOME="$docbook_tools/fop"
export JIMI_HOME="$docbook_tools/jimi"

export CODENDI_LOCAL_INC=$rootdir/local.inc

#
# User Guide
#
echo "Generate User Guide"
user_guide="$rootdir/documentation/user_guide"

make -C $user_guide

#
# Programmer guide
#
echo "Generate Programmer Guide"
programmer_guide="$rootdir/documentation/programmer_guide"

make -C $programmer_guide

#
# CLI
#
echo "Generate CLI"
cli_guide="$rootdir/documentation/cli"

substitute $cli_guide/xml/en_US/Codendi_CLI.xml "/etc/codendi/documentation/cli/xml/ParametersLocal.dtd" "$rootdir/cli_ParametersLocal.dtd"
substitute $cli_guide/xml/fr_FR/Codendi_CLI.xml "/etc/codendi/documentation/cli/xml/ParametersLocal.dtd" "$rootdir/cli_ParametersLocal.dtd"

substitute $cli_guide/xml/en_US/Codendi_CLI.xml "/usr/local" "$docbook_tools"
substitute $cli_guide/xml/fr_FR/Codendi_CLI.xml "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/fo/docbook_fr_FR.xsl "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/fo/docbook_en_US.xsl "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/htmlhelp/htmlhelp_fr_FR.xsl "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/htmlhelp/htmlhelp_onechunk_en_US.xsl "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/htmlhelp/htmlhelp_onechunk_fr_FR.xsl "/usr/local" "$docbook_tools"
substitute $cli_guide/xsl/htmlhelp/htmlhelp_en_US.xsl "/usr/local" "$docbook_tools"

$rootdir/src/utils/generate_cli_package.sh
