#!/bin/sh

#
# CI build: build Codendi project on a Continuous Integration server
# Usage: sh ci_build.sh <sys_default_domain> <sys_ip_address>
# Note: WORKSPACE is a variable of Hudson
#

sys_default_domain=$1;
sys_ip_address=$2;
local_module_directory=$3;
port="80";
sys_org_name="Xerox";
sys_long_org_name="Xerox Corporation";
codendi_dir="$WORKSPACE";

cd $WORKSPACE/$local_module_directory/

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  perl -pi -e "s/$2/$replacement/g" $1
}

# Create /var/tmp/codendi_cache dir
mkdir -p ../var/
mkdir -p ../var/tmp/
mkdir -p ../var/tmp/codendi_cache/

# Create /etc/codendi/conf and /etc/codendi/plugins/IM/etc dir
mkdir -p ../etc/
mkdir -p ../etc/codendi/
mkdir -p ../etc/codendi/conf/
mkdir -p ../etc/codendi/plugins/
mkdir -p ../etc/codendi/plugins/IM/
mkdir -p ../etc/codendi/plugins/IM/etc/

# Copy dist files to etc dir
cp src/etc/database.inc.dist ../etc/codendi/conf/database.inc
cp plugins/IM/include/jabbex_api/installation/resources/jabbex_conf.tpl.xml ../etc/codendi/plugins/IM/etc/jabbex_conf.xml
cp src/etc/local.inc.dist ../etc/codendi/conf/local.inc

# Substitute dist values by correct ones
substitute '../etc/codendi/conf/local.inc' '%sys_default_domain%' "$sys_default_domain:$port" 
substitute '../etc/codendi/conf/local.inc' '%sys_ldap_server%' " " 
substitute '../etc/codendi/conf/local.inc' '%sys_org_name%' "Xerox" 
substitute '../etc/codendi/conf/local.inc' '%sys_long_org_name%' "Xerox Corp" 
substitute '../etc/codendi/conf/local.inc' '%sys_fullname%' "$sys_default_domain" 
substitute '../etc/codendi/conf/local.inc' '%sys_win_domain%' " " 
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/codendi' "$codendi_dir/$local_module_directory"
substitute '../etc/codendi/conf/local.inc' '\/var\/lib\/codendi' "$codendi_dir/var/lib/codendi"
substitute '../etc/codendi/conf/local.inc' '\/var\/log\/codendi' "$codendi_dir/var/log/codendi"
substitute '../etc/codendi/conf/local.inc' '\/etc\/codendi' "$codendi_dir/etc/codendi"
substitute '../etc/codendi/conf/local.inc' '\/usr\/lib\/codendi\/bin' "$codendi_dir/etc/codendi"
substitute '../etc/codendi/conf/local.inc' '^\$sys_https_host ' "// \\\$sys_https_host"
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/htmlpurifier' "/usr/share/htmlpurifier"
substitute '../etc/codendi/conf/local.inc' '\/usr\/share\/jpgraph' "/usr/share/jpgraph"
substitute '../etc/codendi/conf/local.inc' '\/var\/tmp' "$codendi_dir/var/tmp"

# Set environment var CODENDI_LOCAL_INC
export CODENDI_LOCAL_INC="$WORKSPACE/etc/codendi/conf/local.inc"

# Create a symbolic link from plugins/tests to codendi_tools/tests
cd $WORKSPACE/$local_module_directory/plugins/
ln -sf ../codendi_tools/plugins/tests
cd tests/www/

# Execute the Tests
# This will produce a "JUnit like" test result file named codendi_unit_tests_report.xml that Hudson can use to produce test results.
php -d include_path="$WORKSPACE/$local_module_directory/src/www/include:$WORKSPACE/$local_module_directory/src:." -d memory_limit=196M test_all.php -r

# Checkstyle
php -d memory_limit=256M /usr/bin/phpcs --standard=$WORKSPACE/$local_module_directory/codendi_tools/utils/phpcs/Codendi $WORKSPACE/$local_module_directory/src/common/chart $WORKSPACE/$local_module_directory/src/common/backend --report=checkstyle -n --ignore=*/phpwiki/* > $WORKSPACE/var/tmp/checkstyle.xml 

#################
# DOCUMENTATION #
#################

BASEDIR="$WORKSPACE/$local_module_directory/documentation"

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

# Create /etc/codendi/documentation/user_guide/xml/ and /etc/codendi/documentation/cli/xml/ dir
mkdir -p /etc/codendi/
mkdir -p /etc/codendi/documentation/
mkdir -p /etc/codendi/documentation/user_guide/
mkdir -p /etc/codendi/documentation/user_guide/xml/

mkdir -p /etc/codendi/documentation/cli/
mkdir -p /etc/codendi/documentation/cli/xml/

# Copy dist files to /etc/codendi/documentation
cp $WORKSPACE/$local_module_directory/src/etc/ParametersLocal.dtd.dist /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd
cp $WORKSPACE/$local_module_directory/src/etc/ParametersLocal.cli.dtd.dist /etc/codendi/documentation/cli/xml/ParametersLocal.dtd

# Substitute dist values by correct ones
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_long_org_name%' "$sys_long_org_name" 

substitute '/etc/codendi/documentation/cli/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 


# Generate the documentation (User Guide, CLI and programmer's guide)
sh $WORKSPACE/$local_module_directory/src/utils/generate_doc.sh -v
sh $WORKSPACE/$local_module_directory/src/utils/generate_cli_doc.sh -v
sh $WORKSPACE/$local_module_directory/src/utils/generate_programmer_doc.sh -v
