#!/bin/bash
#
# Copyright (c) Xerox Corporation, Codendi 2001-2009.
# This file is licensed under the GNU General Public License version 2. See the file COPYING. 
#
#      Originally written by Laurent Julliard 2004-2006, Codendi Team, Xerox
#
#  This file is part of the Codendi software and must be placed at the same
#  level as the Codendi, RPMS_Codendi and nonRPMS_Codendi directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running Codendi 3.6 to Codendi 4.0
#


progname=$0
#scriptdir=/mnt/cdrom
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd "${scriptdir}";TOP_DIR=`pwd`;cd - > /dev/null # redirect to /dev/null to remove display of folder (RHEL4 only)
RPMS_DIR="${TOP_DIR}/RPMS_Codendi"
nonRPMS_DIR="${TOP_DIR}/nonRPMS_Codendi"
Codendi_DIR="${TOP_DIR}/Codendi"
TODO_FILE=/root/todo_codendi_upgrade_4.0.txt
export INSTALL_DIR="/usr/share/codendi"
BACKUP_INSTALL_DIR="/usr/share/codex_36"
ETC_DIR="/etc/codendi"
USR_LIB_DIR="/usr/lib/codendi"
VAR_LIB_DIR="/var/lib/codendi"
VAR_TMP_DIR="/var/tmp/codendi_cache"
VAR_LOG_DIR="/var/log/codendi"
BACKUP_DIR="/root/codex_3_6_backup"
TMP_DUMP_DIR="/var/tmp"

# path to command line tools
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
GROUPMOD='/usr/sbin/groupmod'
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
export MYSQL='/usr/bin/mysql'
# For instance -hdb.codendi.com
MYSQL_PARAMS=""
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

CMD_LIST="GROUPADD GROUDEL GROUPMOD USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND MYSQL TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL DIFF"

CHCON='/usr/bin/chcon'
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
if [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] || `grep -i -q '^SELINUX=disabled' /etc/selinux/config`; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi


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
  if [ -f $1 ]; then
    # $1: filename, $2: string to match, $3: replacement string
    # Allow '/' is $3, so we need to double-escape the string
    replacement=`echo $3 | sed "s|/|\\\\\/|g"`
    $PERL -pi -e "s/$2/$replacement/g" $1
  fi
}

codendification() {
    substitute "$1" 'C O D E X' 'C O D E N D I'
    substitute "$1" 'CODEX' 'CODENDI'
    substitute "$1" 'CodeX' 'Codendi'
    substitute "$1" 'Codex' 'Codendi'
    substitute "$1" 'codex' 'codendi'
}

# @param $1 table
# @param $2 name of the index
mysql_drop_index() {
    $MYSQL $pass_opt codendi -e "SHOW INDEX FROM $1 WHERE key_name = '$2'" | grep -q $2
    if [ $? -eq 0 ]; then
        $MYSQL $pass_opt codendi -e "ALTER TABLE $1 DROP INDEX $2"
    fi
}

# @param $1 table
# @param $2 name of the index
# @param $3 columns (coma separated)
mysql_add_index() {
    mysql_drop_index "$1" "$2"
    $MYSQL $pass_opt codendi -e "ALTER TABLE $1 ADD INDEX $2($3)"
}

# @param $1 table
# @param $2 name of the unique index
# @param $3 columns (coma separated)
mysql_add_unique() {
    mysql_drop_index "$1" "$2"
    $MYSQL $pass_opt codendi -e "ALTER TABLE $1 ADD UNIQUE $2($3)"
}
##############################################
# Codendi 3.6 to 4.0 migration
##############################################
echo "Migration script from Codendi 3.6 to Codendi 4.0"
echo "Please Make sure you read migration_from_Codendi_3.6_to_Codendi_4.0.README"
echo "*before* running this script!"
echo "Also, make sure you have enough disk space in $TMP_DUMP_DIR, because this script will dump the whole database in this directory"
echo "If you don't have enough space there, please update the TMP_DUMP_DIR variable in this script"
yn="y"
read -p "Continue? [yn]: " yn
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
# Check the machine is running Codendi 3.6
#
OLD_CX_RELEASE='3.6'
yn="y"
$GREP -q "$OLD_CX_RELEASE" /usr/share/codex/src/www/VERSION
if [ $? -ne 0 ]; then
    $CAT <<EOF
This machine does not have Codendi ${OLD_CX_RELEASE} installed. Executing this
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found Codendi ${OLD_CX_RELEASE} installed... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check we are running on RHEL 5
#
RH_RELEASE="5"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
  $RPM -q centos-release-${RH_RELEASE}* 2>/dev/null 1>&2
  if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
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

##############################################
# Detect architecture
$UNAME -m | $GREP -q x86_64
if [ $? -ne 0 ]; then
  ARCH=i386
else
  ARCH=x86_64
fi


##############################################
# Check Required Stock RedHat RPMs are installed
#

rpms_ok=1
for rpm in nscd php-pear mod_auth_mysql
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
sys_default_domain=`grep ServerName /etc/httpd/conf/httpd.conf | grep -v '#' | head -1 | cut -d " " -f 2 ;`
if [ -z $sys_default_domain ]; then
  read -p "CodeX Domain name: " sys_default_domain
fi
sys_ip_address=`grep NameVirtualHost /etc/httpd/conf/httpd.conf | grep -v '#' | cut -d " " -f 2 | cut -d ":" -f 1`
if [ -z $sys_ip_address ]; then
  read -p "Codex Server IP address: " sys_ip_address
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



################################################
# Check that there is no codendi database
#
if [ -d /var/lib/mysql/codendi ]; then
    echo "Conflict: There is already a database named codendi."
    echo "Aborting."
    exit 1
fi

###############################################################################
echo "Updating Packages"

# -> libnss-mysql (system authentication based on MySQL)
$RPM -e --allmatches libnss-mysql 2>/dev/null
echo "Installing libnss-mysql RPM for Codendi...."
cd "${RPMS_DIR}/libnss-mysql"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --nosignature ${newest_rpm}/$ARCH/libnss-mysql-1*.rpm
	 
# -> APC
$RPM -e php-pecl-apc 2>/dev/null
echo "Installing APC (PHP cache) RPM for Codendi...."
cd "${RPMS_DIR}/php-pecl-apc"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/$ARCH/php-pecl-apc-*.rpm

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, httpd, sendmail, mailman and smb ..."
$SERVICE openfire stop
$SERVICE crond stop
$SERVICE httpd stop
$SERVICE mysqld stop
$SERVICE postfix stop
$SERVICE mailman stop
$SERVICE smb stop


echo -n "codexadm is now known as codendiadm..."
$GROUPMOD -n codendiadm codexadm
$USERMOD -d /home/codendiadm -m  -c 'Owner of Codendi directories' -l codendiadm codexadm
# also relocate homedir of ftp, ftpadmin and dummy users
$USERMOD -d /var/lib/codendi/ftp ftp 2> /dev/null
$USERMOD -d /var/lib/codendi/ftp ftpadmin 2> /dev/null
$USERMOD  -c 'Dummy Codendi User' -d /var/lib/codendi/dumps dummy 2> /dev/null
echo "done"

##############################################
# Install the Codendi software 
#
echo "Installing the Codendi software..."
$MV /usr/share/codex $BACKUP_INSTALL_DIR
$MKDIR $INSTALL_DIR;
cd $INSTALL_DIR
$TAR xfz "${Codendi_DIR}"/codendi*.tgz
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR

echo "Setting up fileperms on installed files and directory"
$FIND $INSTALL_DIR -type f -exec $CHMOD u+rw,g+rw,o-w+r {} \;
$FIND $INSTALL_DIR -type d -exec $CHMOD 775 {} \;

#
# Migrate paths
#

# Backups
$MKDIR -p $BACKUP_DIR
$MKDIR -p $BACKUP_DIR/etc
$MKDIR -p $BACKUP_DIR/etc/httpd
$MKDIR -p $BACKUP_DIR/usr/lib
$MKDIR -p $BACKUP_DIR/var/tmp
$MKDIR -p $BACKUP_DIR/var/named/chroot/var

echo "Backup /etc/httpd/conf.d"
$CP -r /etc/httpd/conf.d $BACKUP_DIR/etc/httpd
echo "Backup /etc/httpd/conf"
$CP -r /etc/httpd/conf $BACKUP_DIR/etc/httpd
echo "Backup /etc/codex"
$CP -r /etc/codex $BACKUP_DIR/etc/codex
echo "Backup /etc/passwd, /etc/shadow, /etc/group"
$CP -r /etc/passwd $BACKUP_DIR/etc/
$CP -r /etc/shadow $BACKUP_DIR/etc/
$CP -r /etc/group $BACKUP_DIR/etc/
echo "Backup /usr/lib/codex"
$CP -r /usr/lib/codex $BACKUP_DIR/usr/lib
echo "Backup /var/named/chroot/var/named"
$CP -r /var/named/chroot/var/named $BACKUP_DIR/var/named/chroot/var
echo "Backup /var/named/chroot/etc"
$CP -r /var/named/chroot/etc $BACKUP_DIR/var/named/chroot
echo "Backup /var/tmp/codex_cache"
$MV /var/tmp/codex_cache $BACKUP_DIR/var/tmp/

# Renames
$MV /etc/codex $ETC_DIR
$MV /usr/lib/codex $USR_LIB_DIR
$MV /var/lib/codex/ftp/codex /var/lib/codex/ftp/codendi
$MV /var/lib/codex $VAR_LIB_DIR
$MV /var/log/codex $VAR_LOG_DIR
build_dir /var/tmp/codendi_cache codendiadm codendiadm 755
$RM /etc/httpd/conf.d/codex_svnroot.conf
$TOUCH /etc/httpd/conf.d/codendi_svnroot.conf



#
# Codendification of config files
#
echo "Codendification"
substitute '/etc/logrotate.d/httpd' '\/var\/log\/codex' "$VAR_LOG_DIR"
substitute '/etc/logrotate.d/vsftpd.log' '\/var\/log\/codex' "$VAR_LOG_DIR"

if [ -d /etc/skel_codex ]; then 
    $MV /etc/skel_codex /etc/skel_codendi
fi

$CP /var/named/chroot/var/named/codex_full.zone /var/named/chroot/var/named/codex_full.zone_36
$MV /var/named/chroot/var/named/codex_full.zone /var/named/chroot/var/named/codendi.zone
substitute '/var/named/chroot/etc/named.conf' 'codex_full.zone' "codendi.zone" 

$CP /etc/aliases.codex /etc/aliases.codex_36
$MV /etc/aliases.codex /etc/aliases.codendi
substitute '/etc/mail/sendmail.cf' '\/etc\/aliases.codex' "/etc/aliases.codendi"

codendification /etc/shells

if [ -f /etc/cvsnt/PServer ]; then
    $PERL -pi -e 's/^\#(.*)CODEX/\#\1CODENDI/g' /etc/cvsnt/PServer 
    $PERL -pi -e 's/^\#(.*)CodeX/\#\1Codendi/g' /etc/cvsnt/PServer 
fi

codendification "$ETC_DIR/conf/local.inc"
codendification "$ETC_DIR/conf/database.inc"
substitute "$ETC_DIR/conf/local.inc" "sys_themedefault\s*=\s*'CodendiTab'" "sys_themedefault = 'CodeXTab'"
substitute "$ETC_DIR/conf/local.inc" "sys_themedefault\s*=\s*'Codendi'" "sys_themedefault = 'CodeX'"
substitute "$ETC_DIR/conf/local.inc" "sys_themedefault_old\s*=\s*'Codendi'" "sys_themedefault_old = 'CodeX'"
substitute "$ETC_DIR/conf/local.inc" "sys_email_admin\s*=\s*'codendi" "sys_email_admin = 'codex"

# -> cvs
echo "Removing existing CVS .."
$RPM -e --allmatches cvs 2>/dev/null
echo "Installing CVS RPMs for Codendi...."
cd "${RPMS_DIR}/cvs"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/$ARCH/cvs-1.*.rpm

# Recover config
$CP /etc/xinetd.d/cvs.rpmsave /etc/xinetd.d/cvs

# -> JPGraph
$RPM -e jpgraph jpgraphs-docs 2>/dev/null
echo "Installing JPGraph RPM for Codendi...."
cd "${RPMS_DIR}/jpgraph"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/jpgraph-2*noarch.rpm
$RPM -Uvh ${newest_rpm}/jpgraph-docs-2*noarch.rpm

# -> ViewVC
$RPM -e --nodeps viewcvs 2>/dev/null
$RPM -e --nodeps viewvc 2>/dev/null
echo "Installing viewvc RPM for Codendi...."
cd "${RPMS_DIR}/viewvc"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/viewvc-*.noarch.rpm
# Use new conf file
$CP /etc/codendi/conf/viewvc.conf.rpmnew /etc/codendi/conf/viewvc.conf

# -> mailman
echo "Removing installed mailman if any .."
$RPM -e --allmatches mailman 2>/dev/null
echo "Installing mailman RPM for Codendi...."
cd "${RPMS_DIR}/mailman"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/$ARCH/mailman-2*.rpm

# Recover config
$CP /usr/lib/mailman/Mailman/mm_cfg.py.rpmsave /usr/lib/mailman/Mailman/mm_cfg.py

# Munin
echo "Removing installed Munin if any .."
$RPM -e --allmatches `rpm -qa 'munin*' 'perl-HTML-Template*' 'perl-Net-Server' 'perl-rrdtool*' 'rrdtool*' 'perl-Crypt-DES' 'perl-Net-SNMP' 'perl-Config-General'` 2>/dev/null
echo "Installing Munin RPMs for Codendi...."
cd "${RPMS_DIR}/munin"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM --nosignature -Uvh ${newest_rpm}/noarch/perl-Net-Server*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/$ARCH/perl-Crypt-DES*.rpm
$RPM --nosignature -Uvh ${newest_rpm}/noarch/perl-Net-SNMP-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/noarch/perl-Config-General-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/noarch/perl-HTML-Template*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/$ARCH/rrdtool-*.rpm ${newest_rpm}/$ARCH/perl-rrdtool-*.rpm
$RPM -Uvh ${newest_rpm}/noarch/munin-node-*.noarch.rpm
$RPM -Uvh ${newest_rpm}/noarch/munin-1*.noarch.rpm
# Fix ownership issues.
$CHOWN -R munin:munin /var/www/munin
$CHOWN -R munin:munin /var/run/munin
$CHOWN -R munin:munin /var/log/munin
$CHOWN -R munin:munin /var/lib/munin

# -> HTML Purifier
echo "Removing installed htmlpurifier if any .."
$RPM -e htmlpurifier 2>/dev/null
$RPM -e htmlpurifier-docs 2>/dev/null
echo "Installing htmlpurifier RPM for Codendi...."
cd "${RPMS_DIR}/htmlpurifier"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/htmlpurifier-3*.noarch.rpm
$RPM -Uvh ${newest_rpm}/htmlpurifier-docs*.noarch.rpm

#####
# Codendi RPMS

# -> codendi-jri
echo "Removing installed CodeX JRI if any .."
$RPM -e --allmatches codex-jri 2>/dev/null
echo "Installing Codendi JRI RPM...."
cd "${RPMS_DIR}/codendi-jri"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-jri-*noarch.rpm


# -> codendi-eclipse
echo "Removing installed Eclipse plugin if any .."
$RPM -e --allmatches codex-eclipse 2>/dev/null
echo "Installing Eclipse plugin RPM...."
cd "${RPMS_DIR}/codendi-eclipse"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-eclipse-*noarch.rpm

##############################################
# Now install various precompiled utilities
# Fileforge was codendified
cd "${nonRPMS_DIR}/utilities"
for f in *
do
  $CP $f /usr/lib/codendi/bin
  $CHOWN codendiadm.codendiadm /usr/lib/codendi/bin/$f
done
$CHOWN root:root /usr/lib/codendi/bin/fileforge
$CHMOD u+s /usr/lib/codendi/bin/fileforge


# Path codendification of /cvsroot and /svnroot
rm /cvsroot
rm /svnroot
ln -s $VAR_LIB_DIR/cvsroot /cvsroot
ln -s $VAR_LIB_DIR/svnroot /svnroot

#
# Install New dist files
#

for f in /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/codendi_aliases.conf \
 /etc/httpd/conf.d/php.conf /etc/httpd/conf.d/subversion.conf \
 /etc/libnss-mysql.cfg  /etc/libnss-mysql-root.cfg /etc/httpd/conf.d/auth_mysql.conf; do
    yn="0"
    fn=`basename $f`
    [ -f "$f" ] && read -p "$f already exist. Overwrite? [y|n]:" yn

    if [ "$yn" = "y" ]; then
	$CP -f $f $f.orig
    fi

    if [ "$yn" != "n" ]; then
	$CP -f $INSTALL_DIR/src/etc/$fn.dist $f
    fi

    $CHOWN codendiadm.codendiadm $f
    $CHMOD 640 $f
done

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"
# replace string patterns in codendi_aliases.inc
substitute '/etc/httpd/conf.d/codendi_aliases.conf' '%sys_default_domain%' "$sys_default_domain" 
$RM /etc/httpd/conf.d/codex_aliases.conf


# replace strings in libnss-mysql config files
substitute '/etc/libnss-mysql.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
substitute '/etc/libnss-mysql-root.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
$CHOWN root:root /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg
$CHMOD 644 /etc/libnss-mysql.cfg
$CHMOD 600 /etc/libnss-mysql-root.cfg

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


###############################################################################
# Add logrotate for SVN logs
$CAT <<'EOF' >>/etc/logrotate.d/httpd

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

###############################################################################
# Add some privacy in shared directories. Also helps libnss_mysql...
$CHMOD 751 $VAR_LIB_DIR/cvsroot/
$CHMOD 751 $VAR_LIB_DIR/svnroot/
$CHMOD 771 /home/users
$CHMOD 771 /home/groups

# Remove some privacy on project directories: private data should now be stored in the "private" directory
# This is needed because codendiadm is no longer member of all private projects.
$CHMOD o+x /home/groups/*

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss_mysql is used for authentication
$CHKCONFIG nscd on

$SERVICE nscd start


#############################################
# Make codendiadm a member of the apache group
# for phpMyAdmin (session, config files...)
# NG: wasn't this done in 3.6?
$USERMOD -a -G apache codendiadm

# Allow read/write access to DAV lock dir for codendiadm in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

#############################################
# Remove SMB support
$CHKCONFIG smb off

# move password file to backup dir
if [ -f /etc/samba/smbpasswd ]; then
  $MV /etc/samba/smbpasswd $BACKUP_DIR/etc
fi



###############################################################################
echo "Updating local.inc"

# Remove $sys_win_domain
$PERL -pi -e 's/(\$sys_win_domain.*)/\/\/\1 DEPRECATED/g' $ETC_DIR/conf/local.inc

# Remove $apache_htpasswd
$PERL -pi -e 's/(\$apache_htpasswd.*)/\/\/\1 DEPRECATED/g' $ETC_DIR/conf/local.inc

# Remove sys_crondelay
$PERL -pi -e 's/(\$sys_crondelay.*)/\/\/\1 DEPRECATED/g' $ETC_DIR/conf/local.inc

# dbauthuser and password
$GREP -q ^\$sys_dbauth_user  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
// DB user for http authentication (must have access to user/group/user_group tables)
\$sys_dbauth_user = "dbauthuser";
\$sys_dbauth_passwd = '$dbauth_passwd';
?>
EOF
fi

# sys_pending_account_lifetime
$GREP -q ^\$sys_pending_account_lifetime  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
// Duration before deleting pending accounts which have not been activated
// (in days)
// Default value is 60 days
\$sys_pending_account_lifetime = 60;
?>
EOF
fi

# unix_uid_add
$GREP -q ^\$unix_uid_add  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
// How much to add to the database unix_uid to get the actual unix uid
\$unix_uid_add  = "20000";
?>
EOF
fi

# unix_gid_add
$GREP -q ^\$unix_gid_add  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
// How much to add to the database group_id to get the unix gid
\$unix_gid_add  = "1000";
?>
EOF
fi

# cvs_hook_tmp_dir
$GREP -q ^\$cvs_hook_tmp_dir  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
\$cvs_hook_tmp_dir    = "/var/run/log_accum"; // temporary directory used by CVS commit hooks

?>
EOF
fi

# svn_root_file 
$GREP -q ^\$svn_root_file  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc
\$svn_root_file = "/etc/httpd/conf.d/codendi_svnroot.conf"; // File containing SVN repository definitions for Apache

?>
EOF
fi

# alias_file 
$GREP -q ^\$alias_file  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc

// Sendmail alias
\$alias_file      = "/etc/aliases.codendi";

?>
EOF
fi

# sys_supported_languages 
$GREP -q ^\$sys_supported_languages  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codendi/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codendi/conf/local.inc

// Supported languages (separated comma)
// Only en_US and fr_FR are available for now
// Exemple: 'en_US,fr_FR'
\$sys_supported_languages = 'en_US,fr_FR';

?>
EOF
fi


###############################################################################
# HTTP-based authentication
echo "Removing /etc/httpd/conf/htpasswd: this file is no longer needed (now using MySQL based authentication with mod_auth_mysql)"

if [ -f "/etc/httpd/conf/htpasswd" ]; then
  $RM /etc/httpd/conf/htpasswd
fi

echo "Update munin.conf accordingly"
# replace string patterns in munin.conf (for MySQL authentication)
substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 


##############################################
# MySQL config
echo "Updating /etc/my.cnf..."
codendification "/etc/my.cnf"

$PERL -pi -e "s/(\[mysqld\])/\1\n# Skip logging openfire db (for instant messaging)\n# The 'monitor' openfire plugin creates large codendi-bin files\n# Comment this line if you prefer to be safer.\nset-variable  = binlog-ignore-db=openfire\n/g" /etc/my.cnf

##############################################
# Database Structure and initvalues upgrade
#

echo "Updating the database..."

$SERVICE mysqld restart
sleep 5



pass_opt=""
# See if MySQL root account is password protected
mysqlshow $MYSQL_PARAMS 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow $MYSQL_PARAMS --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"


echo "Starting DB update for Codendi 4.0 This might take a few minutes."

echo "- rename codex db as codendi"
mysqldump $MYSQL_PARAMS --max_allowed_packet=512M -u root $pass_opt codex > $TMP_DUMP_DIR/dump.codex.sql
$MYSQL $MYSQL_PARAMS -u root $pass_opt mysql -e "CREATE DATABASE codendi DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;"
$MYSQL $MYSQL_PARAMS -u root $pass_opt codendi < $TMP_DUMP_DIR/dump.codex.sql

mysqldump $MYSQL_PARAMS -u root $pass_opt mysql > $TMP_DUMP_DIR/dump.mysql.sql
substitute "$TMP_DUMP_DIR/dump.mysql.sql" 'codex' 'codendi'
$MYSQL $MYSQL_PARAMS -u root $pass_opt mysql < $TMP_DUMP_DIR/dump.mysql.sql

# Restart DB.
$SERVICE mysqld restart
sleep 5
 

echo "- Create dbauthuser, needed for MySQL-based authentication for HTTP (SVN), libNSS-mysql and Openfire"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS -u root mysql $pass_opt
GRANT SELECT ON codendi.user to dbauthuser@localhost identified by '$dbauth_passwd';
GRANT SELECT ON codendi.groups to dbauthuser@localhost;
GRANT SELECT ON codendi.user_group to dbauthuser@localhost;
GRANT SELECT ON codendi.session to dbauthuser@localhost;
FLUSH PRIVILEGES;
EOF


echo "- Add support for > 2GB files in DB (FRS and Wiki)"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE frs_file CHANGE file_size file_size BIGINT NOT NULL DEFAULT '0';
ALTER TABLE wiki_attachment_revision CHANGE size size BIGINT NOT NULL;
EOF

echo "- Remove nobody permissions FRS and update permissions table"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
DELETE FROM permissions_values WHERE (permission_type='PACKAGE_READ' or permission_type='RELEASE_READ') and ugroup_id='100';
UPDATE permissions SET ugroup_id='4' WHERE (permission_type='PACKAGE_READ' or permission_type='RELEASE_READ') and ugroup_id='100';
EOF

echo "- Remove useless tables"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
DROP TABLE intel_agreement;
DROP TABLE user_diary;
DROP TABLE user_diary_monitor;
DROP TABLE user_metric0;
DROP TABLE user_metric1;
DROP TABLE user_metric_tmp1_1;
DROP TABLE user_ratings;
DROP TABLE user_trust_metric;
EOF


echo "- Account approver"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE user ADD COLUMN approved_by int(11) NOT NULL default '0' AFTER add_date;
EOF


echo "- Windows password no longer needed"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE user DROP COLUMN windows_pw;
EOF

echo "- Table structure for System Events"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
# 
# Table structure for System Events
# 
# type        : one of "PROJECT_CREATE", "PROJECT_DELETE", "USER_CREATE", etc.
# parameters  : event parameters (group_id, etc.) depending on event type
# priority    : event priority from 3 (high prio) to 1 (low prio)
# status      : event status: 'NEW' = nothing done yet, 'RUNNING' = event is being processed, 
#               'DONE', 'ERROR', 'WARNING' = event processed successfully, with error, or with a warning message respectively.
# create_date : date when the event was created in the DB
# process_date: date when event processing started
# end_date    : date when processing finished
# log         : log message after processing (useful for e.g. error messages or warnings).

DROP TABLE IF EXISTS system_event;
CREATE TABLE IF NOT EXISTS system_event (
  id INT(11) unsigned NOT NULL AUTO_INCREMENT, 
  type VARCHAR(255) NOT NULL default '',
  parameters TEXT,
  priority TINYINT(1) NOT NULL default '0',
  status  ENUM( 'NEW', 'RUNNING', 'DONE', 'ERROR', 'WARNING' ) NOT NULL DEFAULT 'NEW',
  create_date DATETIME NOT NULL,
  process_date DATETIME NULL,
  end_date DATETIME NULL,
  log TEXT,
  PRIMARY KEY (id)
) TYPE=MyISAM;

CREATE TABLE system_events_followers (
  id INT(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, 
  emails TEXT NOT NULL ,
  types VARCHAR( 31 ) NOT NULL
);

INSERT INTO system_events_followers (emails, types) VALUES
('admin', 'WARNING,ERROR');

EOF


echo "- Artifact permissions"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE artifact ADD COLUMN use_artifact_permissions tinyint(1) NOT NULL DEFAULT '0' AFTER group_artifact_id;

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',15);
EOF


echo "- Mandatory reference in SVN commit message"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE groups 
    ADD svn_mandatory_ref TINYINT NOT NULL DEFAULT '0' AFTER svn_tracker,
    ADD svn_accessfile text NULL AFTER svn_preamble;
EOF

echo "- Cross references : add a new field 'nature'"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE reference ADD nature VARCHAR( 64 ) NOT NULL;
EOF

echo "- Set the nature for existing references"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE reference
SET nature = 'artifact'
WHERE (keyword = 'art' OR
       keyword = 'artifact' OR
       keyword = 'bug' OR
       keyword = 'patch' OR
       keyword = 'slmbug' OR
       keyword = 'sr' OR
       keyword = 'story' OR
       keyword = 'task'
      ) OR link LIKE '%/tracker/%';
      
UPDATE reference
SET nature = 'document'
WHERE (keyword = 'doc' OR
       keyword = 'document' OR
       keyword = 'dossier' OR
       keyword = 'folder'
      );
UPDATE reference
SET nature = 'cvs_commit'
WHERE (keyword = 'cvs' OR
       keyword = 'commit'
      );
UPDATE reference
SET nature = 'svn_revision'
WHERE (keyword = 'svn' OR
       keyword = 'revision' OR
       keyword = 'rev'
      );
UPDATE reference
SET nature = 'file'
WHERE (keyword = 'file'
      );
UPDATE reference
SET nature = 'release'
WHERE (keyword = 'release'
      );
UPDATE reference
SET nature = 'forum'
WHERE (keyword = 'forum'
      );
UPDATE reference
SET nature = 'forum_message'
WHERE (keyword = 'msg'
      );
UPDATE reference
SET nature = 'news'
WHERE (keyword = 'news'
      );
UPDATE reference
SET nature = 'snippet'
WHERE (keyword = 'snippet'
      );
UPDATE reference
SET nature = 'wiki_page'
WHERE (keyword = 'wiki'
      );
UPDATE reference
SET nature = 'other'
WHERE (nature = '' OR
       nature IS NULL);

UPDATE reference
SET service_short_name = 'tracker'
WHERE (nature = '' OR nature IS NULL);
EOF

echo "- Cross-references change the type of column to handle wiki references (not int)"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE cross_references CHANGE source_id source_id VARCHAR( 255 ) NOT NULL DEFAULT '0';
ALTER TABLE cross_references CHANGE target_id target_id VARCHAR( 255 ) NOT NULL DEFAULT '0';
EOF

echo "- Cross references : add two fields"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE cross_references ADD source_keyword VARCHAR( 32 ) NOT NULL AFTER source_type;
ALTER TABLE cross_references ADD target_keyword VARCHAR( 32 ) NOT NULL AFTER target_type;
EOF

echo "- Change type of existing cross references from 'revision_svn' to 'svn_revision'"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE cross_references SET source_type = 'svn_revision' WHERE source_type LIKE 'revision_svn';
UPDATE cross_references SET target_type = 'svn_revision' WHERE target_type LIKE 'revision_svn';
EOF

echo "- Set keywords"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE cross_references SET source_keyword = 'art' WHERE source_type LIKE 'artifact';
UPDATE cross_references SET source_keyword = 'doc' WHERE source_type LIKE 'document';
UPDATE cross_references SET source_keyword = 'cvs' WHERE source_type LIKE 'cvs_commit';
UPDATE cross_references SET source_keyword = 'svn' WHERE source_type LIKE 'svn_revision';
UPDATE cross_references SET source_keyword = 'file' WHERE source_type LIKE 'file';
UPDATE cross_references SET source_keyword = 'release' WHERE source_type LIKE 'release';
UPDATE cross_references SET source_keyword = 'forum' WHERE source_type LIKE 'forum';
UPDATE cross_references SET source_keyword = 'msg' WHERE source_type LIKE 'forum_message';
UPDATE cross_references SET source_keyword = 'news' WHERE source_type LIKE 'news';
UPDATE cross_references SET source_keyword = 'snippet' WHERE source_type LIKE 'snippet';
UPDATE cross_references SET source_keyword = 'wiki' WHERE source_type LIKE 'wiki_page';
UPDATE cross_references SET target_keyword = 'art' WHERE target_type LIKE 'artifact';
UPDATE cross_references SET target_keyword = 'doc' WHERE target_type LIKE 'document';
UPDATE cross_references SET target_keyword = 'cvs' WHERE target_type LIKE 'cvs_commit';
UPDATE cross_references SET target_keyword = 'svn' WHERE target_type LIKE 'svn_revision';
UPDATE cross_references SET target_keyword = 'file' WHERE target_type LIKE 'file';
UPDATE cross_references SET target_keyword = 'release' WHERE target_type LIKE 'release';
UPDATE cross_references SET target_keyword = 'forum' WHERE target_type LIKE 'forum';
UPDATE cross_references SET target_keyword = 'msg' WHERE target_type LIKE 'forum_message';
UPDATE cross_references SET target_keyword = 'news' WHERE target_type LIKE 'news';
UPDATE cross_references SET target_keyword = 'snippet' WHERE target_type LIKE 'snippet';
UPDATE cross_references SET target_keyword = 'wiki' WHERE target_type LIKE 'wiki_page';
EOF

echo "- fix references > services"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE reference
SET service_short_name = 'tracker'
WHERE scope = 'P'
AND (service_short_name = '' OR service_short_name IS NULL)
AND link LIKE '/tracker/%func=detail%';
EOF

echo "- add new reference for IM chat"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
INSERT INTO reference SET 
    keyword='chat', 
    description='plugin_im:reference_chat_desc_key', 
    link='/plugins/IM/?group_id=\$group_id&action=viewchatlog&chat_log=\$1', 
    scope='S', 
    service_short_name='IM',
    nature='im_chat';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups; 
EOF

# IM plugin

echo "- Add IM service"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=\$group_id', 1 , 1 , 'system',  210 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=1', 1 , 0 , 'system',  210 );
# Create IM service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', CONCAT('/plugins/IM/?group_id=', group_id), 1 , 0 , 'system',  210
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'IM');
EOF

echo "- IM plugin : grant privileges for openfireadm on session table (required for webmuc)"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
GRANT SELECT ON codendi.session to openfireadm@localhost;
FLUSH PRIVILEGES;
EOF

# IM openfire configuration
echo "- Specific configuration for webmuc"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
REPLACE INTO openfire.jiveProperty (name, propValue) VALUES 
	("httpbind.enabled", "true"),
	("httpbind.port.plain", "7070"),
	("xmpp.httpbind.client.requests.polling", "0"),
	("xmpp.httpbind.client.requests.wait", "10"),
	("xmpp.httpbind.scriptSyntax.enabled", "true"),
	("xmpp.muc.history.type", "all"),
	("conversation.idleTime", "10"),
    ("conversation.maxTime", "240"),
    ("conversation.messageArchiving", "false"),
    ("conversation.metadataArchiving", "true"),
    ("conversation.roomArchiving", "true");
EOF


echo "- CI with Hudson plugin"
echo "- install and enable hudson plugin"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
INSERT INTO plugin (name, available) VALUES ('hudson', '1');
EOF

$CAT $INSTALL_DIR/plugins/hudson/db/install.sql | $MYSQL $MYSQL_PARAMS $pass_opt codendi

echo "- Update user language"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
ALTER TABLE user CHANGE language_id language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US';

UPDATE user 
SET language_id = 'fr_FR'
WHERE language_id = 2;

UPDATE user 
SET language_id = 'en_US'
WHERE language_id != 'fr_FR';

ALTER TABLE wiki_group_list CHANGE language_id language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US';

UPDATE wiki_group_list 
SET language_id = 'fr_FR'
WHERE language_id = 2;

UPDATE wiki_group_list 
SET language_id = 'en_US'
WHERE language_id != 'fr_FR';

DROP TABLE supported_languages;
EOF


echo "- Add 3 new widgets on project summary page"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 1, 'projectclassification', R.rank
FROM groups 
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 1
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id);

INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 1, 'projectdescription', R.rank
FROM groups
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 1
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id);

INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 2, 'projectmembers', R.rank
FROM groups
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 2
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id)
WHERE hide_members = 0;
EOF

echo "- Add system_events widgets on admin dashboard"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT user_id, 'u', layout_id, column_id, 'mysystemevent', R.rank
FROM user_group 
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents INNER JOIN owner_layouts USING (owner_id, owner_type, layout_id)
                 WHERE owner_type = 'u' 
                   AND is_default = 1
                 GROUP BY owner_id, owner_type, layout_id) AS R
           ON (owner_id = user_id)
WHERE group_id = 1
  AND admin_flags = 'A';
EOF

echo "- Delete hide_members column"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
# (not needed anymore, please do it after previous request)
ALTER TABLE groups DROP hide_members;
EOF

echo "- Add cvs_is_private"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi 
ALTER TABLE groups ADD cvs_is_private TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER cvs_preamble ;
EOF

echo "- Layouts for dashboard"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi 
INSERT INTO layouts(id, name, description, scope) VALUES
(2, '3 columns', 'Simple layout made of 3 columns', 'S'),
(3, 'Left', 'Simple layout made of a main column and a small, left sided, column', 'S'),
(4, 'Right', 'Simple layout made of a main column and a small, right sided, column', 'S');

INSERT INTO layouts_rows(id, layout_id, rank) VALUES
(2, 2, 0),
(3, 3, 0),
(4, 4, 0);

INSERT INTO layouts_rows_columns(id, layout_row_id, width) VALUES
(3, 2, 33),
(4, 2, 33),
(5, 2, 33),
(6, 3, 33),
(7, 3, 66),
(8, 4, 66),
(9, 4, 33);



CREATE TABLE IF NOT EXISTS widget_twitterfollow (
  id int(11) unsigned NOT NULL auto_increment,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  user text NOT NULL,
  PRIMARY KEY  (id),
  KEY owner_id (owner_id,owner_type)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS widget_wikipage (
  id int(11) unsigned NOT NULL auto_increment,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  group_id int(11) unsigned NOT NULL default '0',
  wiki_page text,
  PRIMARY KEY  (id),
  KEY owner_id (owner_id,owner_type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

EOF

echo "- Upgrade docman"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi 
CREATE TABLE IF NOT EXISTS plugin_docman_widget_embedded (
  id int(11) unsigned NOT NULL auto_increment,
  owner_id int(11) unsigned NOT NULL,
  owner_type varchar(1) NOT NULL,
  title varchar(255) NOT NULL,
  item_id int(11) unsigned NOT NULL,
  PRIMARY KEY  (id),
  KEY owner_id (owner_id,owner_type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

EOF

echo "- Files can now be browsed and downloaded by anonymous users (default permissions do not change, we only allow it)"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi 
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',1);
EOF

echo "- CVS is private"
# TODO : private projects 
$CAT <<EOF | $PHP
<?php
require_once('/etc/codendi/conf/local.inc');
require_once('/etc/codendi/conf/database.inc');
mysql_connect(\$sys_dbhost, \$sys_dbuser, \$sys_dbpasswd) or die('ERROR: Unable to connect to the database. Aborting.');
mysql_select_db(\$sys_dbname) or die('ERROR: Unable to select the database. Aborting.');

\$groups = array();
foreach(glob(\$GLOBALS['cvs_prefix'] .'/*/.CODEX_PRIVATE') as \$g) {
    \$groups[] = "'". mysql_real_escape_string(preg_replace('|^.*/([^/]*)/.CODEX_PRIVATE|', '\$1', \$g)) ."'";
    unlink(\$g);
}
if (count(\$groups)) {
    echo 'The following projects want to set their cvs repository private: '. implode(', ', \$groups). PHP_EOL;
    \$sql = "UPDATE groups 
            SET cvs_is_private = '1'
            WHERE unix_group_name IN (". implode(', ', \$groups) .")";
    mysql_query(\$sql) or die("ERROR: While executing the sql statement: ". mysql_error() ." -> ".\$sql);
    echo 'done.'. PHP_EOL;
} else {
    echo 'No projects want to set their cvs repository private.'. PHP_EOL;
}
?>
EOF

echo "- Store .SVNAccessFile in db"
$CAT <<EOF | $PHP
<?php
require_once('/etc/codendi/conf/local.inc');
require_once('/etc/codendi/conf/database.inc');

mysql_connect(\$sys_dbhost, \$sys_dbuser, \$sys_dbpasswd) or die('ERROR: Unable to connect to the database. Aborting.');
mysql_select_db(\$sys_dbname) or die('ERROR: Unable to select the database. Aborting.');

function svn_utils_read_svn_access_file(\$gname) {

    global \$svn_prefix;

    \$filename = "\$svn_prefix/\$gname/.SVNAccessFile";
    \$buffer = '';

    \$fd = @fopen("\$filename", "r");
    if (!\$fd) {
        error_log("Unable to open \$filename");
        \$buffer = false;
    } else {
        \$in_settings = false;
        while (!feof(\$fd)) {
            \$line = fgets(\$fd, 4096);
            if (strpos(\$line,'# BEGIN CODEX DEFAULT') !== false) { \$in_settings = true; }
            if (!\$in_settings) { \$buffer .= \$line; }
            if (strpos(\$line,'# END CODEX DEFAULT') !== false) { \$in_settings = false; }
        }
        fclose(\$fd);
    }
    return \$buffer;
}

foreach(glob(\$svn_prefix.'/*/.SVNAccessFile') as \$file) {
    \$gname = basename(dirname(\$file));
    \$content = svn_utils_read_svn_access_file(\$gname);
    \$sql = "UPDATE groups 
            SET svn_accessfile = '". mysql_real_escape_string(\$content) ."' 
            WHERE unix_group_name = '". mysql_real_escape_string(\$gname) ."'";
    mysql_query(\$sql) or error_log(mysql_error());
}
?>
EOF


echo "- Rename codexjri to codendijri"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE plugin
SET name = 'codendijri'
WHERE name = 'codexjri';
EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck $MYSQL_PARAMS -Aaos $pass_opt

##############################################
# Upgrade to SVN 1.6
#
echo "Upgrade repositories to SVN 1.6"
find /svnroot/ -maxdepth 1 -mindepth 1 -name "*" -exec sudo -u codendiadm svnadmin upgrade {} \;  2>/dev/null 1>&2

###############################################################################
# Create 'private' directories in /home/groups/
echo "Creating private directories in /home/groups/"
find /home/groups/ -maxdepth 1 -mindepth 1 -type d -exec mkdir -p --mode=2770 '{}/private' \; -exec chown dummy '{}/private' \;


###############################################################################
# Remove old backend script from crontab
echo "Add new system scripts in root crontab"
$CAT <<'EOF' > $TMP_DUMP_DIR/root_cronfile
# Once a minute, process Codendi system events
* * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./process_system_events.php)
#
# Regularly launch a system_check event (e.g. every half-hour) 
0,30 * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./launch_system_check.php)
#
EOF

echo "Remove xerox_crontab script from root crontab"
crontab -u root -l >> $TMP_DUMP_DIR/root_cronfile
$PERL -i'.orig' -p -e's/^(.*xerox_crontab.sh.*)$/#\1/' $TMP_DUMP_DIR/root_cronfile
# Also remove this line (done in SYSTEM_CHECK event)
$PERL -pi -e's/^(.*chmod u\+s log_accum fileforge.*)$/#\1/' $TMP_DUMP_DIR/root_cronfile

# Codendification of crontab
codendification "$TMP_DUMP_DIR/root_cronfile"

substitute "$TMP_DUMP_DIR/root_cronfile" "xerox_all_daily_stats" "compute_all_daily_stats" 

crontab -u root $TMP_DUMP_DIR/root_cronfile


echo "Installing  codendiadm user crontab..."
$CAT <<'EOF' >$TMP_DUMP_DIR/cronfile
# Daily Codendi PHP cron (obsolete documents...)
10 0 * * * /usr/share/codendi/src/utils/php-launcher.sh /usr/share/codendi/src/utils/codendi_daily.php
# Re-generate the Codendi User Guides on a daily basis
00 03 * * * /usr/share/codendi/src/utils/generate_doc.sh
30 03 * * * /usr/share/codendi/src/utils/generate_programmer_doc.sh
45 03 * * * /usr/share/codendi/src/utils/generate_cli_package.sh
EOF
crontab -u codendiadm $TMP_DUMP_DIR/cronfile

# Move away codexadm crontab
if [ -f '/var/spool/cron/codexadm' ]; then
  $MV '/var/spool/cron/codexadm' "$BACKUP_DIR/codexadm.cron"
fi


##############################################
# Codendification of CODEX_LICENSE_ACCEPTED
if [ -f "/etc/codendi/CODEX_LICENSE_ACCEPTED" ]; then
  $MV /etc/codendi/CODEX_LICENSE_ACCEPTED /etc/codendi/CODENDI_LICENSE_ACCEPTED
fi

##############################################
# Codendification of profile
codendification '/etc/profile'
codendification '/etc/profile_codex'
$MV /etc/profile_codex /etc/profile_codendi


codendification '/etc/vsftpd/vsftpd.conf'
codendification '/var/lib/codex/ftp/.message'

perl -pi -e 's@/var/lib/codex@/var/lib/codendi@g;' /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd
perl -pi -e 's@ftp/codex@ftp/codendi@g;' /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd
perl -pi -e 's@/var/lib/codex@/var/lib/codendi@g;' /etc/codendi/plugins/docman/etc/docman.inc

##############################################
# Codendification: CVS and SVN repos

echo "Migrate CVS hook files"
$FIND /cvsroot/ -type d -name CVSROOT -exec co -q -l {}/loginfo \; 2> /dev/null
$FIND /cvsroot/ -type f -name loginfo -exec perl -pi -e 's@/codex/@/codendi/@g; s/CodeX/Codendi/g; s/CODEX/CODENDI/g'  {} \;
$FIND /cvsroot/ -type f -name loginfo -exec /usr/bin/rcs -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name loginfo -exec ci -q -m"Codendi 4.0 mod" -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name loginfo -exec co -q {} \; 2> /dev/null


$FIND /cvsroot/ -type f -name commitinfo -exec co -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name commitinfo -exec perl -pi -e 's@/codex/@/codendi/@g; s/CodeX/Codendi/g; s/CODEX/CODENDI/g'  {} \; 
$FIND /cvsroot/ -type f -name commitinfo -exec /usr/bin/rcs -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name commitinfo -exec ci -q -m"Codendi 4.0 mod" -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name commitinfo -exec co -q {} \; 2> /dev/null

$FIND /cvsroot/ -type f -name config -exec co -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name config -exec perl -pi -e 's@/codex/@/codendi/@g; s/CodeX/Codendi/g; s/CODEX/CODENDI/g'  {} \; 
$FIND /cvsroot/ -type f -name config -exec /usr/bin/rcs -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name config -exec ci -q -m"Codendi 4.0 mod" -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name config -exec co -q {} \; 2> /dev/null

$FIND /cvsroot/ -type f -name notify -exec co -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name notify -exec perl -pi -e 's@/codex/@/codendi/@g; s/CodeX/Codendi/g; s/CODEX/CODENDI/g'  {} \; 
$FIND /cvsroot/ -type f -name notify -exec /usr/bin/rcs -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name notify -exec ci -q -m"Codendi 4.0 mod" -q -l {} \; 2> /dev/null
$FIND /cvsroot/ -type f -name notify -exec co -q {} \; 2> /dev/null

echo "Migrate SVN hook files"
$FIND /svnroot/ -type f -name post-commit -exec perl -pi -e 's@/codex/@/codendi/@g; s/CodeX/Codendi/g; s/CODEX/CODENDI/g'  {} \;
$FIND /svnroot/ -type f -name .SVNAccessFile -exec perl -pi -e 's/CODEX DEF/CODENDI DEF/g'  {} \;
# Pre-commit hooks will be automatically installed during the next system_check event.

##############################################
# Clean-up system files

echo "Cleaning-up system files (/etc/passwd, /etc/shadow, /etc/group)"

$PERL <<'EOF'

sub open_array_file {
    my $filename = shift(@_);
    open (FD, $filename) || die "Can't open $filename: $!.\n";
    @tmp_array = <FD>;
    close(FD);
    return @tmp_array;
}       

sub write_array_file {
    my ($file_name, @file_array) = @_;    
    open(FD, ">$file_name.codenditemp") || die "Can't open $file_name: $!.\n";
    foreach (@file_array) { 
        if ($_ ne '') { 
                print FD;
            }       
    }       
    close(FD);
    rename "$file_name.codenditemp","$file_name" || die "Can't rename $file_name.codenditemp to $file_name: $!.\n";
}

sub hash_passwd_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name,$junk,$id,$rest) = split(":", $_);
          if (defined $tmp_hash{$id}) { ;}
          $tmp_hash{$id}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }

sub hash_shadow_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name,$rest) = split(":", $_);
          if (defined $tmp_hash{$name}) {;}
          $tmp_hash{$name}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }

sub hash_group_array {
        my @file_array = @_;
        my %tmp_hash;
        my $counter=0;

        foreach (@file_array) {
          ($name,$x,$gid,$rest) = split(":", $_);
          if (defined $tmp_hash{$gid}) { ;}
          $tmp_hash{$gid}=$counter;
          $counter++;
        }
        return %tmp_hash;
      }

@passwd_array = open_array_file("/etc/passwd");
@passwd_array_copy = open_array_file("/etc/passwd");
@shadow_array = open_array_file("/etc/shadow");
@group_array = open_array_file("/etc/group");
@group_array_copy = open_array_file("/etc/group");
%passwd_hash = hash_passwd_array(@passwd_array);
%shadow_hash = hash_shadow_array(@shadow_array);
%group_hash = hash_group_array(@group_array);

while ($ln = pop(@passwd_array_copy)) {
    chop($ln);
    ($username, $x, $uid, $gid, $remain) = split(":", $ln);
    if ($uid > 20000) {
        # Remove Codendi user
        if (defined $passwd_hash{$uid}) {
            $passwd_array[$passwd_hash{$uid}] = '';
        }
        if (defined $shadow_hash{$username}) {
          $shadow_array[$shadow_hash{$username}] = '';
        }
    }
}

while ($ln = pop(@group_array_copy)) {
    chop($ln);
    ($groupname, $x, $gid,$remain) = split(":", $ln);
    if ($gid > 1000) {
        if (defined $group_hash{$gid}) {
            $group_array[$group_hash{$gid}] = '';
        }
    }
}

write_array_file("/etc/passwd", @passwd_array);
write_array_file("/etc/shadow", @shadow_array);
write_array_file("/etc/group", @group_array);


EOF




##############################################
# Remove deprecated SELinux modules
if [ $SELINUX_ENABLED ]; then
  echo "Removing obsolete SELinux module"
  /usr/sbin/semodule -r cvs ethtool fileforge mailman others viewvc svn vsftpd 2> /dev/null
fi

##############################################
# Fix SELinux contexts if needed
#
echo "Update SELinux contexts and modules"
cd $INSTALL_DIR/src/utils
./fix_selinux_contexts.pl

##############################################
# Restarting some services
#
#echo "Starting services..."
#$SERVICE crond start
#$SERVICE httpd start
#$SERVICE postfix start
#$SERVICE mailman start






# IM / Webchat configuration
echo "Configuring IM/Webchat"
SYS_DEFAULT_DOMAIN=`$GREP '^\$sys_default_domain' $ETC_DIR/conf/local.inc | /bin/sed -e 's/\$sys_default_domain\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
echo " - configure database_im.inc" 
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/database_im.tpl.inc $ETC_DIR/plugins/IM/etc/database_im.inc
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_HOST__}' "localhost"
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_USER__}' "openfireadm"
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_NAME__}' "openfire"
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_PWD__}' "`php -r '\$jive = new SimpleXmlElement(file_get_contents(\"/opt/openfire/conf/openfire.xml\")); echo \$jive->database->defaultProvider->password;'`"
echo " - modify openfire/conf/openfire.xml"
substitute "/opt/openfire/conf/openfire.xml" 'org.jivesoftware.openfire.auth.JDBCAuthProvider' "org.jivesoftware.openfire.auth.CodendiJDBCAuthProvider" 
substitute "/opt/openfire/conf/openfire.xml" '<passwordType>md5<\/passwordType>' "<passwordType>md5</passwordType><codendiUserSessionIdSQL>SELECT session_hash FROM session WHERE session.user_id = (SELECT user_id FROM user WHERE user.user_name = ?)</codendiUserSessionIdSQL>"
codendification "/opt/openfire/conf/openfire.xml"
echo " - copy Codendi Auth jar file into openfire lib dir"
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/codendi_auth.jar /opt/openfire/lib/.
echo " - install monitoring plugin (copy monitoring plugin jar in openfire plugin dir)"
cd "${RPMS_DIR}/openfire"
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$CP ${newest_rpm}/monitoring.jar /opt/openfire/plugins

echo "- Upgrade to openfire 3.6"
cd "${newest_rpm}"
./migration_openfire_3.5.2_to_3.6.4.sh

#
# Re-copy files that have been modified
#
cd $INSTALL_DIR/src/utils/cvs1
$CP log_accum /usr/lib/codendi/bin
$CP commit_prep /usr/lib/codendi/bin
cd /usr/lib/codendi/bin
$CHOWN codendiadm.codendiadm log_accum commit_prep
$CHMOD 755 log_accum commit_prep cvssh cvssh-restricted
$CHMOD u+s log_accum   # sets the uid bit (-rwsr-xr-x)

cd $INSTALL_DIR/src/utils
$CP backup_job /usr/lib/codendi/bin

cd $INSTALL_DIR/src/utils/svn
$CP commit-email.pl codendi_svn_pre_commit.php backup_subversion.sh /usr/lib/codendi/bin
cd /usr/lib/codendi/bin
$CHOWN codendiadm.codendiadm commit-email.pl codendi_svn_pre_commit.php backup_subversion.sh
$CHMOD 755 commit-email.pl codendi_svn_pre_commit.php

#
# Paths Codendification 
#
echo "- Rename codex to codendi"
$CAT <<EOF | $MYSQL $MYSQL_PARAMS $pass_opt codendi
UPDATE svn_repositories 
SET repository=replace(repository, '/var/lib/codex', '/var/lib/codendi');
UPDATE plugin_docman_version 
SET path= replace(path, '/var/lib/codex', '/var/lib/codendi');
EOF


##############################################
# Generate Documentation
#
# echo "Generating the Codendi Manuals. This will take a few minutes."
# su -c "$INSTALL_DIR/src/utils/generate_doc.sh -f" - codendiadm 2> /dev/null &
# su -c "$INSTALL_DIR/src/utils/generate_programmer_doc.sh -f" - codendiadm 2> /dev/null &
# su -c "$INSTALL_DIR/src/utils/generate_cli_package.sh -f" - codendiadm 2> /dev/null &
# $CHOWN -R codendiadm.codendiadm $INSTALL_DIR/documentation
# $CHOWN -R codendiadm.codendiadm $INSTALL_DIR/downloads


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
echo
echo "The following files contain the string 'codex'."
echo "You should manually edit them and check if the string should be replaced by 'codendi'."
echo
fgrep -ril codex /etc/codendi/site-content

echo
echo "Note: if you wish to change all occurences of 'codex' into 'codendi' for one file, you can execute this simple command:"
echo ' perl -pi -e "s/codex/codendi/g" filename'

exit 1;
