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
#  $Id$
#
#      Originally written by Laurent Julliard 2004, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be place at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.0 to CodeX 2.2
#


#############################################
# List of files to restore from an existing CodeX 2.0 installation
#

# System files for user/groups (please restore before CodeX upgrade, then reboot)
# ----------------------------
#/etc/passwd
#/etc/shadow
#/etc/groups
#
# Data Directories (please restore before CodeX upgrade)
# ----------------
#/home/httpd /home/users /home/groups /home/dummy /home/ftp /home/large_tmp /home/log /home/mailman /home/sfcache /home/tools /home/var /home/mailman (may link to /usr/local/mailman)
# ...You may simply restore /home/* !
#/cvsroot 
#/var/lib/mysql (may link to /home/var/lib/mysql)
#
# Check that Mailman is in /home/mailman, and that there is a directory, or a link, at /var/lib/mysql
#
# Other files to restore systematically (CodeX specific)
# -------------------------------------
#/etc/aliases.codex
#/etc/cvs_root_allow
#/etc/motd.inc
#/etc/profile_codex
#/etc/skel_codex 
#/etc/smrsh/
#/etc/local.inc
#/usr/local/domain/data/primary/
#
#Files to restore carefully by hand before upgrade:
#--------------------------------------------------
#/etc/named.conf
#/etc/aliases
#

#############################################
# Other files that _don't need_ to be restored
#
# No need to restore (but not overwritten)
# ------------------
#/var/spool/cron/
#/etc/my.cnf
#/usr/local/bin/*
#/etc/httpd/conf/httpd.conf (backed up)
#/etc/profile
#
#Overwritten (and backed up) by upgrade script: (no need to restore)
#----------------------------------------------
#/etc/ftpaccess
#/etc/smrsh/gotohell
#/etc/smrsh/mailman
#/etc/sysconfig/i18n
#/etc/xinetd.d/cvs
#/etc/xinetd.d/wu-ftpd
#
#Deprecated files (please delete... or move to a backup dir :-)
#----------------
#/etc/sendmail.cf
#/etc/sendmail.cw



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
# CodeX 2.0 to 2.2 migration
##############################################


##############################################
# Check the machine is running CodeX 2.0
#
OLD_CX_RELEASE='2.0'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/SF/www/VERSION
if [ $? -eq 1 ]; then
    cat <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found CodeX ${OLD_CX_RELEASE} installed... good!"
fi

[ "$yn" != "y" ] && (echo "Bye now!"; exit 1;)


##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Check we are running on RHEL 3 ES
#
RH_RELEASE="3ES"
yn="y"
$RPM -q redhat-release-${RH_RELEASE} 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Running on RedHat ${RH_RELEASE}... good!"
fi

[ "$yn" != "y" ] && (echo "Bye now!"; exit 1;)

$RM -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX MIGRATION (see $TODO_FILE)"

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, apache and httpd, sendmail, and postfix ..."
$SERVICE crond stop
$SERVICE apache stop
$SERVICE httpd stop
$SERVICE sendmail stop
$SERVICE postfix stop

##############################################
# Check Required Stock RedHat RPMs are installed
# (note: gcc is required to recompile mailman)
#
rpms_ok=1
for rpm in openssh-server openssh openssh-clients openssh-askpass \
   openssl openldap perl perl-DBI perl-CGI gd gcc \
   sendmail telnet bind ntp samba python php php-mysql php-ldap enscript \
   bind python-devel rcs
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ $? -eq 1 ]; then
	rpms_ok=0
	missing_rpms="$missing_rpms $rpm"
    fi
done
if [ $rpms_ok -eq 0 ]; then
    msg="The following Redhat Linux RPMs must be installed first:\n"
    msg="${msg}$missing_rpms\n"
    msg="${msg}Get them from your Redhat CDROM or FTP site, install them and re-run the installation script"
    die "$msg"
fi
echo "All requested RedHat RPMS installed... good!"

##############################################
# Ask for domain name and other installation parameters
#
read -p "CodeX Domain name: " sys_default_domain
read -p "Codex Server IP address: " sys_ip_address

##############################################
# Change user id for mailman. Used to be 105 on 
# CodeX 2.0/RH 7.3 but it collides with mysql user on 
# CodeX 2.2/RHEL ES 3
#
todo "- Check user id for mailman and mysql (mailman used to be 105, but it collides with mysql user on RHES3). It might not be a problem though!"

##############################################
# Create new directory structure for configuration and
# customization items
#
echo "Creating new directories for CodeX 2.2..."

build_dir /etc/codex sourceforge sourceforge 755
build_dir /etc/codex/conf sourceforge sourceforge 755
build_dir /etc/codex/documentation sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide/xml sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide/xml/en_US sourceforge sourceforge 755
build_dir /etc/codex/themes sourceforge sourceforge 755
build_dir /etc/codex/themes/css sourceforge sourceforge 755
build_dir /etc/codex/themes/images sourceforge sourceforge 755
build_dir /svnroot sourceforge sourceforge 755

build_dir /home/ftp/codex/DELETED sourceforge sourceforge 755

# Move configuration file
$MV /etc/local.inc /etc/codex/conf/local.inc

# Add $sys_win_domain to configuration file
$PERL -i'.orig' -p -e's:(// Part II.*paths$):// Windows Workgroup CodeX belog to (Samba)\n\$sys_win_domain = "YOURDOMAIN";\n\n\1:' /etc/codex/conf/local.inc
todo "- Customize the sys_win_domain parameter in /etc/codex/conf/local.inc"
# Update one line that is not compatible with Python
$PERL -i'.orig2' -p -e's:(sys_themeroot.*)\$.*(themes):\1"/home/httpd/SF/www/\2:' /etc/codex/conf/local.inc

######
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

# -> wu-ftpd
echo "Removing existing wu-ftp daemon.."
$RPM -e --nodeps wu-ftpd 2>/dev/null
echo "Installing wu-ftpd..."
cd ${RPMS_DIR}/wu-ftpd
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/wu-ftpd*.i386.rpm

# -> perlsuid
echo "Removing Perl suid if any..."
$RPM -e --nodeps perl-suidperl 2>/dev/null
echo "Installing Perl suid..."
cd ${RPMS_DIR}/perl-suidperl
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-suidperl*.i386.rpm

# -> Perl DBD for MySQL
echo "Removing Redhat Perl DBD MySQL if any..."
$RPM -e --nodeps perl-DBD-MySQL 2>/dev/null
echo "Installing Perl DBD MySQL..."
cd ${RPMS_DIR}/perl-dbd-mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-DBD-MySQL-*.i386.rpm

# -> mysql
echo "Removing existing MySQL..."
$SERVICE mysql stop 2>/dev/null
sleep 2
[ -e /usr/bin/mysqladmin ] && /usr/bin/mysqladmin shutdown 2>/dev/null
sleep 2
$RPM -e --nodeps MySQL MySQL-client MySQL-shared mysql-bench MySQL-bench MySQL-devel MySQL-server 2>/dev/null
echo "Installing MySQL RPMs for CodeX...."
cd ${RPMS_DIR}/mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/MySQL-*.i386.rpm
$SERVICE mysql start
$CHKCONFIG mysql on

# -> mysql module for Python
echo "Removing existing MySQL module for Python..."
$RPM -e --nodeps MySQL-python 2>/dev/null
echo "Installing Python MySQL module RPM for CodeX...."
cd ${RPMS_DIR}/mysql-python
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/MySQL-python-*.i386.rpm

# -> apache
echo "Removing existing Apache..."
#$SERVICE httpd stop
$RPM -e --nodeps apache apache-devel apache-manual httpd httpd-devel httpd-manual 2>/dev/null
$RPM -e --nodeps 'apr*' apr-util mod_ssl db4-devel db4-utils 2>/dev/null
echo "Installing Apache RPMs for CodeX...."
cd ${RPMS_DIR}/apache
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/db42-4.*.i386.rpm ${newest_rpm}/db42-utils*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/apr-0.*.i386.rpm ${newest_rpm}/apr-util-0.*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/httpd-2*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/mod_ssl-*.i386.rpm
$CHKCONFIG httpd on
# restart Apache after subversion installation - see below

# -> jre
echo "Removing RedHat Java JRE..."
$RPM -e --nodeps jre j2re 2>/dev/null
echo "Installing Java JRE RPMs for CodeX...."
cd ${RPMS_DIR}/jre
newest_rpm=`$LS -1 -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/j2re-*i?86.rpm
cd /usr/java
newest_jre=`$LS -1d j2re* | $TAIL -1`
$LN -sf $newest_jre jre

# -> cvs
echo "Removing existing CVS .."
$RPM -e --nodeps cvs 2>/dev/null
echo "Installing CVS RPMs for CodeX...."
cd ${RPMS_DIR}/cvs
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/cvs-*.i386.rpm

# -> subversion
echo "Removing existing subversion .."
$RPM -e --nodeps `rpm -qa 'subversion*' 'swig*' 'neon*' 'rapidsvn*'` 2>/dev/null
echo "Installing Subversion RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/neon-0*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/swig-1*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-1.*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-server*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-python*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-tools*.i386.rpm

# Create an empty codex_svnhosts.conf file
$TOUCH /etc/httpd/conf/codex_svnhosts.conf

# Restart Apache after subversion is installed
# so that mod_dav_svn module is taken into account
$SERVICE httpd start

# -> cvsgraph
$RPM -e --nodeps cvsgraph 2>/dev/null
echo "Installing cvsgraph RPM for CodeX...."
cd ${RPMS_DIR}/cvsgraph
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/cvsgraph-*i?86.rpm

# Create an http password file
$TOUCH /etc/httpd/conf/codex_htpasswd
$CHOWN sourceforge.sourceforge /etc/httpd/conf/codex_htpasswd
$CHMOD 775 /etc/httpd/conf/codex_htpasswd

# perl-Crypt-SmbHash needed by gensmbpasswd.pl
echo "Removing existing perl-Crypt-SmbHash..."
$RPM -e --nodeps perl-Crypt-SmbHash 2>/dev/null
echo "Installing perl-Crypt-SmbHash..."
cd ${RPMS_DIR}/perl-Crypt-SmbHash
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-Crypt-SmbHash*.noarch.rpm

######
# Now install the non RPMs stuff 
#

# -> saxon
echo "Installing Saxon...."
cd /usr/local
$RM -rf saxon*
$TAR xfz ${nonRPMS_DIR}/docbook/saxon-*.tgz
dir_entry=`$LS -1d saxon-*`
$LN -sf ${dir_entry} saxon

# -> fop
echo "Installing FOP...."
cd /usr/local
$RM -rf fop*
$TAR xfz ${nonRPMS_DIR}/docbook/fop-*.tgz
dir_entry=`$LS -1d fop-*`
$LN -sf ${dir_entry} fop

# -> Jimi
echo "Installing Jimi...."
cd /usr/local
$RM -rf [jJ]imi*
$TAR xfz ${nonRPMS_DIR}/docbook/Jimi-*.tgz
dir_entry=`$LS -1d [jJ]imi-*`
$LN -sf ${dir_entry} jimi

# -> Docbook DTD
echo "Installing DocBook DTD...."
cd /usr/local
$RM -rf docbook-dtd*
$TAR xfz ${nonRPMS_DIR}/docbook/docbook-dtd-*.tgz
dir_entry=`$LS -1d docbook-dtd-*`
$LN -sf ${dir_entry} docbook-dtd

# -> Docbook XSL
echo "Installing DocBook XSL...."
cd /usr/local
$RM -rf docbook-xsl*
$TAR xfz ${nonRPMS_DIR}/docbook/docbook-xsl-*.tgz
dir_entry=`$LS -1d docbook-xsl-*`
$LN -sf ${dir_entry} docbook-xsl


##############################################
# Now install various precompiled utilities
#
cd ${nonRPMS_DIR}/utilities
for f in *
do
  $CP -a $f /usr/local/bin
  $CHOWN sourceforge.sourceforge /usr/local/bin/$f
done
$CHOWN root.root /usr/local/bin/fileforge
$CHMOD u+s /usr/local/bin/fileforge
$CHOWN root.root /usr/local/bin/tmpfilemove
$CHMOD u+s /usr/local/bin/tmpfilemove


##############################################
# Move DNS config files
#

if [ -e /usr/local/domain/data/primary/codex.zone ]; then
    $MV /usr/local/domain/data/primary/codex.zone /var/named
    $MV /usr/local/domain/data/primary/codex_full.zone /var/named
    $MV /usr/local/domain /tmp # for future manual deletion

    # Add svn server aliases to DNS config file.
    $PERL -i'.orig' -p -e's:(cvs1\s*IN\s*CNAME\s*)(.*)$:\1\2\nsvn                             IN      CNAME   \2\nsvn1                            IN      CNAME   \2:' /var/named/codex.zone

else
    todo "- Could not find codex.zone DNS file. Please move files by hand to /var/named"
    todo "  Also insert svn and svn1 aliases in the codex.zone file"
fi
if [ -e /etc/named.conf ]; then
    $PERL -i'.orig' -p -e's:/usr/local/domain/data/primary:/var/named:' /etc/named.conf
fi
todo "- Make sure that the directory defined in /etc/named.conf (/var/named?) contains the codex.zone file and the DNS cache file"



##############################################
# Move custom and config files to new places
#
echo "Move custom and configuration files to /etc/codex/ directory. Some files may not exist."

$MV -f $INSTALL_DIR/site-content/custom /etc/codex/site-content
$MV -f /etc/motd.inc /etc/codex/site-content/en_US/others/motd.txt
$MV -f $INSTALL_DIR/SF/www/css/custom/* /etc/codex/themes/css
$MV -f $INSTALL_DIR/SF/www/images/custom/* /etc/codex/themes/images
build_dir /tmp/custom root root 755
$MV $INSTALL_DIR/SF/www/css/custom /tmp/custom/css       # for future manual deletion
$MV $INSTALL_DIR/SF/www/images/custom /tmp/custom/images # for future manual deletion

$MV -f $INSTALL_DIR/documentation/user_guide/xml/en_US/ParametersLocal.dtd /etc/codex/documentation/user_guide/xml/en_US/

build_dir /etc/codex/site-content/en_US/others sourceforge sourceforge 755
# First, copy default page, then overwrite if a custom one exists
$CP $INSTALL_DIR/site-content/en_US/others/default_page.php /etc/codex/site-content/en_US/others
$MV -f $INSTALL_DIR/SF/utils/custom/default_page.php /etc/codex/site-content/en_US/others
$MV $INSTALL_DIR/SF/utils/custom /tmp/custom/utils  # for future manual deletion


##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_20
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex20
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/mailman.conf.dist /etc/httpd/conf/mailman.conf
$CP $INSTALL_DIR/SF/etc/ssl.conf.dist /etc/httpd/conf.d/ssl.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf
$CP $INSTALL_DIR/SF/etc/subversion.conf.dist /etc/httpd/conf.d/subversion.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

# replace string patterns in ssl.conf
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it"
todo "Edit the new /etc/httpd/conf.d/ssl.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/subversion.conf file and update it if needed"

# needed by newparse.pl
$TOUCH /etc/httpd/conf/htpasswd
$CHMOD 644 /etc/httpd/conf/htpasswd



##############################################
# replace gensmbpasswd with perl script
#
cd /usr/local/bin
$RM -f gensmbpasswd
$CP $INSTALL_DIR/SF/utils/gensmbpasswd.pl gensmbpasswd
$CHOWN sourceforge.sourceforge gensmbpasswd
$CHMOD 755 gensmbpasswd

##############################################
# Install viewcvs now that the /home/httpd directory will not move
echo "Removing installed viewcvs if any .."
$RPM -e --nodeps viewcvs 2>/dev/null
echo "Installing viewcvs RPM for CodeX...."
cd ${RPMS_DIR}/viewcvs
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/viewcvs-*.noarch.rpm

#############################################
# Because of directory structure change, specifically move one site-content file if it exists
if [ -d /etc/codex/site-content/en_US/project/ ]; then
    build_dir /etc/codex/site-content/en_US/file sourceforge sourceforge 755
    $MV -f /etc/codex/site-content/en_US/project/editrelease_attach_file.txt /etc/codex/site-content/en_US/file/
fi


#############################################
# Copy new icons in all custom themes
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/lock.png /etc/codex/themes/images/*/ic/
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/svn16b.png /etc/codex/themes/images/*/ic/
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/file.png /etc/codex/themes/images/*/ic/


##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

# Update privilege table for MySQL 4.0
echo "Updating privilege table: ignore errors"
/usr/bin/mysql_fix_privilege_tables $old_passwd
$SERVICE mysql restart
sleep 5 # wait a bit...

$CAT <<EOF | $MYSQL $pass_opt sourceforge
# delete foundry project type
DELETE FROM group_type where type_id=2;

# drop jobs related tables and fields
DROP TABLE IF EXISTS people_job, people_job_category, people_job_inventory, people_job_status;
ALTER TABLE stats_agr_project DROP COLUMN help_requests;
ALTER TABLE stats_project DROP COLUMN help_requests;
ALTER TABLE stats_project_tmp DROP COLUMN help_requests;

# add Subversion tables and fields
ALTER TABLE groups ADD COLUMN svn_box VARCHAR(20) NOT NULL DEFAULT 'svn1' AFTER cvs_box;
ALTER TABLE groups ADD COLUMN svn_tracker INT(11) NOT NULL DEFAULT '1';
ALTER TABLE groups ADD COLUMN svn_events_mailing_list VARCHAR(64) binary DEFAULT NULL;
ALTER TABLE groups ADD COLUMN svn_events_mailing_header VARCHAR(64) binary DEFAULT NULL;
ALTER TABLE groups ADD COLUMN svn_preamble TEXT NOT NULL;
ALTER TABLE stats_agr_project
  ADD COLUMN svn_commits     smallint(6) DEFAULT '0' NOT NULL,
  ADD COLUMN svn_adds        smallint(6) DEFAULT '0' NOT NULL,
  ADD COLUMN svn_deletes   smallint(6) DEFAULT '0' NOT NULL,
  ADD COLUMN svn_checkouts   smallint(6) DEFAULT '0' NOT NULL,
  ADD COLUMN svn_access_count       smallint(6) DEFAULT '0' NOT NULL;

ALTER TABLE stats_project
  ADD COLUMN svn_commits     smallint(6) DEFAULT '0' NOT NULL AFTER cvs_adds,
  ADD COLUMN svn_adds        smallint(6) DEFAULT '0' NOT NULL AFTER svn_commits,
  ADD COLUMN svn_deletes   smallint(6) DEFAULT '0' NOT NULL AFTER svn_adds,
  ADD COLUMN svn_checkouts   smallint(6) DEFAULT '0' NOT NULL AFTER svn_deletes,
  ADD COLUMN svn_access_count       smallint(6) DEFAULT '0' NOT NULL AFTER svn_checkouts;

CREATE TABLE group_svn_full_history (
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  svn_commits int(11) NOT NULL default '0',
  svn_adds int(11) NOT NULL default '0',
  svn_deletes int(11) NOT NULL default '0',
  svn_checkouts int(11) NOT NULL default '0',
  svn_access_count int(11) NOT NULL default '0',
  UNIQUE accessid (group_id,user_id,day),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (day)
);

CREATE TABLE svn_checkins (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  type enum('Change','Add','Delete'),
  commitid int(11) DEFAULT '0' NOT NULL,
  dirid int(11) DEFAULT '0' NOT NULL,
  fileid int(11) DEFAULT '0' NOT NULL,
  addedlines int(11) DEFAULT '999' NOT NULL,
  removedlines int(11) DEFAULT '999' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_checkins_idx (commitid,dirid,fileid),
  KEY dirid (dirid),
  KEY fileid (fileid)
);

CREATE TABLE svn_commits (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  repositoryid int(11) DEFAULT '0' NOT NULL,
  revision int(11) DEFAULT '0' NOT NULL,
  date int(11) NOT NULL default '0',
  whoid int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (id),
  UNIQUE uniq_commits_idx (repositoryid,revision),
  KEY whoid (whoid),
  FULLTEXT (description)
);

CREATE TABLE svn_dirs (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  dir varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_dir_idx (dir)
);

CREATE TABLE svn_files (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  file varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_file_idx (file)
);

CREATE TABLE svn_repositories (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  repository varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_repository_idx (repository)
);

CREATE TABLE svn_tracks ( 
  group_artifact_id int(11),
  tracker varchar(64) binary DEFAULT '' NOT NULL, 
  artifact_id int(11) NOT NULL, 
  commit_id int(11) NOT NULL
); 

--
-- ugroup table, used to store the description of groups of users (see also ugroup_user table)
--
CREATE TABLE ugroup (  
  ugroup_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  group_id int(11) NOT NULL,
  PRIMARY KEY  (ugroup_id)
);

--
-- Insert special ugroup values
--
-- Apart from the mandatory 'nobody', 'anonymous_users', 'registered_users', 'project_members' and  
-- 'project_admins', the table lists all possible roles in the 'User Permissions' matrix.

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (100, "nobody", "Empty Group", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (1, "anonymous_users", "Anonymous Users", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (2, "registered_users", "Registered CodeX Users", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (3, "project_members", "Project Members", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (4, "project_admins", "Project Administrators", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (10, "document_editor", "Document Editors", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (11, "file_manager_admin", "File Manager Administrators", 100);
--
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (12, "forum_moderator", "Forum Moderators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (13, "patch_tech", "Patch Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (14, "patch_admins", "Patch Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (15, "tracker_tech", "Tracker Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "tracker_admins", "Tracker Administrators", 100);
--
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (30, "legacy_sr_tech", "Support Request Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (31, "legacy_sr_admins", "Support Request Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (32, "legacy_task_tech", "Task Manager Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (33, "legacy_task_admins", "Task Manager Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (34, "legacy_bug_tech", "Bug Tracker Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (35, "legacy_bug_admins", "Bug Tracker Administrators", 100);


--
-- ugroup_user table
-- Contains the ugroup members (users)
--
CREATE TABLE ugroup_user (
  ugroup_id int(11) NOT NULL,
  user_id int(11) NOT NULL
);


--
-- permissions table, used to store specific access rights (for packages, releases, documentation, etc.)
--
CREATE TABLE permissions (
  permission_type text NOT NULL,
  object_id text NOT NULL,
  ugroup_id int(11) NOT NULL
);


--
-- permissions_values table, used to store the list of default ugroups available by permission_type.
-- ugroups are selected from the special ugroups, so their ID should be less than 100.
--
CREATE TABLE permissions_values (
  permission_type text NOT NULL,
  ugroup_id int(11) NOT NULL,
  is_default int(11) NOT NULL default '0'
);


---
--- Set permissions_values entries. These should normally be set at installation time only.
---
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PACKAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',11);

-- No default value for RELEASE_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',11);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('DOCUMENT_READ',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',10);




---
--- Add File Release Admin role in the user_group table
---
ALTER TABLE user_group ADD file_flags int(11) NOT NULL default '0';

-- And update existing project admins to File Release admins
UPDATE user_group SET file_flags='2' WHERE admin_flags='A';


---
--- Suppress useless table
---
DROP TABLE mailaliases;

---
--- Fix SR 97 on Partners 
--- (in tracker reports: in Submitted by field look after 
--- artifact_submitters instead of group_members)
--- 
UPDATE artifact_field SET value_function = 'artifact_submitters' WHERE field_name = 'submitted_by';

---
--- Fix SR 92 on Partners
--- (use default value for fields that are not shown on add)
---
UPDATE artifact_field SET default_value = '1' WHERE field_name = 'status_id';

---
--- Fix SR 90 on Partners
--- (cvs mail address field not limited on 64 characters)
---
ALTER TABLE groups CHANGE cvs_events_mailing_list cvs_events_mailing_list text NOT NULL;

--- NOT APPLIED YET
--- File service for future project should point to the new script
UPDATE service SET link='/file/showfiles.php?group_id=$group_id' where short_name='file';

EOF

# Update 'file' service for each project
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;

sub updateFileService {
  my ($group_id,$url) = @_;
  $sql= "UPDATE service SET link='$url' WHERE group_id='$group_id' AND short_name='file'";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();

  if (!$res) {
    print "Could not update service 'file' for group_id ". $group_id."\n";
    print "Please contact your CodeX support representative\n";
  }
}

## get existing values from the group table
$query = "select group_id FROM groups";
$c = $dbh->prepare($query);
$c->execute();

while (my ($group_id) = $c->fetchrow()) {
    if ($group_id == 100) {
	$url = "/file/showfiles.php?group_id=\$group_id";
    } else {
        $url = "/file/showfiles.php?group_id=$group_id";
    }
    updateFileService($group_id, $url);
}

exit;
EOF


# Add svn service entry for each project
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;

sub createService {
  my ($group_id,$label,$description,$short_name,$link,$is_active,$is_used,$scope,$rank) = @_;
  $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($group_id,'$label','$description','$short_name','$link',$is_active,$is_used,'$scope',$rank)";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();

  ## get the service_id from inserted_row
  if (!$res) {
    print "Could not create service ".$label.", for group_id ". $group_id."\n";
    print "Please contact your CodeX support representative\n";
  }
}

## delete all svn_entries if there are some
$query = "DELETE FROM service WHERE short_name='svn'";
$c = $dbh->prepare($query);
$c->execute();

## get existing values from the group table
$query = "select group_id FROM groups";
$c = $dbh->prepare($query);
$c->execute();

while (my ($group_id) = $c->fetchrow()) {
    if ($group_id == 100) {
	$url = "/svn/?group_id=\$group_id";
        $enabled = 1;
    } else {
        $url = "/svn/?group_id=$group_id";
        $enabled = 0;
    }
    createService($group_id, 'Subversion', 'Subversion Access', 'svn', $url, 1, $enabled, 'system', 135);
}

exit;
EOF


##############################################
# Installing phpMyAdmin
#
echo "Installing phpMyAdmin..."
cd $INSTALL_DIR
$RM -rf phpMyAdmin*
$TAR xfj ${nonRPMS_DIR}/phpMyAdmin/phpMyAdmin-*
dir_entry=`$LS -1d phpMyAdmin-*`
$LN -sf ${dir_entry} phpMyAdmin
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/phpMyAdmin*

export sys_default_domain
$PERL -i'.orig' -p - $INSTALL_DIR/phpMyAdmin/config.inc.php <<'EOF'
s/.*cfg\['PmaAbsoluteUri'\] =.*/\$cfg\['PmaAbsoluteUri'\] = 'http:\/\/$ENV{'sys_default_domain'}\/phpMyAdmin';/;
s/(.*Servers.*'auth_type'.*')config('.*)$/$1http$2/g;
s/(.*Servers.*'user'.*')root('.*)$/$1sourceforge$2/g;
s/(.*Servers.*'only_db'.*').*('.*)$/$1sourceforge$2/g;
EOF


#todo "Customize phpMyAdmin. Edit $INSTALL_DIR/phpMyAdmin/config.inc.php"
#todo "  - $cfg['PmaAbsoluteUri'] = 'http://$sys_default_domain/phpMyAdmin';"
#todo "  - $cfg['Servers'][$i]['auth_type']     = 'http'; "
#todo "  - $cfg['Servers'][$i]['user']          = 'sourceforge';"
#todo "  - $cfg['Servers'][$i]['only_db']       = 'sourceforge';";


##############################################
# Create my.cnf if missing
echo "Creating MySQL conf file..."

install_my_cnf=1;
if [ -a /etc/my.cnf ]; then
     install_my_cnf=0;
     todo "Check that /etc/my.cnf is up to date"
fi

if [ install_my_cnf -eq 1 ]; then 
  $CAT <<'EOF' >/etc/my.cnf
# The MySQL server
[mysqld]
log-bin=/cvsroot/.mysql_backup/codex-bin
skip-innodb
# file attachment can be 16M in size so take a bit of slack
# on the mysql packet size
set-variable = max_allowed_packet=24M

[safe_mysqld]
err-log=/var/log/mysqld.log
EOF
fi

##############################################
# Update Mailman from 2.0 to 2.1
#
echo "Updating mailman..."
echo "Removing installed mailman RPM if any .."
$RPM -e --nodeps mailman 2>/dev/null
MAILMAN_DIR="/home/mailman"
echo "Updating mailman in $MAILMAN_DIR..."
if [ ! -a $MAILMAN_DIR ]; then
    $CP -a /usr/local/mailman /home/mailman
fi
if [ ! -d $MAILMAN_DIR"_20" ]; then
    $CP -a /home/mailman /home/mailman_20
fi
build_dir /home/mailman mailman mailman 2775

# compile and install
$RM -rf /tmp/mailman; $MKDIR -p /tmp/mailman; cd /tmp/mailman;
#$RM -rf $MAILMAN_DIR/* Keep existing ML!
$TAR xfz $nonRPMS_DIR/mailman/mailman-*.tgz
newest_ver=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
cd $newest_ver
mail_gid=`id -g mail`
cgi_gid=`id -g sourceforge`
./configure --prefix=$MAILMAN_DIR --with-mail-gid=$mail_gid --with-cgi-gid=$cgi_gid
$MAKE install
# migration of existing mailing lists is done during install

$CHOWN -R mailman.mailman $MAILMAN_DIR
$CHMOD a+rx,g+ws $MAILMAN_DIR
$LN -sf $MAILMAN_DIR /usr/local/mailman # should not be necessary, but who knows...

# make sure permissions are OK
$MAILMAN_DIR/bin/check_perms -f
#... a second time!
$MAILMAN_DIR/bin/check_perms -f

# modify mailman crontab for mailman 2.1
#crontab -u mailman -l > /tmp/mailman_cronfile
#$PERL -i'.orig' -p -e's:(^.*mailman)/cron/qrunner\s*$:\1/bin/qrunner -o -r All\n:' /tmp/mailman_cronfile
# Mailman used to be in /local/mailman
#$PERL -i'.orig2' -p -e's:/usr/local/mailman:/home/mailman:' /tmp/mailman_cronfile
#crontab -u mailman /tmp/mailman_cronfile

# install service
$CP $MAILMAN_DIR/scripts/mailman /etc/init.d/mailman
$CHKCONFIG --add mailman

# Update Mailman config
install_mm_config=1;
if [ -a $MAILMAN_DIR/Mailman/mm_cfg.py ]; then
   grep DEFAULT_HOST_NAME $MAILMAN_DIR/Mailman/mm_cfg.py > /dev/null
   if [ $? -eq 0 ]; then
     install_mm_config=0;
     todo "Check $MAILMAN_DIR/Mailman/mm_cfg.py:\n\
you may replace DEFAULT_EMAIL_HOST and DEFAULT_URL_HOST by DEFAULT_HOST_NAME\n\
and DEFAULT_URL (see CodeX Installation Guide). Recompile with python -O mm_cfg.py"
   fi
fi

if [ $install_mm_config -eq 1 ]; then 
  $CAT <<EOF >> $MAILMAN_DIR/Mailman/mm_cfg.py
DEFAULT_EMAIL_HOST = 'lists.$sys_default_domain'
DEFAULT_URL_HOST = 'lists.$sys_default_domain'
add_virtualhost(DEFAULT_URL_HOST, DEFAULT_EMAIL_HOST)
EOF
  # Compile file
  `python -O $MAILMAN_DIR/Mailman/mm_cfg.py`
fi

todo "Create a site-wide mailing list: in $MAILMAN_DIR, type 'bin/newlist mailman', then 'bin/config_list -i data/sitelist.cfg mailman', and don't forget to subscribe to this ML."


##############################################
# Installing and configuring Sendmail
# RHEL 3 comes with sendmail 8.12, and conf files have moved to /etc/mail
echo "##############################################"
echo "Installing sendmail shell wrappers and configuring sendmail..."
cd /etc/smrsh
$RM -f  gotohell mailman
$LN -sf /usr/local/bin/gotohell
$LN -sf $MAILMAN_DIR/mail/mailman

$PERL -i'.orig' -p -e's:^O\s*AliasFile.*:O AliasFile=/etc/aliases,/etc/aliases.codex:' /etc/mail/sendmail.cf
cat <<EOF >/etc/mail/local-host-names
# local-host-names - include all aliases for your machine here.
$sys_default_domain
lists.$sys_default_domain
users.$sys_default_domain
EOF

todo "Finish sendmail settings (see installation Guide). "
todo "Check that codex-contact and codex-admin aliases are defined in /etc/aliases"

##############################################
# Install and Configure Subversion
#
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl

# Add sys_svn_host variable to local.inc
# and Documentation parameter file (ParametersLocal.dtd)
sys_svn_host="svn.$sys_default_domain"

$PERL -i'.orig' -p -e"s:(^\\\$sys_cvs_host.*):\1\n\n// Machine that hosts Subversion\n// Note that while this machine need not be the same as the CodeX host,\n// the \"viewcvs\" interface currently must run on the CodeX host system.\n// If this host is different from the CodeX host, then the CodeX host\n// will need to be able to access the CVS file tree via NFS.\n\\\$sys_svn_host = \"$sys_svn_host\";:" /etc/codex/conf/local.inc

$PERL -i'.orig' -p -e"s:(^<\!ENTITY SYS_CVS_HOST.*):\1\n<\!ENTITY SYS_SVN_HOST \"$sys_svn_host\":" /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd

##############################################
# replace all references to cvsweb.cgi to viewcvs.cgi in
# CVS loginfo files
#
for f in /cvsroot/*/CVSROOT/loginfo
do
    $PERL -i -p -e"s/cvsweb.cgi/viewcvs.cgi/g" $f
done


##############################################
#
# A few updates that may not be necessary in case of a real OS upgrade
# but that are useful in case of fresh install of OS.
# In any case, you should run these updates
#
##############################################
# CVS configuration
#
echo "Configuring the CVS server and CVS tracking tools..."
$TOUCH /etc/cvs_root_allow
$CHOWN sourceforge.sourceforge /etc/cvs_root_allow
$CHMOD 644 /etc/cvs_root_allow

make_backup "/etc/xinetd.d/cvs" codex20
$CAT <<'EOF' >/etc/xinetd.d/cvs
service cvspserver
{
        disable             = no
        socket_type         = stream
        protocol            = tcp
        wait                = no
        user                = root
        server              = /usr/bin/cvs
        server_args         = -f -z3 -T/home/large_tmp --allow-root-file=/etc/cvs_root_allow pserver
}
EOF

cd $INSTALL_DIR/SF/utils/cvs1
$CP log_accum /usr/local/bin
$CP commit_prep /usr/local/bin
cd /usr/local/bin
$CHOWN sourceforge.sourceforge log_accum commit_prep
$CHMOD 755 log_accum commit_prep
$CHMOD u+s log_accum   # sets the uid bit (-rwsr-xr-x)


##############################################
# Make the system daily cronjob run at 23:58pm
echo "Updating daily cron job in system crontab..."
$PERL -i'.orig' -p -e's/\d+ \d+ (.*daily)/58 23 \1/g' /etc/crontab

##############################################
# FTP server configuration
#
make_backup "/etc/xinetd.d/wu-ftpd" codex20
echo "Configuring FTP servers and directories..."
$CAT <<EOF >/etc/xinetd.d/wu-ftpd
service ftp
{
        disable = no
        socket_type             = stream
        wait                    = no
        user                    = root
        server                  = /usr/sbin/in.ftpd
        server_args             = -l -a
        log_on_success          += DURATION
        nice                    = 10
}
EOF

make_backup "/etc/ftpaccess"
$CAT <<EOF >/etc/ftpaccess
class   all   real,guest,anonymous  *
class anonftp anonymous *

upload /home/ftp * no
upload /home/ftp /bin no
upload /home/ftp /etc no
upload /home/ftp /lib no
noretrieve .notar
upload /home/ftp /incoming yes ftpadmin ftpadmin 0644 nodirs
noretrieve /home/ftp/incoming
noretrieve /home/ftp/codex

email root@localhost

loginfails 5

readme  README*    login
readme  README*    cwd=*

message /welcome.msg            login
message .message                cwd=*

compress        yes             all
tar             yes             all
chmod        no        guest,anonymous
delete        no        guest,anonymous
overwrite    no        guest,anonymous
rename        no        guest,anonymous

log transfers anonymous,real inbound,outbound

shutdown /etc/shutmsg

passwd-check rfc822 warn
EOF

##############################################
# Crontab configuration
#

# Install root crontab if it is not set
install_root_crontab=1;
if [ -a /var/spool/cron/root ]; then
   grep xerox_crontab /var/spool/cron/root > /dev/null
   if [ $? -eq 0 ]; then
     install_root_crontab=0;
     echo "root user crontab seems up to date..."
   fi
fi

if [ $install_root_crontab -eq 1 ]; then 
  echo "Installing root user crontab..."
  $CAT <<'EOF' >/tmp/cronfile
# run the Codex crontab script once every 2 hours
# this script synchronizes user, groups, cvs repo,
# directories, mailing lists, etc...
0 0-23/2 * * * /home/httpd/SF/utils/xerox_crontab.sh
#
# run the daily statistics script just a little bit after
# midnight so that it computes stats for the day before
# Run at 0:30 am
30 0 * * * /home/httpd/SF/utils/xerox_all_daily_stats.sh
#
# run the weekly stats for projects. Run it on Monday morning so that
# it computes the stats for the week before
# Run on Monday at 1am
0 1 * * Mon (cd /home/httpd/SF/utils/underworld-root; ./db_project_weekly_metric.pl)
#
# weekly backup preparation (mysql shutdown, file dump and restart)
45 0 * * Sun /home/tools/backup_job
#
# Delete all files in FTP incoming that are older than 2 weeks (336 hours)
#
0 3 * * * /usr/sbin/tmpwatch -m -f 336 /home/ftp/incoming
#
# It looks like we have memory leaks in Apache in some versions so restart it
# on Sunday. Do it while the DB is down for backup
50 0 * * Sun /etc/rc.d/init.d/httpd restart
#
# Once a minute make sure that the setuid bit is set on some critical files
* * * * * (cd /usr/local/bin; /bin/chmod u+s commit-email.pl log_accum tmpfilemove fileforge)
EOF
  crontab -u root /tmp/cronfile
fi


# Install sourceforge crontab if needed
install_sourceforge_crontab=1;
if [ -a /var/spool/cron/sourceforge ]; then
   grep generate_doc /var/spool/cron/sourceforge > /dev/null
   if [ $? -eq 0 ]; then
     install_sourceforge_crontab=0;
     echo "sourceforge user crontab seems up to date..."
   fi
fi

if [ $install_sourceforge_crontab -eq 1 ]; then 
  echo "Installing sourceforge user crontab..."
  $CAT <<'EOF' >/tmp/cronfile
# Re-generate the CodeX User Guide on a daily basis
00 03 * * * /home/httpd/SF/utils/generate_doc.sh -f
EOF
  crontab -u sourceforge /tmp/cronfile
fi


echo "Installing  mailman user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# At 8AM every day, mail reminders to admins as to pending requests.
# They are less likely to ignore these reminders if they're mailed
# early in the morning, but of course, this is local time... ;)
0 8 * * * /usr/bin/python -S /home/mailman/cron/checkdbs
#
# At 9AM, send notifications to disabled members that are due to be
# reminded to re-enable their accounts.
0 9 * * * /usr/bin/python -S /home/mailman/cron/disabled
#
# Noon, mail digests for lists that do periodic as well as threshhold delivery.
0 12 * * * /usr/bin/python -S /home/mailman/cron/senddigests
#
# 5 AM on the first of each month, mail out password reminders.
0 5 1 * * /usr/bin/python -S /home/mailman/cron/mailpasswds
#
# Every 5 mins, try to gate news to mail.  You can comment this one out
# if you don't want to allow gating, or don't have any going on right now,
# or want to exclusively use a callback strategy instead of polling.
#0,5,10,15,20,25,30,35,40,45,50,55 * * * * /usr/bin/python -S /home/mailman/cron/gate_news
#
# At 3:27am every night, regenerate the gzip'd archive file.  Only
# turn this on if the internal archiver is used and
# GZIP_ARCHIVE_TXT_FILES is false in mm_cfg.py
27 3 * * * /usr/bin/python -S /home/mailman/cron/nightly_gzip
EOF
crontab -u mailman /tmp/cronfile

##############################################
# Make ISO latin characters the default charset for the
# entire system instead of UTF-8
#
make_backup "/etc/sysconfig/i18n"
echo "Set ISO Latin as default system character set..."
$CAT <<'EOF' >/etc/sysconfig/i18n
LANG="en_US.iso885915"
SUPPORTED="en_US.iso885915:en_US:en"
SYSFONT="lat0-sun16"
SYSFONTACM="iso15"
EOF
$CHOWN root.root /etc/sysconfig/i18n
$CHMOD 644 /etc/sysconfig/i18n

##############################################
# Log Files rotation configuration
#
echo "Installing log files rotation..."
$RM -f /etc/logrotate.d/apache # v1.3 from RH7.3

$CAT <<'EOF' >/etc/logrotate.d/httpd
/var/log/httpd/access_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
        # LJ Added for Codex archiving
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/home/log/$year/$month"
     destfile="http_combined_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/access_log.1 $destdir/$destfile
    endscript
}
 
/var/log/httpd/vhosts-access_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
        # LJ Added for Codex archiving
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     server=`hostname`
     destdir="/home/log/$server/$year/$month"
     destfile="vhosts-access_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/vhosts-access_log.1 $destdir/$destfile
    endscript
}
                                                                              
/var/log/httpd/agent_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
    endscript
}
                                                                              
/var/log/httpd/error_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
    endscript
}

/var/log/httpd/referer_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
    endscript
}
                                                                               
/var/log/httpd/suexec_log {
    missingok
    # LJ
    daily
    rotate 4
    postrotate
        /usr/bin/killall -HUP httpd 2> /dev/null || true
    endscript
}
EOF
$CHOWN root.root /etc/logrotate.d/httpd
$CHMOD 644 /etc/logrotate.d/httpd


$CAT <<'EOF' >/etc/logrotate.d/ftpd
/var/log/xferlog {
    # ftpd doesn't handle SIGHUP properly
    nocompress
    # LJ Modified for codex
    daily
    postrotate
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/home/log/$year/$month"
     destfile="ftp_xferlog_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/xferlog.1 $destdir/$destfile
    endscript
}
EOF
$CHOWN root.root /etc/logrotate.d/ftpd
$CHMOD 644 /etc/logrotate.d/ftpd


# Remove useless files in logrotate.d that generate errors
$RM -rf /etc/logrotate.d/*.nocodex /etc/logrotate.d/*.rpmnew


#############################################
# Profile

# customize the global profile 
$GREP profile_codex /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' >>/etc/profile
# LJ Now the Part specific to CodeX users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_codex
fi
EOF


##############################################
# Restarting some services before upgrading
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd restart
$SERVICE sendmail start


##############################################
# Generate Documentation
#
echo "Updating the User Manual. This might take a few minutes."
/home/httpd/SF/utils/generate_doc.sh -f
todo "Edit utils/generate_doc.sh to make sure that the CVS update is possible. Do a cvs login on CVS server as user 'sourceforge'. Alternatively, add a '-f' flag in the 'sourceforge' crontab to force generation each night"

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
todo "Customize /etc/php.ini: "
todo "  - register_globals = On"
todo "  - memory_limit = 30M"
todo "  - post_max_size = 20M"
todo "  - upload_max_file_size = 20M"
#todo "  - include_path = .:$INSTALL_DIR/SF/www/include:$INSTALL_DIR/SF/www/phpMyAdmin"

# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 1;


cat <<EOF >/dev/null
When migrating a 2.0 site to 2.2 here are the things that must be done:

- DONE - update /etc/logrotate.d/httpd file with su_exec block
- DONE - create /etc/codex/(conf|themes|themes/css|themes/images|documentation|site-content)
- DONE - move /etc/local.inc /etc/codex/conf/local.inc
- DONE - add $sys_win_domain in /etc/codex/conf/local.inc
- upgrade mailman from 2.0 to 2.1
- DONE - change mailman crontab cron/qrunner becomes bin/qrunner -o -r All
- DONE - move codex.zone and codex_full.zone from /usr/local/domain/data/primary/ into /var/named
- DONE - Add an svn and svn1 alias in the /var/named/codex.zone file
- DONE - if $INSTALL_DIR/site-content/custom exists then move all files (and subdir) into /etc/codex/site-documentation
- DONE - if /etc/motd.inc exists move it into /etc/codex/site-content/en_US/others/motd.txt
- DONE - if $INSTALL_DIR/SF/www/css/custom exist then move all subdirs in /etc/codex/themes/css
- DONE - if $INSTALL_DIR/SF/www/images/custom exist then move all subdirs in /etc/codex/themes/images
- DONE - Copy SF/etc/httpd.conf.dist in /etc/httpd/conf/httpd.conf and make the necessary changes
- DONE - Copy SF/etc/ssl.conf.dist in /etc/httpd/conf.d/ssl.conf and make the necessary changes
- DONE - Copy SF/etc/php.conf.dist in /etc/httpd/conf.d/php.conf and make the necessary changes
- DONE - Copy $INSTALL_DIR/documentation/user_guide/xml/en_US/ParametersLocal.dtd to /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd (do a mkdir -p to create full path first)
- DONE - if SF/utils/custom/default_page.php exist then copy it to /etc/codex/site-content/en_US/others
- Faire un diff sur database_structure.sql and database_initvalues.sql avec la v 2.0 pour reverifier tous les chagement dans la base et les mettre dans le script d'upgrade.
- DONE - delete Foundry (type=2) from the group_type table
- DONE - delete tables people_job, people_job_category, people_job_inventory, people_job_status
- DONE - delete field help_requests from table stats_agr_project, stats_project, stats_project_tmp


-> FOR Subversion
=================
- DONE - Copy SF/etc/subversion.conf.dist in /etc/httpd/conf.d/subversion.conf and make the necessary changes
- DONE - Install all necessary RPMs for subversion:
Server: remove with nodeps db4-devel, db4-utils (db42-utils conflicts with native db4-utils-4.1.xx), install db42 , subversion-server, subversion, neon (use --nodeps because it requires apr-0.95 and httpd >=2.0.48 but those 2 conflict with the httpd packages)
install in one go httpd, mod_ssl, apr, apr-util
- DONE - create the /svnroot directory with perm and mod sourceforge.sourceforge 755
- DONE - touch /etc/httpd/conf/codex_htpasswd
- DONE - copy $INSTALL_DIR/SF/utils/svn/commit-email.pl to /usr/local/bin/ mod sourceforge.sourceforge 755
- DONE - ALTER groups table and create new fields in the group database use_svn, svn_box, svn_tracker svn_events_mailing_list svn_events_mailing_header svn_preamble
  ALTER TABLE groups ADD COLUMN svn_box VARCHAR(20) NOT NULL DEFAULT 'svn1' AFTER cvs_box;
  ALTER TABLE groups ADD COLUMN use_svn int(11) NOT NULL DEFAULT '1' AFTER use_cvs;
  ALTER TABLE groups ADD COLUMN svn_tracker INT(11) NOT NULL DEFAULT '1';
  ALTER TABLE groups ADD COLUMN svn_events_mailing_list VARCHAR(64) binary DEFAULT NULL;
  ALTER TABLE groups ADD COLUMN svn_events_mailing_header VARCHAR(64) binary DEFAULT NULL;
  ALTER TABLE groups ADD COLUMN svn_preamble TEXT NOT NULL;

- DONE - (Upgrade) CREATE table group_svn_history
- DONE - Modify Project.class and usesSvn
- DONE - (Upgrade) Add sys_svn_host to local.inc
- DONE - (Upgrade) Add SYS_SVN_HOST to /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd
- DONE - (Upgrade) Add a svn entry into the service table for each project. Set the svn service to disabled by default for all projects except project 100.
- DONE - (Upgrade) Create all svn_xxxxx tables for SVN commit tracking
- For mass update on trackers create table script_state

TODO in install script:
- DONE - Update httpd.conf with the version for Apache 2.0 from codex.xerox.com (and finish the cleanup in the file)
- DONE - Put %..% patterns in all *.dist files and change them at installation time
- DONE - Change AliasFile in /etc/mail/sendmail.cf at installation time
- DONE - Create /etc/mail/local-host-names at installation time
- DONE - change $INSTALL_DIR/SF/utils/underworld-dummy/mail_aliases.pl (wrapper is now called mailman - Installation file already updated)
- DONE - Substitute _DOMAIN_NAME_ in the database init file with the domain name before creating the database
- DONE - Update installation script with subversion installation (see upgrade notes above for installation)
- DONE touch /etc/httpd/conf/codex_htpasswd
- DONE copy $INSTALL_DIR/SF/utils/svn/commit-email.pl to /usr/local/bin/ mod sourceforge.sourceforge 755
- DONE Create new fields in the group database use_svn, svn_box, svn_tracker svn_events_mailing_list svn_events_mailing_header svn_preamble 

TODO
- DONE - Change /usr/local/domain/data/primary/ into /var/named (standard place)
- DONE - Change /etc/local.inc into /etc/codex/conf/local.inc in all source code
- DONE - Move motd.inc in site-content
- DONE - Move default_page.php in site-content and look for customized version in /etc/codex/site-content
- DONE - Add Include conf.d/subversion.conf in httpd.conf
- DONE - Add subversion.conf.dist in SF/etc

Notes:
- DONE - to create a new SVN repo in new_parse use mkdir /svnroot/codex; chmod 775 /svnroot/xxxxx; svnadmin create /svnroot/xxxxx; chown -R sourceforge.xxxxx /svnroot/xxxxx; 
- gensmbpasswd crashes because load_client_codepage: filename /usr/share/samba/codepages/codepage.850 is not in /usr/share/samba/codepages (to be investigated and fixed - gensmbpasswd must be recompiled against Smaba 3.0 because codepage are now handled through .so files)
- For Subversion
 . permission management in user management PHP script (read/write/none globally)
 . by directory permission (to be investigated)
 . mail notification hook to put in place
 . subversion query interface
 . integrate viewcvs with cvs and subversion

- For viewcvs
 . cvs checkout viewcvs 1.0-dev cvs -d:pserver:anonymous@cvs.sourceforge.net:/cvsroot/viewcvs co -d viewcvs-1.0 viewcvs
 . viewcvs-install and force a return on the install dir question
 . chown -R sourceforge.sourceforge /usr/local/viewcvs-*
 . cp -a /usr/local/viewcvs-x.y.z/cgi/viewcvs.cgi $INSTALL_DIR/cgi-bin/
 . Must install swig and subversion python binding 
rpm -Uvh ~/packages-rhel3/RPMS_CodeX/subversion/subversion-1.0.1/subversion-python-1.0.1-1.wbel3.i386.rpm ~/packages-rhel3/RPMS_CodeX/subversion/subversion-1.0.1/swig-1.3.19-2.i386.rpm
 . install enscript (make sure it is in the list of mandatory RedHat RPMs at insntallation - DONE)

Subversion Integration
=====================
This is development notes not migration

- Check all other places in source code where CVS is mentioned to see
what must be done to bring the same features for SVN as for CVS
(statistics, source code access logs, etc...)

- Problem with time. when changing time zone the time shown in the Web
query interface doesn't change.


EOF