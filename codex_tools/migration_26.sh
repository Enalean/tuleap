#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# THIS FILE IS THE PROPERTY OF XEROX AND IS ONLY DISTRIBUTED WITH A
# COMMERCIAL LICENSE OF CODEX. IT IS *NOT* DISTRIBUTED UNDER THE GNU
# PUBLIC LICENSE.
#
#  $Id: migration_24.sh 1776 2005-06-23 14:29:05Z guerin $
#
#      Originally written by Laurent Julliard 2004, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be placed at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.4 to CodeX 2.6
#


progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd -
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/tmp/todo_codex_migration.txt
CODEX_TOPDIRS="SF site-content documentation cgi-bin codex_tools"
INSTALL_DIR="/home/httpd"

# path to command line tools
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
USERADD='/usr/sbin/useradd'
USERDEL='/usr/sbin/userdel'
USERMOD='/usr/sbin/usermod'
MV='/bin/mv'
CP='/bin/cp'
LN='/bin/ln'
LS='/bin/ls'
RM='/bin/rm'
TAR='/bin/tar'
MKDIR='/bin/mkdir'
RPM='/bin/rpm'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
MYSQL='/usr/bin/mysql'
TOUCH='/bin/touch'
CAT='/bin/cat'
MAKE='/usr/bin/make'
TAIL='/usr/bin/tail'
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'

CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL"

# Functions
create_group() {
    # $1: groupname, $2: groupid
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -g "$2" "$1"
}

build_dir() {
    # $1: dir path, $2: user, $3: group, $4: permission
    $MKDIR -p "$1" 2>/dev/null; $CHOWN "$2.$3" "$1";$CHMOD "$4" "$1";
}

make_backup() {
    # $1: file name, $2: extension for old file (optional)
    file="$1"
    ext="$2"
    if [ -z $ext ]; then
	ext="nocodex"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && $CP "$file" "$backup_file"
}

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

die() {
  # $1: message to prompt before exiting
  echo -e "**ERROR** $1"; exit 1
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  $PERL -pi -e "s/$2/$3/g" $1
}

##############################################
# CodeX 2.4 to 2.6 migration
##############################################


##############################################
# Check the machine is running CodeX 2.4
#
OLD_CX_RELEASE='2.4'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/SF/www/VERSION
if [ $? -ne 0 ]; then
    $CAT <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found CodeX ${OLD_CX_RELEASE} installed... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi


##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Warn user about upgrade
#

yn="y"
$CAT <<EOF
This script will reinstall some configuration files:
/etc/httpd/conf/httpd.conf
/etc/httpd/conf.d/php.conf
If you have customized these files for your special needs, you should backup them before starting the migration.

EOF
read -p "Continue? [yn]: " yn

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check we are running on RHEL 3
#
RH_RELEASE="3"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Running on RedHat Enterprise Linux ${RH_RELEASE}... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

$RM -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX MIGRATION (see $TODO_FILE)"

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, apache and httpd, sendmail, and postfix ..."
$SERVICE crond stop
$SERVICE apache stop
$SERVICE httpd stop
$SERVICE mysql stop
$SERVICE sendmail stop
$SERVICE postfix stop
$SERVICE mailman stop


##############################################
# Check Required Stock RedHat RPMs are installed
# (note: gcc is required to recompile mailman)
#

# Removed: see install script for required RPMs. No new RPM needed for upgrade

##############################################
# Ask for domain name and other installation parameters
#
read -p "CodeX Domain name: " sys_default_domain
read -p "Codex Server IP address: " sys_ip_address

##############################################

##############################################
# Update local.inc
#
make_backup /etc/codex/conf/local.inc codex24

#TODO

##############################################
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

#TODO

##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_24
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex24
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"

# New directories
#TODO: déplacer les themes
build_dir /etc/codex/themes/messages sourceforge sourceforge 755

# Re-copy phpMyAdmin and viewcvs installations
$CP -af /home/httpd_24/phpMyAdmin* /home/httpd
$CP -af /home/httpd_24/cgi-bin/viewcvs.cgi /home/httpd/cgi-bin

#############################################
# Copy new icons in all custom themes



##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

$SERVICE mysql start
sleep 5

pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

$CAT <<EOF | $MYSQL $pass_opt sourceforge

-- Plugin tables
-- {{{
CREATE TABLE `priority_plugin_hook` (
`plugin_id` INT NOT NULL,
`hook` VARCHAR(100) NOT NULL,
`priority` INT NOT NULL
);
CREATE TABLE `project_plugin` (
`project_id` INT NOT NULL ,
`plugin_id` INT NOT NULL
);
CREATE TABLE `user_plugin` (
`user_id` INT NOT NULL ,
`plugin_id` INT NOT NULL
);

-- }}}


EOF




##############################################
# Reinstall modified shell scripts
#
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl
$CHMOD u+s /usr/local/bin/commit-email.pl

$CP $INSTALL_DIR/SF/utils/cvs1/log_accum /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/log_accum
$CHMOD 775 /usr/local/bin/log_accum
$CHMOD u+s /usr/local/bin/log_accum

# should have been done before but...
$CP $INSTALL_DIR/SF/utils/cvs1/cvssh-restricted /usr/local/bin


##############################################
# Check for the existence of the following customized content files and advise the person in charge of the installation of the new .tab files to customize to obtain the same effect
#

service="account"
file_exist=0
for sitefile in register_confirmation.txt register_needs_approval.txt \
    register_email.txt register_purpose.txt register_login.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="homepage"
file_exist=0
for sitefile in staff.txt thanks.txt welcome_intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="my"
file_exist=0
for sitefile in intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

# other modified files
file_exist=0
for sitefile in contact/contact.txt \
cvs/intro.txt \
file/editrelease_attach_file.txt \
file/qrs_attach_file.txt \
include/new_project_email.txt \
include/restricted_user_permissions.txt \
layout/footer.txt \
others/default_page.php \
register/complete.txt \
register/intro.txt \
register/license.txt \
register/projectname.txt \
register/registration.txt \
register/tos.txt \
svn/intro.txt \
tos/privacy.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above have change in CodeX 2.6. Please check that your customized files are still up-to-date."
fi


##############################################
# Restarting some services
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start


##############################################
# Generate Documentation
#
echo "Updating the User Manual. This might take a few minutes."
todo "Make sure that the CVS update is possible in /home/httpd/SF/utils/utils/generate_doc.sh. Do a cvs login on CVS server as user 'sourceforge'."
/home/httpd/SF/utils/generate_doc.sh -f
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation


##############################################
# Make sure all major services are on
#
$CHKCONFIG named on
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysql on
$CHKCONFIG cvs on
$CHKCONFIG mailman on

##############################################
# More things to do


# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 1;
