#!/bin/bash
#
# Copyright (c) Xerox Corporation, Codendi 2001-2009.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
#      Originally written by Laurent Julliard 2004, Codendi Team, Xerox
#
#  This file is part of the Codendi software and must be place at the same
#  level as the Codendi, RPMS_Codendi and nonRPMS_Codendi directory when
#  delivered on a CD or by other means
#

# In order to keep a log of the installation, you may run the script with:
# ./codendi_install.sh 2>&1 | tee /tmp/codendi_install.log

progname=$0
#scriptdir=/mnt/cdrom
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd "${scriptdir}";TOP_DIR=`pwd`;cd - > /dev/null # redirect to /dev/null to remove display of folder (RHEL4 only)
RPMS_DIR="${TOP_DIR}/RPMS_Codendi"
nonRPMS_DIR="${TOP_DIR}/nonRPMS_Codendi"
Codendi_DIR="${TOP_DIR}/Codendi"
TODO_FILE=/root/todo_codendi.txt
export INSTALL_DIR="/usr/share/codendi"

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
CHGRP='/bin/chgrp'
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
DIFF='/usr/bin/diff'
PHP='/usr/bin/php'
UNAME='/bin/uname'
YUM='/usr/bin/yum -qy'

CHCON='/usr/bin/chcon'
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
$GREP -i -q '^SELINUX=disabled' /etc/selinux/config
if [ $? -eq 0 ] || [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] ; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi


CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL DIFF"

# Functions
create_group_withid() {
    # $1: groupname, $2: groupid
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -g "$2" "$1"
}

create_group() {
    # $1: groupname
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -r "$1"
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
	ext="nocodendi"
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
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

##############################################
# Codendi installation
##############################################

##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done



##############################################
# Detect architecture
$UNAME -m | $GREP -q x86_64
if [ $? -ne 0 ]; then
  ARCH=i386
else
  ARCH=x86_64
fi

##############################################
# Check we are running on RHEL 5.3 
# 5.3 is needed for openjdk. This will need to be updated when 5.4 is available!
#
RH_RELEASE="5"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* | grep 5-3 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
  $RPM -q centos-release-${RH_RELEASE}* | grep 5-3 2>/dev/null 1>&2
  if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE} or CentOS  ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [y|n]: " yn
  else
    echo "Running on CentOS ${RH_RELEASE}... good!"
  fi
else
    echo "Running on RedHat Enterprise Linux ${RH_RELEASE}... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

rm -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODENDI INSTALLATION (see $TODO_FILE)"


# Check if IM plugin is installed
enable_plugin_im="false"
if [ -d "$INSTALL_DIR/plugins/IM" ]; then
    enable_plugin_im="true"
fi


#####
# Codendi RPMS
#
# Those rpms requires codendiadm user so cannot be installed before codendi
# package as dependencies
#
# The right solution would be to add codendi as a dependency of those packages
#

# -> ViewVC
# Need codendiadm user & group
missing_rpms="$missing_rpms viewvc-1.0.7-1.codendi"

if [ ! -z "$missing_rpms" ]; then
    $YUM install $missing_rpms
fi
echo "All requested RedHat RPMS installed... good!"


echo
echo "Configuration questions"
echo

# Ask for domain name and other installation parameters
read -p "Codendi Domain name: " sys_default_domain
read -p "Your Company short name: " sys_org_name
read -p "Your Company long name: " sys_long_org_name
read -p "Codendi Server fully qualified machine name: " sys_fullname
read -p "Codendi Server IP address: " sys_ip_address
read -p "LDAP server name: " sys_ldap_server
read -p "Activate user shell accounts? [y|n]:" active_shell
read -p "Generate a self-signed SSL certificate to enable HTTPS support? [y|n]:" create_ssl_certificate
read -p "Disable sub-domain management (no DNS delegation)? [y|n]:" disable_subdomains

# Ask for user passwords
rt_passwd="a"; rt_passwd2="b";
while [ "$rt_passwd" != "$rt_passwd2" ]; do
    read -s -p "Password for MySQL root: " rt_passwd
    echo
    read -s -p "Retype MySQL root password: " rt_passwd2
    echo
done

codendiadm_passwd="a"; codendiadm_passwd2="b";
while [ "$codendiadm_passwd" != "$codendiadm_passwd2" ]; do
    read -s -p "Password for user codendiadm: " codendiadm_passwd
    echo
    read -s -p "Retype codendiadm password: " codendiadm_passwd2
    echo
done

mm_passwd="a"; mm_passwd2="b";
while [ "$mm_passwd" != "$mm_passwd2" ]; do
    read -s -p "Password for user mailman: " mm_passwd
    echo
    read -s -p "Retype mailman password: " mm_passwd2
    echo
done

if [ "$enable_plugin_im" = "true" ]; then
    openfire_passwd="a"; openfire_passwd2="b";
    while [ "$openfire_passwd" != "$openfire_passwd2" ]; do
        read -s -p "Password for Openfire DB user: " openfire_passwd
        echo
        read -s -p "Retype password for Openfire DB user: " openfire_passwd2
        echo
    done
fi

echo "DB authentication user: MySQL user that will be used for user authentication"
echo "  Please do not reuse a password here, as this password will be stored in clear on the filesystem and will be accessible to all logged-in user."

dbauth_passwd="a"; dbauth_passwd2="b";
while [ "$dbauth_passwd" != "$dbauth_passwd2" ]; do
    read -s -p "Password for DB Authentication user: " dbauth_passwd
    echo
    read -s -p "Retype password for DB Authentication user: " dbauth_passwd2
    echo
done

py_cmd="import crypt; print crypt.crypt(\"$codendiadm_passwd\",\"\$1\$h67e4niB\$\")"
codendi_encpasswd=`python -c "$py_cmd"`
py_cmd="import crypt; print crypt.crypt(\"$mm_passwd\",\"\$1\$eniB4h67\$\")"
mm_encpasswd=`python -c "$py_cmd"`

# Update codendi user password
$USERMOD -p "$codendi_encpasswd" codendiadm

# Build file structure

build_dir /home/users codendiadm codendiadm 771
build_dir /home/groups codendiadm codendiadm 771

# home directories
build_dir /home/codendiadm codendiadm codendiadm 700
# data dirs
build_dir /var/lib/codendi codendiadm codendiadm 755
build_dir /var/lib/codendi/dumps dummy dummy 755
build_dir /var/lib/codendi/ftp root ftp 755
build_dir /var/lib/codendi/ftp/codendi root root 711
build_dir /var/lib/codendi/ftp/pub ftpadmin ftpadmin 755
build_dir /var/lib/codendi/ftp/incoming ftpadmin ftpadmin 3777
build_dir /var/lib/codendi/wiki codendiadm codendiadm 700
build_dir /var/lib/codendi/backup codendiadm codendiadm 711
build_dir /var/lib/codendi/backup/mysql mysql mysql 770 
build_dir /var/lib/codendi/backup/mysql/old root root 700
build_dir /var/lib/codendi/backup/subversion root root 700
build_dir /var/lib/codendi/docman codendiadm codendiadm 700
# log dirs
build_dir /var/log/codendi codendiadm codendiadm 755
build_dir /var/log/codendi/cvslogs codendiadm codendiadm 775
build_dir /var/tmp/codendi_cache codendiadm codendiadm 755
# config dirs
build_dir /etc/skel_codendi root root 755
build_dir /etc/codendi codendiadm codendiadm 755
build_dir /etc/codendi/conf codendiadm codendiadm 700
build_dir /etc/codendi/documentation codendiadm codendiadm 755
build_dir /etc/codendi/documentation/user_guide codendiadm codendiadm 755
build_dir /etc/codendi/documentation/user_guide/xml codendiadm codendiadm 755
build_dir /etc/codendi/documentation/cli codendiadm codendiadm 755
build_dir /etc/codendi/documentation/cli/xml codendiadm codendiadm 755
build_dir /etc/codendi/site-content codendiadm codendiadm 755
build_dir /etc/codendi/site-content/en_US codendiadm codendiadm 755
build_dir /etc/codendi/site-content/en_US/others codendiadm codendiadm 755
build_dir /etc/codendi/site-content/fr_FR codendiadm codendiadm 755
build_dir /etc/codendi/site-content/fr_FR/others codendiadm codendiadm 755
build_dir /etc/codendi/themes codendiadm codendiadm 755
build_dir /etc/codendi/plugins codendiadm codendiadm 755
build_dir /etc/codendi/plugins/docman codendiadm codendiadm 755
build_dir /etc/codendi/plugins/pluginsadministration codendiadm codendiadm 755
# SCM dirs
build_dir /var/run/log_accum root root 777
build_dir /var/lib/codendi/cvsroot codendiadm codendiadm 751
build_dir /var/lib/codendi/svnroot codendiadm codendiadm 751
build_dir /var/lock/cvs root root 751
$LN -sf /var/lib/codendi/cvsroot /cvsroot
$LN -sf /var/lib/codendi/svnroot /svnroot


$TOUCH /var/lib/codendi/ftp/incoming/.delete_files
$CHOWN codendiadm.ftpadmin /var/lib/codendi/ftp/incoming/.delete_files
$CHMOD 750 /var/lib/codendi/ftp/incoming/.delete_files
$TOUCH /var/lib/codendi/ftp/incoming/.delete_files.work
$CHOWN codendiadm.ftpadmin /var/lib/codendi/ftp/incoming/.delete_files.work
$CHMOD 750 /var/lib/codendi/ftp/incoming/.delete_files.work
build_dir /var/lib/codendi/ftp/codendi/DELETED codendiadm codendiadm 750

$TOUCH /etc/httpd/conf.d/codendi_svnroot.conf

# SELinux specific
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/codendi
    $CHCON -R -h $SELINUX_CONTEXT /etc/codendi
    $CHCON -R -h $SELINUX_CONTEXT /var/lib/codendi
    $CHCON -R -h $SELINUX_CONTEXT /home/groups
    $CHCON -R -h $SELINUX_CONTEXT /home/codendiadm
    $CHCON -h $SELINUX_CONTEXT /svnroot
    $CHCON -h $SELINUX_CONTEXT /cvsroot
fi


##############################################
# Move away useless Apache configuration files
# before installing our own config files.
#
echo "Renaming existing Apache configuration files..."
cd /etc/httpd/conf.d/
for f in *.conf
do
    # Do not erease conf files provided by "our" packages and for which
    # we don't have a .dist version
    case "$f" in
        "viewvc.conf"|"munin.conf"|"mailman.conf")
            continue;;
    esac
    yn="0"
    current_name="$f"
    orig_name="$f.rhel"
    [ -f "$orig_name" ] && read -p "$orig_name already exist. Overwrite? [y|n]:" yn

    if [ "$yn" != "n" ]; then
	$MV -f $current_name $orig_name
    fi

    if [ "$yn" = "n" ]; then
	$RM -f $current_name
    fi
    # In order to prevent RedHat from reinstalling those files during an RPM update, re-create an empty file for each file deleted
    $TOUCH $current_name
done
cd - > /dev/null


$PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to Codendi FTP service./g" /etc/vsftpd/vsftpd.conf 


echo "Creating MySQL conf file..."
$CAT <<'EOF' >/etc/my.cnf
[client]
loose-default-character-set=utf8

[mysqld]
default-character-set=utf8
log-bin=codendi-bin
skip-bdb
set-variable = max_allowed_packet=128M
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
# Default to using old password format for compatibility with mysql 3.x
# clients (those using the mysqlclient10 compatibility package).
old_passwords=1

# Skip logging openfire db (for instant messaging)
# The 'monitor' openfire plugin creates large codendi-bin files
# Comment this line if you prefer to be safer.
set-variable  = binlog-ignore-db=openfire

[mysql.server]
user=mysql
basedir=/var/lib

[mysqld_safe]
err-log=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EOF

echo "Initializing MySQL: You can ignore additionnal messages on MySQL below this line:"
echo "***************************************"
# Start database
$SERVICE mysqld start
echo "***************************************"


##############################################
# Install the Codendi software 
#

echo "Installing configuration files..."
#echo " You should overwrite existing files"
make_backup /etc/httpd/conf/httpd.conf
for f in /etc/httpd/conf/httpd.conf /var/named/chroot/var/named/codendi.zone \
/etc/httpd/conf/ssl.conf \
/etc/httpd/conf.d/php.conf /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/auth_mysql.conf \
/etc/libnss-mysql.cfg  /etc/libnss-mysql-root.cfg \
/etc/codendi/conf/local.inc /etc/codendi/conf/database.inc /etc/httpd/conf.d/codendi_aliases.conf; do
    yn="0"
    fn=`basename $f`
#   [ -f "$f" ] && read -p "$f already exist. Overwrite? [y|n]:" yn
# Always overwrite files
    [ -f "$f" ] && yn="y"

    if [ "$yn" = "y" ]; then
	$CP -f $f $f.orig
    fi

    if [ "$yn" != "n" ]; then
	$CP -f $INSTALL_DIR/src/etc/$fn.dist $f
    fi

    $CHOWN codendiadm.codendiadm $f
    $CHMOD 640 $f
done

$CHOWN root:named /var/named/chroot/var/named/codendi.zone
#$LN -s /var/named/chroot/etc/named.conf /etc
if [ -f "/var/named/chroot/etc/named.conf" ]; then
   $CHGRP named /var/named/chroot/etc/named.conf
fi

if [ $SELINUX_ENABLED ]; then
    $CHCON -h system_u:object_r:named_zone_t /var/named/chroot/var/named/codendi.zone
    if [ -f "/var/named/chroot/etc/named.conf" ]; then
        $CHCON -h system_u:object_r:named_conf_t /var/named/chroot/etc/named.conf
    fi
fi


# Update nsswitch.conf to use libnss-mysql
if [ -f "/etc/nsswitch.conf" ]; then
    # passwd
    $GREP ^passwd  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^passwd(.*)/passwd\1 mysql/g" /etc/nsswitch.conf
    fi

    # shadow
    $GREP ^shadow  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^shadow(.*)/shadow\1 mysql/g" /etc/nsswitch.conf
    fi

    # group
    $GREP ^group  /etc/nsswitch.conf | $GREP -q mysql
    if [ $? -ne 0 ]; then
        $PERL -i'.orig' -p -e "s/^group(.*)/group\1 mysql/g" /etc/nsswitch.conf
    fi
else
    echo '/etc/nsswitch.conf does not exist. Cannot use MySQL authentication!'
fi


# replace string patterns in local.inc
substitute '/etc/codendi/conf/local.inc' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codendi/conf/local.inc' '%sys_ldap_server%' "$sys_ldap_server" 
substitute '/etc/codendi/conf/local.inc' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codendi/conf/local.inc' '%sys_long_org_name%' "$sys_long_org_name" 
substitute '/etc/codendi/conf/local.inc' '%sys_fullname%' "$sys_fullname" 
substitute '/etc/codendi/conf/local.inc' '%sys_dbauth_passwd%' "$dbauth_passwd" 
if [ "$disable_subdomains" = "y" ]; then
  substitute '/etc/codendi/conf/local.inc' 'sys_lists_host = "lists.' 'sys_lists_host = "'
  substitute '/etc/codendi/conf/local.inc' 'sys_disable_subdomains = 0' 'sys_disable_subdomains = 1'
fi
# replace string patterns in codendi_aliases.inc
substitute '/etc/httpd/conf.d/codendi_aliases.conf' '%sys_default_domain%' "$sys_default_domain" 

# replace string patterns in database.inc
substitute '/etc/codendi/conf/database.inc' '%sys_dbpasswd%' "$codendiadm_passwd" 

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

# replace strings in libnss-mysql config files
substitute '/etc/libnss-mysql.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
substitute '/etc/libnss-mysql-root.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
$CHOWN root:root /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg
$CHMOD 644 /etc/libnss-mysql.cfg
$CHMOD 600 /etc/libnss-mysql-root.cfg

# replace string patterns in munin.conf (for MySQL authentication)
substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 

if [ "$disable_subdomains" != "y" ]; then
  # replace string patterns in codendi.zone
  sys_shortname=`echo $sys_fullname | $PERL -pe 's/\.(.*)//'`
  dns_serial=`date +%Y%m%d`01
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_default_domain%' "$sys_default_domain" 
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_fullname%' "$sys_fullname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_ip_address%' "$sys_ip_address"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_shortname%' "$sys_shortname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%dns_serial%' "$dns_serial"
fi

# Make sure SELinux contexts are valid
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/codendi
fi

todo "Customize /etc/codendi/conf/local.inc and /etc/codendi/conf/database.inc"
todo "You may also want to customize /etc/httpd/conf/httpd.conf /usr/lib/codendi/bin/backup_job and /usr/lib/codendi/bin/backup_subversion.sh"

##############################################
# Installing phpMyAdmin
#

# Make codendiadm a member of the apache group
# This is needed to use the php session at /var/lib/php/session (e.g. for phpwiki)
$USERMOD -a -G apache codendiadm
# Allow read/write access to DAV lock dir for codendiadm in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

##############################################
# Installing the Codendi database
#
echo "Creating the Codendi database..."

yn="-"
freshdb=0
pass_opt=""
if [ -d "/var/lib/mysql/codendi" ]; then
    read -p "Codendi Database already exists. Overwrite? [y|n]:" yn
fi

# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing Codendi DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

# Delete the Codendi DB if asked for
if [ "$yn" = "y" ]; then
    $MYSQL -u root $pass_opt -e "drop database codendi"
fi

if [ ! -d "/var/lib/mysql/codendi" ]; then
    freshdb=1
    $MYSQL -u root $pass_opt -e "create database codendi DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
    $CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES on *.* to codendiadm@localhost identified by '$codendiadm_passwd' WITH GRANT OPTION;
REVOKE SUPER ON *.* FROM codendiadm@localhost;
GRANT ALL PRIVILEGES on *.* to root@localhost identified by '$rt_passwd';
FLUSH PRIVILEGES;
EOF
fi
# Password has changed
pass_opt="--password=$rt_passwd"

if [ $freshdb -eq 1 ]; then
echo "Populating the Codendi database..."
cd $INSTALL_DIR/src/db/mysql/
$MYSQL -u codendiadm codendi --password=$codendiadm_passwd < database_structure.sql   # create the DB
cp database_initvalues.sql /tmp/database_initvalues.sql
substitute '/tmp/database_initvalues.sql' '_DOMAIN_NAME_' "$sys_default_domain"
$MYSQL -u codendiadm codendi --password=$codendiadm_passwd < /tmp/database_initvalues.sql  # populate with init values.
rm -f /tmp/database_initvalues.sql

# Create dbauthuser
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT SELECT ON codendi.user to dbauthuser@localhost identified by '$dbauth_passwd';
GRANT SELECT ON codendi.groups to dbauthuser@localhost;
GRANT SELECT ON codendi.user_group to dbauthuser@localhost;
FLUSH PRIVILEGES;
EOF
fi

##############################################
# SSL Certificate creation

if [ "$create_ssl_certificate" = "y" ]; then
    $INSTALL_DIR/src/utils/generate_ssl_certificate.sh
fi


##############################################
# Mailman configuration
# RPM was intalled previously
#
echo "Configuring Mailman..."

# Setup admin password
/usr/lib/mailman/bin/mmsitepass $mm_passwd

#$LN -sf $MAILMAN_DIR /usr/local/mailman ???

# Update Mailman config
if [ "$disable_subdomains" != "y" ]; then
  LIST_DOMAIN=lists.$sys_default_domain
else
  LIST_DOMAIN=$sys_default_domain
fi

$CAT <<EOF >> /usr/lib/mailman/Mailman/mm_cfg.py
DEFAULT_EMAIL_HOST = '$LIST_DOMAIN'
DEFAULT_URL_HOST = '$LIST_DOMAIN'
add_virtualhost(DEFAULT_URL_HOST, DEFAULT_EMAIL_HOST)

# Remove images from Mailman pages (GNU, Python and Mailman logos)
IMAGE_LOGOS = 0

# Uncomment to run Mailman on secure server only
#DEFAULT_URL_PATTERN = 'https://%s/mailman/'
#PUBLIC_ARCHIVE_URL = 'https://%(hostname)s/pipermail/%(listname)s'

EOF


# Compile file
`python -O /usr/lib/mailman/Mailman/mm_cfg.py`

# Create site wide ML
# Note that if sys_default_domain is not a domain, the script will complain
LIST_OWNER=codendi-admin@$sys_default_domain
if [ "$disable_subdomains" = "y" ]; then
    LIST_OWNER=codendi-admin@$sys_fullname
fi
/usr/lib/mailman/bin/newlist -q mailman $LIST_OWNER $mm_passwd > /dev/null

# Comment existing mailman aliases in /etc/aliases
$PERL -i'.orig' -p -e "s/^mailman(.*)/#mailman\1/g" /etc/aliases


# Add new aliases
cat << EOF >> /etc/aliases

## mailman mailing list
mailman:              "|/usr/lib/mailman/mail/mailman post mailman"
mailman-admin:        "|/usr/lib/mailman/mail/mailman admin mailman"
mailman-bounces:      "|/usr/lib/mailman/mail/mailman bounces mailman"
mailman-confirm:      "|/usr/lib/mailman/mail/mailman confirm mailman"
mailman-join:         "|/usr/lib/mailman/mail/mailman join mailman"
mailman-leave:        "|/usr/lib/mailman/mail/mailman leave mailman"
mailman-owner:        "|/usr/lib/mailman/mail/mailman owner mailman"
mailman-request:      "|/usr/lib/mailman/mail/mailman request mailman"
mailman-subscribe:    "|/usr/lib/mailman/mail/mailman subscribe mailman"
mailman-unsubscribe:  "|/usr/lib/mailman/mail/mailman unsubscribe mailman"

EOF

# Subscribe codendi-admin to this ML
echo $LIST_OWNER | /usr/lib/mailman/bin/add_members -r - mailman

$SERVICE mailman start

##############################################
# Installing and configuring Sendmail
# #
# echo "##############################################"
# echo "Installing sendmail shell wrappers and configuring sendmail..."
# cd /etc/smrsh
# $LN -sf /usr/lib/codendi/bin/gotohell
# #$LN -sf $MAILMAN_DIR/mail/mailman Now done in RPM install

# $PERL -i'.orig' -p -e's:^O\s*AliasFile.*:O AliasFile=/etc/aliases,/etc/aliases.codendi:' /etc/mail/sendmail.cf
# cat <<EOF >/etc/mail/local-host-names
# # local-host-names - include all aliases for your machine here.
# $sys_default_domain
# lists.$sys_default_domain
# users.$sys_default_domain
# EOF


# Default: codex-admin is redirected to root
# TODO check if already there
echo "codendi-admin:          root" >> /etc/aliases

todo "Finish sendmail settings (see installation Guide). By default, emails sent to codendi-admin are redirected to root (see /etc/aliases)"

##############################################
# CVS configuration
#
echo "Configuring the CVS server and CVS tracking tools..."
$TOUCH /etc/cvs_root_allow
$CHOWN codendiadm.codendiadm /etc/cvs_root_allow
$CHMOD 644 /etc/cvs_root_allow

$CP /etc/xinetd.d/cvs /root/cvs.xinetd.ori

$CAT <<'EOF' >/etc/xinetd.d/cvs
service cvspserver
{
        disable             = no
        socket_type         = stream
        protocol            = tcp
        wait                = no
        user                = root
        server              = /usr/bin/cvs
        server_args         = -f -z3 -T/var/tmp --allow-root-file=/etc/cvs_root_allow pserver
}
EOF

$CAT <<'EOF' >> /etc/shells
/usr/lib/codendi/bin/cvssh
/usr/lib/codendi/bin/cvssh-restricted
EOF


##############################################
# Make the system daily cronjob run at 23:58pm
echo "Updating daily cron job in system crontab..."
$PERL -i'.orig' -p -e's/\d+ \d+ (.*daily)/58 23 \1/g' /etc/crontab

##############################################
# FTP server configuration
#

# Configure vsftpd
$PERL -i'.orig' -p -e "s/^#anon_upload_enable=YES/anon_upload_enable=YES/g" /etc/vsftpd/vsftpd.conf 
$PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to Codendi FTP service./g" /etc/vsftpd/vsftpd.conf 
$PERL -pi -e "s/^local_umask=.*/local_umask=002/g" /etc/vsftpd/vsftpd.conf 

# Add welcome messages
$CAT <<'EOF' > /var/lib/codendi/ftp/.message
********************************************************************
Welcome to Codendi FTP server

On This Site:
/incoming          Place where to upload your new file release
/pub               Projects Anonymous FTP space
*********************************************************************

EOF
$CHOWN ftpadmin.ftpadmin /var/lib/codendi/ftp/.message

# Add welcome messages
$CAT <<'EOF' >/var/lib/codendi/ftp/incoming/.message

Upload new file releases here

EOF
$CHOWN ftpadmin.ftpadmin /var/lib/codendi/ftp/incoming/.message

$SERVICE vsftpd start

##############################################
# Create the custom default page for the project Web sites
#
echo "Creating the custom default page for the project Web sites..."
def_page=/etc/codendi/site-content/en_US/others/default_page.php
yn="y"
[ -f "$def_page" ] && read -p "Custom Default Project Home page already exists. Overwrite? [y|n]:" yn
if [ "$yn" = "y" ]; then
    $MKDIR -p /etc/codendi/site-content/en_US/others
    $CHOWN codendiadm.codendiadm /etc/codendi/site-content/en_US/others
    $CP $INSTALL_DIR/site-content/en_US/others/default_page.php /etc/codendi/site-content/en_US/others/default_page.php
fi

if [ "$disable_subdomains" = "y" ]; then
  echo "Use same-host project web sites"
  $MYSQL -u codendiadm codendi --password=$codendiadm_passwd -e "UPDATE service SET link = IF(group_id = 1, '/www/codendi', '/www/\$projectname/') WHERE short_name = 'homepage' "
fi

todo "Customize /etc/codendi/site-content/en_US/others/default_page.php (project web site default home page)"
todo "Customize site-content information for your site."
todo "  For instance: contact/contact.txt cvs/intro.txt"
todo "  svn/intro.txt include/new_project_email.txt, etc."
##############################################
# Shell Access configuration
#

if [ "$active_shell" = "n" ]; then
    echo "Shell access configuration defaulted to 'No shell account'..."
    $MYSQL -u codendiadm codendi --password=$codendiadm_passwd -e "ALTER TABLE user ALTER COLUMN shell SET DEFAULT '/sbin/nologin'"
fi

##############################################
# DNS Configuration
#
if [ "$disable_subdomains" != "y" ]; then
  todo "Create the DNS configuration files as explained in the Codendi Installation Guide:"
  todo "    update /var/named/chroot/var/named/codendi.zone - replace all words starting with %%."
  todo "    make sure the file is readable by 'other':"
  todo "      > chmod o+r /var/named/chroot/var/named/codendi.zone"
  todo "    edit /etc/named.conf to create the new zone."
fi

##############################################
# Crontab configuration
#
echo "Installing root user crontab..."
crontab -u root -l > /tmp/cronfile

$GREP -q "Codendi" /tmp/cronfile
if [ $? -ne 0 ]; then
    $CAT <<'EOF' >>/tmp/cronfile
#
# Codendi: daily incremental backup of subversion repositories
45 23 * * 1-6 /usr/lib/codendi/bin/backup_subversion.sh -i
#
# Codendi: weekly full backup of subversion repositories (0:15 on Sunday)
15 0 * * Sun /usr/lib/codendi/bin/backup_subversion.sh -noarchives
#
# Codendi: weekly backup preparation (mysql shutdown, file dump and restart)
45 0 * * Sun /usr/lib/codendi/bin/backup_job
EOF
    crontab -u root /tmp/cronfile
fi

##############################################
# Log Files rotation configuration
#
echo "Installing log files rotation..."
$CAT <<'EOF' >/etc/logrotate.d/httpd
/var/log/httpd/access_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd graceful > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/var/log/codendi/$year/$month"
     destfile="http_combined_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/access_log.1 $destdir/$destfile
    endscript
}
 
/var/log/httpd/vhosts-access_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd graceful > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     #server=`hostname`
     destdir="/var/log/codendi/$year/$month"
     destfile="vhosts-access_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/vhosts-access_log.1 $destdir/$destfile
    endscript
}

/var/log/httpd/error_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd graceful > /dev/null || true
    endscript
}


/var/log/httpd/svn_log {
    missingok
    daily
    rotate 4
    postrotate
        /sbin/service httpd graceful > /dev/null || true
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     #server=`hostname`
     destdir="/var/log/codendi/$year/$month"
     destfile="svn_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/httpd/svn_log.1 $destdir/$destfile
    endscript
}

EOF
$CHOWN root:root /etc/logrotate.d/httpd
$CHMOD 644 /etc/logrotate.d/httpd


$CAT <<'EOF' >/etc/logrotate.d/vsftpd.log
/var/log/xferlog {
    # ftpd doesn't handle SIGHUP properly
    nocompress
    missingok
    daily
    postrotate
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/var/log/codendi/$year/$month"
     destfile="ftp_xferlog_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/xferlog.1 $destdir/$destfile
    endscript
}
EOF
$CHOWN root:root /etc/logrotate.d/vsftpd.log
$CHMOD 644 /etc/logrotate.d/vsftpd.log

##############################################
# Create Codendi profile script
#

# customize the global profile 
$GREP profile_codendi /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' >>/etc/profile
# Now the Part specific to Codendi users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_codendi
fi
EOF

$CAT <<'EOF' >/etc/profile_codendi
# /etc/profile_codendi
#
# Specific login set up and messages for Codendi users`
 
# All projects this user belong to
 
grplist_id=`id -G`;
grplist_name=`id -Gn`;
 
idx=1
for i in $grplist_id
do
        if [ $i -gt 1000 -a $i -lt 20000 ]; then
                field_list=$field_list"$idx,"
        fi
        idx=$[ $idx + 1]
done
grplist=`echo $grplist_name | cut -f$field_list -d" "`;
 
cat <<EOM
 
-------------------------------------
W E L C O M E   T O   C O D E N D I !
-------------------------------------

You are currently in your user home directory: $HOME
EOM

echo "Your project home directories (Web site) are in:"
for i in $grplist
do
        echo "    - /home/groups/$i"
done

cat <<EOM
Corresponding CVS and Subversion repositories are in /cvsroot and /svnroot

             *** IMPORTANT REMARK ***
The Codendi server hosts very valuable yet publicly available
data. Therefore we recommend that you keep working only in
the directories listed above for which you have full rights
and responsibilities.

EOM
EOF

##############################################
# Make sure all major services are on
#
if [ "$disable_subdomains" != "y" ]; then
  $CHKCONFIG named on
fi
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysqld on
$CHKCONFIG cvs on
$CHKCONFIG mailman on
$CHKCONFIG munin-node on
$CHKCONFIG vsftpd on
$CHKCONFIG crond on

if [ "$enable_plugin_im" = "true" ]; then
    $CHKCONFIG openfire on
fi

/etc/init.d/codendi start

$SERVICE httpd restart
$SERVICE crond restart

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss-mysql is used for authentication
$CHKCONFIG nscd on

$SERVICE nscd start
$SERVICE munin-node start

##############################################
# Set SELinux contexts and load policies
#
if [ $SELINUX_ENABLED ]; then
    echo "Set SELinux contexts and load policies"
    $INSTALL_DIR/src/utils/fix_selinux_contexts.pl
fi

##############################################
# *Last* step: install plugins
#

echo "Install codendi plugins"
# docman plugin
$CAT $INSTALL_DIR/plugins/docman/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
build_dir /etc/codendi/plugins/docman/etc codendiadm codendiadm 755
$CP $INSTALL_DIR/plugins/docman/etc/docman.inc.dist /etc/codendi/plugins/docman/etc/docman.inc
$CHOWN codendiadm.codendiadm /etc/codendi/plugins/docman/etc/docman.inc
$CHMOD 644 /etc/codendi/plugins/docman/etc/docman.inc


#GraphOnTrackers plugin
$CAT $INSTALL_DIR/plugins/graphontrackers/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
$CAT $INSTALL_DIR/plugins/graphontrackers/db/initvalues.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd

# IM plugin
if [ "$enable_plugin_im" = "true" ]; then
    # Create openfireadm MySQL user
    $CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES on openfire.* to openfireadm@localhost identified by '$openfire_passwd';
GRANT SELECT ON codendi.user to openfireadm@localhost;
GRANT SELECT ON codendi.groups to openfireadm@localhost;
GRANT SELECT ON codendi.user_group to openfireadm@localhost;
GRANT SELECT ON codendi.session to openfireadm@localhost;
FLUSH PRIVILEGES;
EOF
    # Install plugin
    build_dir /etc/codendi/plugins/IM/etc codendiadm codendiadm 755
    $CAT $INSTALL_DIR/plugins/IM/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('IM', '1');
EOF
    # Initialize Jabbex
    IM_ADMIN_GROUP='imadmingroup'
    IM_ADMIN_USER='imadmin-bot'
    IM_ADMIN_USER_PW='1M@dm1n'
    IM_MUC_PW='Mu6.4dm1n' # Doesn't need to change
    $PHP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/install.php -a -orp $rt_passwd -uod openfireadm -pod $openfire_passwd -ucd openfireadm -pcd $openfire_passwd -odb jdbc:mysql://localhost:3306/openfire -cdb jdbc:mysql://localhost:3306/codendi -ouri $sys_default_domain -gjx $IM_ADMIN_GROUP -ujx $IM_ADMIN_USER -pjx $IM_ADMIN_USER_PW -pmuc $IM_MUC_PW
fi

##############################################
# Install & configure forgeupgrade for Codendi
#

# Cannot be done yet (to old branch)


##############################################
# End of installation
#
todo "Don't forget to read the INSTALL file"
todo ""
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE."

# End of it
echo "=============================================="
echo "Installation completed successfully!"
$CAT $TODO_FILE

exit 0

