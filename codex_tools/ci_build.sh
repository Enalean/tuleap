#!/bin/sh

#
# CI build: build Codendi project on a Continuous Integration server
# Usage: sh ci_build.sh <sys_default_domain> <sys_ip_address>
# Note: WORKSPACE is a variable of Hudson
#

sys_default_domain=$1;
sys_ip_address=$2;
port="80";
sys_org_name="Xerox";
sys_long_org_name="Xerox Corporation";
codendi_dir="$WORKSPACE";

cd $WORKSPACE/trunk/

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  perl -pi -e "s/$2/$replacement/g" $1
}

# Create /var/tmp/codex_cache dir
mkdir -p ../var/
mkdir -p ../var/tmp/
mkdir -p ../var/tmp/codex_cache/

# Create /etc/codex/conf and /etc:codex/plugins/IM/etc dir
mkdir -p ../etc/
mkdir -p ../etc/codex/
mkdir -p ../etc/codex/conf/
mkdir -p ../etc/codex/plugins/
mkdir -p ../etc/codex/plugins/IM/
mkdir -p ../etc/codex/plugins/IM/etc/

# Copy dist files to etc dir
cp src/etc/database.inc.dist ../etc/codex/conf/database.inc
cp plugins/IM/include/jabbex_api/installation/resources/jabbex_conf.tpl.xml ../etc/codex/plugins/IM/etc/jabbex_conf.xml
cp src/etc/local.inc.dist ../etc/codex/conf/local.inc

# Substitute dist values by correct ones
substitute '../etc/codex/conf/local.inc' '%sys_default_domain%' "$sys_default_domain:$port" 
substitute '../etc/codex/conf/local.inc' '%sys_ldap_server%' " " 
substitute '../etc/codex/conf/local.inc' '%sys_org_name%' "Xerox" 
substitute '../etc/codex/conf/local.inc' '%sys_long_org_name%' "Xerox Corp" 
substitute '../etc/codex/conf/local.inc' '%sys_fullname%' "$sys_default_domain" 
substitute '../etc/codex/conf/local.inc' '%sys_win_domain%' " " 
substitute '../etc/codex/conf/local.inc' '\/usr\/share\/codex' "$codendi_dir/trunk"
substitute '../etc/codex/conf/local.inc' '\/var\/lib\/codex' "$codendi_dir/var/lib/codex"
substitute '../etc/codex/conf/local.inc' '\/var\/log\/codex' "$codendi_dir/var/log/codex"
substitute '../etc/codex/conf/local.inc' '\/etc\/codex' "$codendi_dir/etc/codex"
substitute '../etc/codex/conf/local.inc' '\/usr\/lib\/codex\/bin' "$codendi_dir/etc/codex"
substitute '../etc/codex/conf/local.inc' '^\$sys_https_host ' "// \\\$sys_https_host"
substitute '../etc/codex/conf/local.inc' '\/usr\/share\/htmlpurifier' "/usr/share/htmlpurifier"
substitute '../etc/codex/conf/local.inc' '\/usr\/share\/jpgraph' "/usr/share/jpgraph"
substitute '../etc/codex/conf/local.inc' '\/var\/tmp' "$codendi_dir/var/tmp"

# Set environment var CODEX_LOCAL_INC
export CODEX_LOCAL_INC="$WORKSPACE/etc/codex/conf/local.inc"

# Create a symbolic link from plugins/tests to codex_tools/tests
cd $WORKSPACE/trunk/plugins/
ln -sf ../codex_tools/plugins/tests
cd tests/www/

# Execute the Tests
# This will produce a "JUnit like" test result file named codendi_unit_tests_report.xml that Hudson can use to produce test results.
php -d include_path="$WORKSPACE/trunk/src/www/include:$WORKSPACE/trunk/src:." -d memory_limit=196M test_all.php


#################
# DOCUMENTATION #
#################

BASEDIR="$WORKSPACE/trunk/documentation"

DOCBOOK_LIBS_HOME="/usr/share/hudson/share"
SAXON_HOME="$DOCBOOK_LIBS_HOME/saxon/"
FOP_HOME="$DOCBOOK_LIBS_HOME/fop"
JIMI_HOME="$DOCBOOK_LIBS_HOME/jimi"

export SAXON_HOME
export FOP_HOME
export JIMI_HOME
export BASEDIR

ln -sf /usr/share/hudson/share/docbook-dtd /usr/local/docbook-dtd
ln -sf /usr/share/hudson/share/docbook-xsl /usr/local/docbook-xsl
ln -sf /usr/share/hudson/share/fop /usr/local/fop
ln -sf /usr/share/hudson/share/jimi /usr/local/jimi
ln -sf /usr/share/hudson/share/saxon /usr/local/saxon

# Create /etc/codex/documentation/user_guide/xml/ and /etc/codex/documentation/cli/xml/ dir
mkdir -p /etc/codex/
mkdir -p /etc/codex/documentation/
mkdir -p /etc/codex/documentation/user_guide/
mkdir -p /etc/codex/documentation/user_guide/xml/

mkdir -p /etc/codex/documentation/cli/
mkdir -p /etc/codex/documentation/cli/xml/

# Copy dist files to /etc/codex/documentation
cp $WORKSPACE/trunk/src/etc/ParametersLocal.dtd.dist /etc/codex/documentation/user_guide/xml/ParametersLocal.dtd
cp $WORKSPACE/trunk/src/etc/ParametersLocal.cli.dtd.dist /etc/codex/documentation/cli/xml/ParametersLocal.dtd

# Substitute dist values by correct ones
substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_long_org_name%' "$sys_long_org_name" 

substitute '/etc/codex/documentation/cli/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 


# Generate the documentation (User Guide, CLI and programmer's guide)
sh $WORKSPACE/trunk/src/utils/generate_doc.sh -v
sh $WORKSPACE/trunk/src/utils/generate_cli_doc.sh -v
sh $WORKSPACE/trunk/src/utils/generate_programmer_doc.sh -v
