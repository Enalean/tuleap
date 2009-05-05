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
cd ${scriptdir};TOP_DIR=`pwd`;cd - > /dev/null # redirect to /dev/null to remove display of folder (RHEL4 only)
RPMS_DIR=${TOP_DIR}/RPMS_Codendi
nonRPMS_DIR=${TOP_DIR}/nonRPMS_Codendi
Codendi_DIR=${TOP_DIR}/Codendi
TODO_FILE=/root/todo_codendi_upgrade_4.0.txt
export INSTALL_DIR="/usr/share/codendi"
BACKUP_INSTALL_DIR="/usr/share/codex_36"
ETC_DIR="/etc/codendi"
USR_LIB_DIR="/usr/lib/codendi"
VAR_LIB_DIR="/var/lib/codendi"
VAR_TMP_DIR="/var/tmp/codendi_cache"
VAR_LOG_DIR="/var/log/codendi"
BACKUP_DIR="/root/codex_3_6_backup"

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
export MYSQL='/usr/bin/mysql'
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

CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
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
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
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
##############################################
# Codendi 3.6 to 4.0 migration
##############################################
echo "Migration script from Codendi 3.6 to Codendi 4.0"
echo "Please Make sure you read migration_from_Codendi_3.6_to_Codendi_4.0.README"
echo "*before* running this script!"
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
# Check Required Stock RedHat RPMs are installed
#

$RPM -q java-1.6.0-openjdk  2>/dev/null 1>&2
if [ $? -eq 1 ]; then
   echo "Java is now supported by RHEL/CentOS with the OpenJDK. If you wish to use it, you can install the package java-1.6.0-openjdk with yum and uninstall the JRE."
   read -p "Continue with current Java configuration? [yn]: " yn
   if [ "$yn" = "n" ]; then
       echo "Bye now!"
       exit 1
   fi
fi

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

echo "Upgrading to Subversion 1.6"
$RPM -e --allmatches subversion-tools  2>/dev/null
$RPM -e --allmatches subversion-devel 2>/dev/null
$RPM -e --allmatches mod_dav_svn 2>/dev/null
$RPM -e --allmatches subversion-perl 2>/dev/null
$RPM -e --allmatches subversion-python 2>/dev/null
$RPM -e --allmatches subversion 2>/dev/null
$RPM -e --allmatches neon-devel 2>/dev/null
$RPM -e --allmatches neon 2>/dev/null
echo "Installing Subversion, Neon and recent SQLite RPMs for Codendi...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
cd ${newest_rpm}
# Update SQLite first: version above 3.4 is required for SVN 1.6, and RHEL5 only provides version 3.3.
$RPM -Uvh sqlite-3*.i386.rpm
$RPM -ivh neon-0.*.i386.rpm neon-devel*.i386.rpm subversion-1.*.i386.rpm mod_dav_svn*.i386.rpm subversion-perl*.i386.rpm subversion-python*.i386.rpm 
# Dependency error with Perl ??
$RPM --nodeps -Uvh subversion-tools*.i386.rpm


# TODO MUST reinstall: munin RPM (Codendi specific, with MySQL auth), viewVC (bug fixed), phpMyAdmin, Mailman, htmlpurifier, cvs

# -> libnss-mysql (system authentication based on MySQL)
$RPM -e --allmatches libnss-mysql 2>/dev/null
echo "Installing libnss-mysql RPM for Codendi...."
cd ${RPMS_DIR}/libnss-mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --nosignature ${newest_rpm}/libnss-mysql-1*i?86.rpm
	 
# -> APC
$RPM -e php-pecl-apc 2>/dev/null
echo "Installing APC (PHP cache) RPM for Codendi...."
cd ${RPMS_DIR}/php-pecl-apc
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/php-pecl-apc-*.i?86.rpm


##############################################
# Stop some services before upgrading
#
echo "Stopping crond, httpd, sendmail, mailman and smb ..."
$SERVICE crond stop
$SERVICE httpd stop
$SERVICE mysqld stop
$SERVICE sendmail stop
$SERVICE mailman stop
$SERVICE smb stop


echo -n "codexadm is now known as codendiadm..."
groupmod -n codendiadm codexadm
usermod -d /home/codendiadm -m  -c 'Owner of Codendi directories' -l codendiadm codexadm
# also relocate homedir of ftp, ftpadmin and dummy users
usermod -d /var/lib/codendi/ftp ftp 2> /dev/null
usermod -d /var/lib/codendi/ftp ftpadmin 2> /dev/null
usermod  -c 'Dummy Codendi User' -d /var/lib/codendi/dumps dummy 2> /dev/null
echo "done"

##############################################
# Install the Codendi software 
#
echo "Installing the Codendi software..."
$MV /usr/share/codex $BACKUP_INSTALL_DIR
$MKDIR $INSTALL_DIR;
cd $INSTALL_DIR
$TAR xfz ${Codendi_DIR}/codendi*.tgz
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
$MV /var/named/chroot/var/named/codex_full.zone /var/named/chroot/etc/codendi.zone
substitute '/var/named/chroot/etc/named.conf' 'codex_full.zone' "codendi.zone" 

$CP /etc/aliases.codex /etc/aliases.codex_36
$MV /etc/aliases.codex /etc/aliases.codendi
substitute '/etc/mail/sendmail.cf' '\/etc\/aliases.codex' "/etc/aliases.codendi"

codendification "$ETC_DIR/conf/local.inc"
codendification "$ETC_DIR/conf/database.inc"
substitute "$ETC_DIR/conf/local.inc" "sys_themedefault\s*=\s*'CodendiTab'" "sys_themedefault = 'CodeXTab'"
substitute "$ETC_DIR/conf/local.inc" "sys_themedefault\s*=\s*'Codendi'" "sys_themedefault = 'CodeX'"

# -> cvs
echo "Removing existing CVS .."
$RPM -e --allmatches cvs 2>/dev/null
echo "Installing CVS RPMs for Codendi...."
cd ${RPMS_DIR}/cvs
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/cvs-1.*.i386.rpm

# -> JPGraph
$RPM -e jpgraph jpgraphs-docs 2>/dev/null
echo "Installing JPGraph RPM for Codendi...."
cd ${RPMS_DIR}/jpgraph
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/jpgraph-2*noarch.rpm
$RPM -Uvh ${newest_rpm}/jpgraph-docs-2*noarch.rpm

# -> ViewVC
$RPM -e --nodeps viewcvs 2>/dev/null
$RPM -e --nodeps viewvc 2>/dev/null
echo "Installing viewvc RPM for Codendi...."
cd ${RPMS_DIR}/viewvc
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/viewvc-*.noarch.rpm

# -> phpMyAdmin
$RPM -e phpMyAdmin phpmyadmin 2>/dev/null
echo "Installing phpMyAdmin RPM for Codendi...."
cd ${RPMS_DIR}/phpMyAdmin
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`

# Munin
echo "Removing installed Munin if any .."
$RPM -e --allmatches `rpm -qa 'munin*' 'perl-HTML-Template*' 'perl-Net-Server' 'perl-rrdtool*' 'rrdtool*' 'perl-Crypt-DES' 'perl-Net-SNMP' 'perl-Config-General'` 2>/dev/null
echo "Installing Munin RPMs for Codendi...."
cd ${RPMS_DIR}/munin
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM --nosignature -Uvh ${newest_rpm}/perl-Net-Server*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Crypt-DES*.i386.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Net-SNMP-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-Config-General-*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/perl-HTML-Template*.noarch.rpm
$RPM --nosignature -Uvh ${newest_rpm}/rrdtool-*.i386.rpm ${newest_rpm}/perl-rrdtool-*.i386.rpm
$RPM -Uvh ${newest_rpm}/munin-node-*.noarch.rpm
$RPM -Uvh ${newest_rpm}/munin-1*.noarch.rpm


#####
# Codendi RPMS

# -> codendi-jri
echo "Removing installed CodeX JRI if any .."
$RPM -e --allmatches codex-jri 2>/dev/null
echo "Installing Codendi JRI RPM...."
cd ${RPMS_DIR}/codendi-jri
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-jri-*noarch.rpm


# -> codendi-eclipse
echo "Removing installed Eclipse plugin if any .."
$RPM -e --allmatches codex-eclipse 2>/dev/null
echo "Installing Eclipse plugin RPM...."
cd ${RPMS_DIR}/codendi-eclipse
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-eclipse-*noarch.rpm

# -> codendi-salome-tmf
echo "Removing installed SalomeTMF plugin if any .."
$RPM -e --allmatches codex-salome-tmf 2>/dev/null
echo "Installing SalomeTMF plugin RPM...."
cd ${RPMS_DIR}/codendi-salome-tmf
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/codendi-salome-tmf-*noarch.rpm
        




# /cvsroot and /svnroot
rm /cvsroot
rm /svnroot
ln -s $VAR_LIB_DIR/cvsroot /cvsroot
ln -s $VAR_LIB_DIR/svnroot /svnroot

dbauth_passwd="a"; dbauth_passwd2="b";
while [ "$dbauth_passwd" != "$dbauth_passwd2" ]; do
    read -s -p "Password for DB Authentication user: " dbauth_passwd
    echo
    read -s -p "Retype password for DB Authentication user: " dbauth_passwd2
    echo
done


#
# Install New dist files
#

# libnss-mysql
for f in /etc/libnss-mysql.cfg  /etc/libnss-mysql-root.cfg /etc/httpd/conf.d/auth_mysql.conf; do
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


# replace strings in libnss-mysql config files
substitute '/etc/libnss-mysql.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
substitute '/etc/libnss-mysql-root.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
$CHMOD 600 /etc/libnss-mysql.cfg
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
        /sbin/service httpd reload 2> /dev/null || true
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
chmod 751 $VAR_LIB_DIR/cvsroot/
chmod 751 $VAR_LIB_DIR/svnroot/
chmod 771 /home/users
chmod 771 /home/groups

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
todo "Please note that Windows shares (with Samba) are no longer supported for security reasons"


###############################################################################
echo "Updating local.inc"

# Remove $sys_win_domain XXX ???


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


###############################################################################
# HTTP-based authentication
echo "Moving /etc/httpd/conf/htpasswd to /etc/httpd/conf/htpasswd.codendi4.0"
echo "This file is no longer needed (now using MySQL based authentication with mod_auth_mysql)"

if [ -f "/etc/httpd/conf/htpasswd" ]; then
  $MV /etc/httpd/conf/htpasswd /etc/httpd/conf/htpasswd.codendi4.0
fi

echo "Update munin.conf accordingly"
# replace string patterns in munin.conf (for MySQL authentication)
substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 


##############################################
# Analyze site-content 
#
echo "Analysing your site-content (in $ETC_DIR/site-content/)..."

#Only in etc => removed
removed=`$DIFF -q -r \
 $ETC_DIR/site-content/ \
 $INSTALL_DIR/site-content/        \
 | grep -v '.svn'  \
 | sed             \
 -e "s|^Only in $ETC_DIR/site-content/\([^:]*\): \(.*\)|@\1/\2|g" \
 -e "/^[^@]/ d"  \
 -e "s/@//g"     \
 -e '/^$/ d'`
if [ "$removed" != "" ]; then
  echo "The following files doesn't existing in the site-content of Codendi:"
  echo "$removed"
fi

#Differ => modified
one_has_been_found=0
for i in `$DIFF -q -r \
            $ETC_DIR/site-content/ \
            $INSTALL_DIR/site-content/        \
            | grep -v '.svn'  \
            | sed             \
            -e "s|^Files $ETC_DIR/site-content/\(.*\) and $INSTALL_DIR/site-content/\(.*\) differ|@\1|g" \
            -e "/^[^@]/ d"  \
            -e "s/@//g"     \
            -e '/^$/ d'` 
do
   if [ $one_has_been_found -eq 0 ]; then
      echo "  The following files differ from the site-content of Codendi:"
      one_has_been_found=1
   fi
   echo "    $i"
done

if [ $one_has_been_found -eq 1 ]; then
   echo "  Please check those files"
fi

echo "Analysis done."

##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the database..."

$SERVICE mysqld start
sleep 5



pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"


echo "Starting DB update for Codendi 4.0 This might take a few minutes."

echo "- rename codex db as codendi"
mysqldump -u root $pass_opt codex > /tmp/dump.codex.sql
$MYSQL -u root $pass_opt mysql -e "DROP DATABASE codex; CREATE DATABASE codendi;"
$MYSQL -u root $pass_opt codendi < /tmp/dump.codex.sql

mysqldump -u root $pass_opt mysql > /tmp/dump.mysql.sql
substitute '/tmp/dump.mysql.sql' 'codex' 'codendi'
$MYSQL -u root $pass_opt mysql < /tmp/dump.mysql.sql


echo "- Create dbauthuser, needed for MySQL-based authentication for HTTP (SVN), libNSS-mysql and Openfire"
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT SELECT ON codendi.user to dbauthuser@localhost identified by '$dbauth_passwd';
GRANT SELECT ON codendi.groups to dbauthuser@localhost;
GRANT SELECT ON codendi.user_group to dbauthuser@localhost;
GRANT SELECT ON codendi.session to dbauthuser@localhost;
FLUSH PRIVILEGES;
EOF


echo "- Add support for > 2GB files in DB (FRS and Wiki)"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE frs_file CHANGE file_size file_size BIGINT NOT NULL DEFAULT '0';
ALTER TABLE wiki_attachment_revision CHANGE size size BIGINT NOT NULL;
EOF

echo "- Remove nobody permissions FRS and update permissions table"
$CAT <<EOF | $MYSQL $pass_opt codendi
DELETE FROM permissions_values WHERE (permission_type='PACKAGE_READ' or permission_type='RELEASE_READ') and ugroup_id='100';
UPDATE permissions SET ugroup_id='4' WHERE (permission_type='PACKAGE_READ' or permission_type='RELEASE_READ') and ugroup_id='100';
EOF

echo "- Remove useless tables"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE user ADD COLUMN approved_by int(11) NOT NULL default '0' AFTER add_date;
EOF


echo "- Windows password no longer needed"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE user DROP COLUMN windows_pw;
EOF

echo "- Table structure for System Events"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
EOF


echo "- Artifact permissions"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE artifact ADD COLUMN use_artifact_permissions tinyint(1) NOT NULL DEFAULT '0' AFTER group_artifact_id;

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',15);
EOF


echo "- Mandatory reference in SVN commit message"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE groups 
    ADD svn_mandatory_ref TINYINT NOT NULL DEFAULT '0' AFTER svn_tracker,
    ADD svn_accessfile text NULL AFTER svn_preamble;
EOF

echo "- Cross references : add a new field 'nature'"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE reference ADD nature VARCHAR( 64 ) NOT NULL;
EOF

echo "- Set the nature for existing references"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
      );
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
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE cross_references CHANGE source_id source_id VARCHAR( 128 ) NOT NULL DEFAULT '0';
ALTER TABLE cross_references CHANGE target_id target_id VARCHAR( 128 ) NOT NULL DEFAULT '0';
EOF

echo "- Cross references : add two fields"
$CAT <<EOF | $MYSQL $pass_opt codendi
ALTER TABLE cross_references ADD source_keyword VARCHAR( 32 ) NOT NULL AFTER source_type;
ALTER TABLE cross_references ADD target_keyword VARCHAR( 32 ) NOT NULL AFTER target_type;
EOF

echo "- Change type of existing cross references from 'revision_svn' to 'svn_revision'"
$CAT <<EOF | $MYSQL $pass_opt codendi
UPDATE cross_references SET source_type = 'svn_revision' WHERE source_type LIKE 'revision_svn';
UPDATE cross_references SET target_type = 'svn_revision' WHERE target_type LIKE 'revision_svn';
EOF

echo "- Set keywords"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
$CAT <<EOF | $MYSQL $pass_opt codendi
UPDATE reference
SET service_short_name = 'tracker'
WHERE scope = 'P'
AND (service_short_name = '' OR service_short_name IS NULL)
AND link LIKE '/tracker/%func=detail%';
EOF

echo "- add new reference for IM chat"
$CAT <<EOF | $MYSQL $pass_opt codendi
INSERT INTO reference SET 
    keyword='chat', 
    description='plugin_im:reference_chat_desc_key', 
    link='/plugins/IM/?group_id=$group_id&action=viewchatlog&chat_log=$1', 
    scope='S', 
    service_short_name='IM',
    nature='im_chat';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups; 
EOF

# IM plugin
# TODO : stop openfire service ($SERVICE openfire stop)

echo "- Add IM service"
$CAT <<EOF | $MYSQL $pass_opt codendi
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=$group_id', 1 , 1 , 'system',  210 );
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
$CAT <<EOF | $MYSQL $pass_opt codendi
GRANT SELECT ON codendi.session to openfireadm@localhost;
FLUSH PRIVILEGES;
EOF

# IM openfire configuration
echo "- Specific configuration for webmuc"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
$CAT <<EOF | $MYSQL $pass_opt codendi
CREATE TABLE plugin_hudson_job (
  job_id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  group_id int(11) NOT NULL ,
  job_url varchar(255) NOT NULL ,
  name varchar(128) NOT NULL ,
  use_svn_trigger tinyint(4) NOT NULL default 0 ,
  use_cvs_trigger tinyint(4) NOT NULL default 0 ,
  token varchar(128) NOT NULL
);
CREATE TABLE plugin_hudson_widget (
  id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  widget_name varchar(64) NOT NULL ,
  owner_id int(11) UNSIGNED NOT NULL ,
  owner_type varchar(1) NOT NULL ,
  job_id int(11) NOT NULL
);
EOF

echo "- Add hudson service"
$CAT <<EOF | $MYSQL $pass_opt codendi
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=$group_id', 1 , 1 , 'system',  220 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=1', 1 , 0 , 'system',  220 );
# Create hudson service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', CONCAT('/plugins/hudson/?group_id=', group_id), 1 , 0 , 'system',  220
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'hudson');
EOF


echo "- Update user language"
$CAT <<EOF | $MYSQL $pass_opt codendi
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
$CAT <<EOF | $MYSQL $pass_opt codendi
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

echo "- Delete hide_members column"
$CAT <<EOF | $MYSQL $pass_opt codendi
# (not needed anymore, please do it after previous request)
ALTER TABLE groups DROP hide_members;
EOF

echo "- Add cvs_is_private"
$CAT <<EOF | $MYSQL $pass_opt codendi 
ALTER TABLE groups ADD cvs_is_private TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER cvs_preamble ;
EOF

echo "- Layouts for dashboard"
$CAT <<EOF | $MYSQL $pass_opt codendi 
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
EOF

echo "- Upgrade docman"
$CAT <<EOF | $MYSQL $pass_opt codendi 
ALTER TABLE plugin_docman_approval CHANGE COLUMN version_id version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL;
ALTER TABLE plugin_docman_approval CHANGE COLUMN wiki_version_id wiki_version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL;
EOF

echo "- Perfs"
mysql_drop_index 'artifact_field_value' 'idx_field_id'
mysql_drop_index 'artifact_field_value' 'idx_artifact_id'
mysql_drop_index 'artifact_field_value' 'idx_art_field_id'
mysql_drop_index 'artifact_field_value' 'valueInt'
mysql_add_index  'artifact_field_value' 'idx_valueInt' 'artifact_id, field_id, valueInt'
mysql_add_index  'artifact_field_value' 'xtrk_valueInt' 'valueInt'
mysql_add_index  'user'                 'idx_user_name' 'user_name(10)'
mysql_drop_index 'artifact_field_usage' 'idx_fk_field_id'
mysql_drop_index 'artifact_field_usage' 'idx_fk_group_artifact_id'
mysql_add_index  'artifact_field_value' 'idx_fk' 'field_id, group_artifact_id'

echo "- Files can now be browsed and downloaded by anonymous users (default permissions do not change, we only allow it)"
$CAT <<EOF | $MYSQL $pass_opt codendi 
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
            SET cvs_is_private 
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
$CAT <<EOF | $MYSQL $pass_opt codendi
UPDATE plugin
SET name = 'codendijri'
WHERE name = 'codexjri';
EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aaos $pass_opt

##############################################
# Upgrade to SVN 1.6
#
echo "Upgrade repositories to SVN 1.6"
find /svnroot/ -maxdepth 1 -mindepth 1 -name "*" -exec sudo -u codendiadm svnadmin upgrade {} \; >/dev/null


###############################################################################
# Create 'private' directories in /home/group/
echo "Creating private directories in /home/group/"
find /home/groups/ -maxdepth 1 -mindepth 1 -type d -exec mkdir -v --context=root:object_r:httpd_sys_content_t --mode=2770 '{}/private' \; -exec chown dummy '{}/private' \;


###############################################################################
# Remove old backend script from crontab
echo "Add new system scripts in root crontab"
$CAT <<'EOF' > /tmp/root_cronfile
# Once a minute, process Codendi system events
* * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./process_system_events.php)
#
# Regularly launch a system_check event (e.g. every half-hour) 
0,30 * * * * (cd /usr/share/codendi/src/utils; ./php-launcher.sh ./launch_system_check.php)
#
EOF

echo "Remove xerox_crontab script from root crontab"
crontab -u root -l >> /tmp/root_cronfile
$PERL -i'.orig' -p -e's/^(.*xerox_crontab.sh.*)$/#\1/' /tmp/root_cronfile

crontab -u root /tmp/root_cronfile


##############################################
# Fix SELinux contexts if needed
#
echo "Update SELinux contexts if needed"
cd $INSTALL_DIR/src/utils
./fix_selinux_contexts.pl

codendification '/etc/httpd/conf/httpd.conf'

##############################################
# Restarting some services
#
echo "Starting services..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start



Verifier qu'il y a assez de place sur le disque avant de faire les backup et l'installation
 df -h /big/ | awk '{print $4}' | tail -n 1
=> donne la taille disponible pour la partition qui contient /big/

Codendification:
- /var/lib/mysql/codex-bin*


TODO migrate CodeX* themes (in file and in db and in plugins)
TODO migrate User-Agent (Dont allow access to API for anyone.)

TODO use functions for indexes

TODO: migrate .CODEX_PRIVATE

# IM / Webchat configuration
SYS_DEFAUL_DOMAIN=`$GREP '^\$sys_default_domain' $ETC_DIR/codendi/conf/local.inc | /bin/sed -e 's/\$sys_default_domain\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/database_im.tpl.inc $ETC_DIR/plugins/IM/etc/database_im.inc
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_HOST__}' "$SYS_DEFAUL_DOMAIN"
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_USER__}' "openfireadm"
substitute "$ETC_DIR/plugins/IM/etc/database_im.inc" '{__OPENFIRE_DB_NAME__}' "openfire"
# TODO : substitute {__OPENFIRE_DB_PASSWORD__} -> value available in /opt/openfire/conf/openfire.xml : <jive><database><defaultProvider><password> value here! </password>

# TODO : Modify openfire/conf/openfire.xml : 
# TODO : $xml->provider->auth->className update node to CodexJDBCAuth
# TODO : $xml->jdbcAuthProvider->addChild('codexUserSessionIdSQL', "SELECT session_hash FROM session WHERE session.user_id = (SELECT user_id FROM user WHERE user.user_name = ?)");
# copy jar file into openfire lib dir
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/codendi_auth.jar /opt/openfire/lib/.
# TODO : update httpd.conf and codendi_aliases.conf (see rev #10208 for details)
# Instal monitoring plugin (copy plugin jar in openfire plugin dir)
$CP ${RPMS_DIR}/openfire/monitoring.jar /opt/openfire/plugins

# Add common stylesheet in custom themes

#custom themes
=> no more images
=> refactoring in common/layout instead of www/include

#TODO Clean-up CodendiBlack (fix blue labels on IE, ...)
#TODO remove reserved names javascript

#
# TODO: add these lines to /etc/my.cnf under [mysqld]
#

TODO : Déplacer le script de debug dans Layout.class.php
# TODO : CREATE / UPDATE the pre-commit hook for every existing project.

#
# Todo, modify fileforge.c and recompile for new layout., and reinstall

#
# TODO CODENDIFICATION:
#
# - replace "CODEX BLOCK" by "CODENDI BLOCK" in /etc/cvsnt/PServer
# - httpd.conf: replace CODEX_LOCAL_INC by CODENDI_LOCAL_INC
# - migrate all CodeX blocks in cvs and svn repositories!:
#    "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
#    "# END OF NEEDED CODEX BLOCK";
#   Becomes
#    "# !!! Codendi Specific !!! DO NOT REMOVE (NEEDED CODENDI MARKER)";
#    "# END OF NEEDED CODENDI BLOCK";
#   in:
#   /cvsroot/*/CVSROOT/loginfo
#   /cvsroot/*/CVSROOT/commitinfo
#   /cvsroot/*/CVSROOT/notify
#   /svnroot/*/hooks/post-commit
# - in /svnroot/*/.SVNAccessFile, replace (twice) "CODEX DEFAULT SETTINGS" by "CODENDI DEFAULT SETTINGS"
# - uninstall codex-* rpm (codex-jri, codex-eclipse and codex-salome) and reinstall codendi-* ones
# - /etc/my.cnf: log-bin=codex-bin
# - rename /etc/httpd/cond/codex_aliases.conf
# ????# Create .subversion directory in codexadm home dir.
# ????su -c 'svn info --non-interactive https://partners.xrce.xerox.com/svnroot/codex/dev/trunk' - codexadm 2> /dev/null &
#??? Mailman: codex-admin??
##$PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to CodeX FTP service./g" /etc/vsftpd/vsftpd.conf 
# - /var/lib/codex/ftp/.message contains 'CodeX'
# - Update root and codexadm crontab (partially done)
# - /etc/logrotate.d/httpd and /etc/logrotate.d/vsftpd.log conatain paths with '/codex/'
# - /etc/profile contains reference to '/etc/profile_codex'
# - rename /etc/profile_codex
# - /etc/profile_codex contains "C O D E X" and "CodeX"
# - MySQL: 'codex' db, codexadm user and grants on codex DB.
# - Warn admins that CODEX_LOCAL_INC was replaced by CODENDI_LOCAL_INC
# - OpenFire install.
# - Migrate all CodeX in /etc/codendi (site-content, etc.)
# - change codex project short name on Partners
# - custom themes:'codex' in theme CSS -> codendi .
# - CODEX_LICENSE_ACCEPTED???
#
# Update install/admin guides with new backend system.
#
# remove references to sys_win_domain in documentation (Windows support..)
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

cd $INSTALL_DIR/src/utils/svn
$CP commit-email.pl codendi_svn_pre_commit.php /usr/lib/codendi/bin
cd /usr/lib/codendi/bin
$CHOWN codendiadm.codendiadm commit-email.pl codendi_svn_pre_commit.php
$CHMOD 755 commit-email.pl codendi_svn_pre_commit.php

# TODO: replace codex by codendi in /etc/shells


  # Skip logging openfire db (for instant messaging)
  # The 'monitor' openrfire plugin creates large codendi-bin files
  # Comment this line if you prefer to be safer.
  set-variable  = binlog-ignore-db=openfire

#


##############################################
# Generate Documentation
#
echo "Generating the Codendi Manuals. This will take a few minutes."
su -c "$INSTALL_DIR/src/utils/generate_doc.sh -f" - codendiadm 2> /dev/null &
su -c "$INSTALL_DIR/src/utils/generate_programmer_doc.sh -f" - codendiadm 2> /dev/null &
su -c "$INSTALL_DIR/src/utils/generate_cli_package.sh -f" - codendiadm 2> /dev/null &
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR/documentation
$CHOWN -R codendiadm.codendiadm $INSTALL_DIR/downloads



todo "Note to Codendi Developers: "
todo " - Some deprecated functions have been removed: group_getname, group_getunixname, group_get_result, group_get_object, project_get_object"





















# End of it
echo "=============================================="
echo "Migration completed succesfully!"

exit 1;
