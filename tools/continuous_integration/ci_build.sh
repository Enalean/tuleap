#!/bin/sh

set -e

usage() {
    cat <<EOF
Usage: $1 [options]
Options
  --without-svn-sniff  Disable usage of SVN log to discover new files to sniff
  --srcdir=<value>    Specify a sourcedir
  --workspace=<value>  Absolute path to where 'src-dir' stands

Note: $WORKSPACE is a variable of Hudson/Jenkins and might replace --workspace
EOF
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  perl -pi -e "s/$2/$replacement/g" $1
}

##
## Default values
##
local_module_directory="codendi-src";

##
## Parse options
##
options=`getopt -o h -l help,without-svn-sniff,srcdir:,workspace: -- "$@"`
if [ $? != 0 ] ; then echo "Terminating..." >&2 ; usage $0 ;exit 1 ; fi
eval set -- "$options"
while true
do
    case "$1" in
	-h|--help)
	    usage $0
	    exit 0;;
	--srcdir)
	    local_module_directory=$2; 
	    shift 2;;
	--workspace)
	    WORKSPACE=$2; 
	    shift 2;;
	 *)
	    break;;
    esac
done
# Options post treatment
src_dir="$WORKSPACE/$local_module_directory"

# Go!
set -x
cd "$src_dir"

# Create /var/tmp/codendi_cache dir
mkdir -p ../var/tmp/codendi_cache/

# Create /etc/codendi/conf and /etc/codendi/plugins/IM/etc dir
mkdir -p ../etc/codendi/conf/
mkdir -p ../etc/codendi/plugins/IM/etc/

# Copy dist files to etc dir
cp src/etc/database.inc.dist ../etc/codendi/conf/database.inc
cp plugins/IM/include/jabbex_api/installation/resources/jabbex_conf.tpl.xml ../etc/codendi/plugins/IM/etc/jabbex_conf.xml
cp src/etc/local.inc.dist ../etc/codendi/conf/local.inc

# Substitute dist values by correct ones
substitute '../etc/codendi/conf/local.inc' '%sys_default_domain%' "localhost" 
substitute '../etc/codendi/conf/local.inc' '%sys_ldap_server%' " " 
substitute '../etc/codendi/conf/local.inc' '%sys_org_name%' "Tuleap" 
substitute '../etc/codendi/conf/local.inc' '%sys_long_org_name%' "Tuleap" 
substitute '../etc/codendi/conf/local.inc' '%sys_fullname%' "localhost" 
substitute '../etc/codendi/conf/local.inc' '%sys_win_domain%' " " 
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/codendi' "$WORKSPACE/$local_module_directory"
substitute '../etc/codendi/conf/local.inc' '\/var\/lib\/codendi' "$WORKSPACE/var/lib/codendi"
substitute '../etc/codendi/conf/local.inc' '\/var\/log\/codendi' "$WORKSPACE/var/log/codendi"
substitute '../etc/codendi/conf/local.inc' '\/etc\/codendi' "$WORKSPACE/etc/codendi"
substitute '../etc/codendi/conf/local.inc' '\/usr\/lib\/codendi\/bin' "$WORKSPACE/etc/codendi"
substitute '../etc/codendi/conf/local.inc' '^\$sys_https_host ' "// \\\$sys_https_host"
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/htmlpurifier' "/usr/share/htmlpurifier"
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/jpgraph' "/usr/share/jpgraph"
substitute '../etc/codendi/conf/local.inc' '\/var\/tmp' "$WORKSPACE/var/tmp"

# Set environment var CODENDI_LOCAL_INC
export CODENDI_LOCAL_INC="$WORKSPACE/etc/codendi/conf/local.inc"

# Write test config file for database tests
sys_dbname=$(echo $JOB_NAME | tr '.' '_')
cat >$WORKSPACE/etc/codendi/conf/tests.inc <<EOF
<?php
\$sys_dbname = "$sys_dbname";
?>
EOF

pushd .
cd "$src_dir"

# Execute the Tests
# This will produce a "JUnit like" test result file named codendi_unit_tests_report.xml that Hudson can use to produce test results.
php -d include_path="src/www/include:src:/usr/share/pear:." -d memory_limit=196M tests/bin/simpletest -x tests/simpletest plugins tests/integration

# Checkstyle
files=$(git diff --name-only --diff-filter=A stable/master... | grep ".php" | grep -v "plugins/git/gitphp-0.1.0\|plugins/webdav/include/lib\|src/db/mysql/updates\|tools/examples\|cli" || true)
php -d memory_limit=256M /usr/bin/phpcs --standard="$src_dir/tools/utils/phpcs/Codendi" "$src_dir/src/common/chart" "$src_dir/src/common/backend" --report=checkstyle -n --ignore=*/phpwiki/* --ignore="*/webdav/lib/*" $files > $WORKSPACE/var/tmp/checkstyle.xml || true
popd

exit 0
