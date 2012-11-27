#!/bin/bash
#
# Copyright (c) Enalean, Tuleap 2011,2012
# Copyright (c) STMicroelectronics, Codex 2009,2010
# Copyright (c) Xerox Corporation, Codendi 2001-2009.
#
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
#      Originally written by Laurent Julliard 2004, Codendi Team, Xerox
#

# In order to keep a log of the installation, you may run the script with:
# ./setup.sh 2>&1 | tee /root/tuleap_install.log

TODO_FILE=/root/todo_tuleap.txt
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
MKDIR='/bin/mkdir'
RPM='/bin/rpm'
CHOWN='/bin/chown'
CHGRP='/bin/chgrp'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
MYSQL='/usr/bin/mysql'
MYSQLSHOW='/usr/bin/mysqlshow'
TOUCH='/bin/touch'
CAT='/bin/cat'
TAIL='/usr/bin/tail'
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'
DIFF='/usr/bin/diff'
PHP='/usr/bin/php'
UNAME='/bin/uname'
YUM='/usr/bin/yum -qy'
INSTALL='/usr/bin/install'

CHCON='/usr/bin/chcon'
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
if [ -e /etc/selinux/config ]
then
	$GREP -i -q '^SELINUX=disabled' /etc/selinux/config
	if [ $? -eq 0 ] || [ ! -e $CHCON ] ; then
		# SELinux not installed
		SELINUX_ENABLED=0
	fi
else
	if [ ! -e $CHCON ] ; then
		# SELinux not installed
		SELINUX_ENABLED=0
	fi
fi


CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT TAIL GREP CHKCONFIG \
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

generate_passwd() {
    $CAT /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 9 | head -1
}

##############################################
# Setup chunks
##############################################

###############################################################################
#
# CVS configuration
#
setup_cvs() {
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

    $CHKCONFIG cvs on
    $CHKCONFIG xinetd on

    $SERVICE xinetd start
}

###############################################################################
#
# FTP server configuration
#
setup_vsftpd() {
    # Configure vsftpd
    $PERL -i'.orig' -p -e "s/^#anon_upload_enable=YES/anon_upload_enable=YES/g" /etc/vsftpd/vsftpd.conf 
    $PERL -pi -e "s/^#ftpd_banner=.*/ftpd_banner=Welcome to Tuleap FTP service./g" /etc/vsftpd/vsftpd.conf 
    $PERL -pi -e "s/^local_umask=.*/local_umask=002/g" /etc/vsftpd/vsftpd.conf 

    # Add welcome messages
    $CAT <<'EOF' > /var/lib/codendi/ftp/.message
********************************************************************
Welcome to Tuleap FTP server

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

    # Log Rotate
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

    # Start service
    $CHKCONFIG vsftpd on
    $SERVICE vsftpd start
}


###############################################################################
#
# Bind DNS server configuration
#
setup_bind() {
    if [ -f /var/named/chroot/var/named/codendi.zone ]; then
        $CP -af /var/named/chroot/var/named/codendi.zone /var/named/chroot/var/named/codendi.zone.orig
    fi
    $CP -f $INSTALL_DIR/src/etc/codendi.zone /var/named/chroot/var/named/codendi.zone

    $CHOWN root:named /var/named/chroot/var/named/codendi.zone
    if [ -f "/var/named/chroot/etc/named.conf" ]; then
        $CHGRP named /var/named/chroot/etc/named.conf
    fi

    if [ $SELINUX_ENABLED ]; then
        $CHCON -h system_u:object_r:named_zone_t /var/named/chroot/var/named/codendi.zone
        if [ -f "/var/named/chroot/etc/named.conf" ]; then
            $CHCON -h system_u:object_r:named_conf_t /var/named/chroot/etc/named.conf
        fi
    fi

  # replace string patterns in codendi.zone
  sys_shortname=`echo $sys_fullname | $PERL -pe 's/\.(.*)//'`
  dns_serial=`date +%Y%m%d`01
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_default_domain%' "$sys_default_domain" 
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_fullname%' "$sys_fullname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_ip_address%' "$sys_ip_address"
  substitute '/var/named/chroot/var/named/codendi.zone' '%sys_shortname%' "$sys_shortname"
  substitute '/var/named/chroot/var/named/codendi.zone' '%dns_serial%' "$dns_serial"

  todo "Create the DNS configuration files as explained in the Tuleap Installation Guide:"
  todo "    update /var/named/chroot/var/named/codendi.zone - replace all words starting with %%."
  todo "    make sure the file is readable by 'other':"
  todo "      > chmod o+r /var/named/chroot/var/named/codendi.zone"
  todo "    edit /etc/named.conf to create the new zone."


  $CHKCONFIG named on
  $SERVICE named start
}

###############################################################################
#
# Mailman configuration
#
setup_mailman() {
    echo "Configuring Mailman..."

    # Setup admin password
    /usr/lib/mailman/bin/mmsitepass $mm_passwd

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

    $CHKCONFIG mailman on
    $SERVICE mailman start </dev/null >/dev/null 2>/dev/null &
}

###############################################################################
#
# Mysql configuration
#
setup_mysql() {
    echo "Creating the Tuleap database..."

    # If DB is local, mysql password where not already tested
    pass_opt=""
    if [ -z "$mysql_host" ]; then
        # See if MySQL root account is password protected
        $MYSQLSHOW -uroot 2>&1 | grep password
        while [ $? -eq 0 ]; do
            read -s -p "Existing DB is password protected. What is the Mysql root password?: " old_passwd
            echo
            $MYSQLSHOW -uroot --password=$old_passwd 2>&1 | grep password
        done
        if [ "X$old_passwd" != "X" ]; then
            pass_opt="-uroot --password=$old_passwd"
        else
            pass_opt="-uroot"
        fi
    else
        pass_opt="-uroot --password=$rt_passwd"
    fi

    # Test if tuleap DB already exists
    yn="-"
    freshdb=0
    if $MYSQLSHOW $pass_opt | $GREP codendi 2>&1 >/dev/null; then
        read -p "Tuleap Database already exists. Overwrite? [y|n]:" yn
    fi

    # Delete the Tuleap DB if asked for
    if [ "$yn" = "y" ]; then
        $MYSQL $pass_opt -e "DROP DATABASE codendi"
    fi

    # If no codendi, create it!
    if ! $MYSQLSHOW $pass_opt | $GREP codendi 2>&1 >/dev/null; then
        freshdb=1
        $MYSQL $pass_opt -e "CREATE DATABASE codendi DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
        $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT ALL PRIVILEGES on *.* to 'codendiadm'@'$mysql_httpd_host' identified by '$codendiadm_passwd' WITH GRANT OPTION;
REVOKE SUPER ON *.* FROM 'codendiadm'@'$mysql_httpd_host';
GRANT ALL PRIVILEGES on *.* to 'root'@'$mysql_httpd_host' identified by '$rt_passwd';
FLUSH PRIVILEGES;
EOF
    fi
    # Password has changed
    pass_opt="-uroot --password=$rt_passwd"

    if [ $freshdb -eq 1 ]; then
        echo "Populating the Tuleap database..."
        cd $INSTALL_DIR/src/db/mysql/
        $MYSQL -u codendiadm codendi --password=$codendiadm_passwd < database_structure.sql   # create the DB
        cp database_initvalues.sql /tmp/database_initvalues.sql
        substitute '/tmp/database_initvalues.sql' '_DOMAIN_NAME_' "$sys_default_domain"
        $MYSQL -u codendiadm codendi --password=$codendiadm_passwd < /tmp/database_initvalues.sql  # populate with init values.
        rm -f /tmp/database_initvalues.sql

        # Create dbauthuser
        $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT SELECT ON codendi.user to dbauthuser@$mysql_httpd_host identified by '$dbauth_passwd';
GRANT SELECT ON codendi.groups to dbauthuser@$mysql_httpd_host;
GRANT SELECT ON codendi.user_group to dbauthuser@$mysql_httpd_host;
FLUSH PRIVILEGES;
EOF
    fi
}

###############################################################################
#
# Mysql sanity check
#
test_mysql_host() {
    echo -n "Testing Mysql connexion means... "
    # Root access: w/o password
    if [ -z "$rt_passwd" ]; then
        if ! $MYSQLSHOW -uroot >/dev/null 2>&1; then
            die "You didn't provide any root password for $mysql_host but one seems required"
        fi
    fi
    if ! $MYSQLSHOW -uroot -p$rt_passwd >/dev/null 2>&1; then
        die "The Mysql root password you provided for $mysql_host doesn't work"
    fi
    echo "[OK]"
}

###############################################################################
#
# Usage
#
usage() {
    cat <<EOF
Usage: $1 [options]
Options:
  --auto-passwd                    Automaticaly generate random passwords
  --without-bind-config            Do not setup local DNS server
  --disable-subdomains		   Disable subdomain

  Mysql configuration (if database on remote server):
  --sys-default-domain=<domain>	   Server Domain name
  --sys-fullname=<fqdn>            Server fully qualified machine name
  --sys-ip-address=<ip address>    Server IP address
  --sys-org-name=<string>          Your Company short name
  --sys-long-org-name=<string>     Your Company long name
  --mysql-host=<host>              Hostname (or IP) of mysql server
  --mysql-port=<integer>           Port if not default (3306)
  --mysql-root-password=<password> Mysql root user password on remote host
  --mysql-httpd-host=<host>        Name or IP of the current server as seen by
                                   remote host
EOF
    exit 1
}

##############################################
# Tuleap installation
##############################################
sys_default_domain=""
sys_fullname=""
sys_ip_address=""
sys_org_name=""
sys_long_org_name=""
disable_subdomains=""

auto=""
auto_passwd=""
configure_bind=""
mysql_host=""
mysql_port=""
mysql_httpd_host="localhost"
rt_passwd=""

options=`getopt -o h -l auto,auto-passwd,without-bind-config,mysql-host:,mysql-port:,mysql-root-password:,mysql-httpd-host:,sys-default-domain:,sys-fullname:,sys-ip-address:,sys-org-name:,sys-long-org-name:,disable-subdomains -- "$@"`

if [ $? != 0 ] ; then echo "Terminating..." >&2 ; usage $0 ;exit 1 ; fi

eval set -- "$options"

while true
do
    case "$1" in
	--auto)
		auto_passwd="true";
		configure_bind="false"
		disable_subdomains="y"
		sys_default_domain="`hostname -f`"
		sys_fullname="`hostname -f`"
		sys_ip_address="127.0.1.1"
		sys_org_name="Tuleap"
		sys_long_org_name="Tuleap ALM"
		#rt_passwd="`dd if=/dev/urandom count=1 bs=16 2> /dev/null | md5sum | cut -c-32`"
		auto_passwd="true"
		mysql_host="localhost"
		MYSQL="$MYSQL -h$mysql_host"
		MYSQLSHOW="$MYSQLSHOW -h$mysql_host"
		shift 1 ;;
	--auto-passwd)
		auto_passwd="true";shift 1 ;;
        --without-bind-config)
		configure_bind="false";shift 1 ;;
	--disable-subdomains)
		disable_subdomains="y"; shift 1 ;;
	--sys-default-domain)
		sys_default_domain="$2" ; shift 2 ;;
	--sys-fullname)
		sys_fullname="$2" ; shift 2 ;;
	--sys-ip-address)
		sys_ip_address="$2" ; shift 2 ;;
	--sys-org-name)
		sys_org_name="$2" ; shift 2 ;;
	--sys-long-org-name)
		sys_long_org_name="$2" ; shift 2 ;;
	--mysql-host) 
		mysql_host="$2";shift 2
		MYSQL="$MYSQL -h$mysql_host"
		MYSQLSHOW="$MYSQLSHOW -h$mysql_host"
		;;
	--mysql-port)
		mysql_port="$2";shift 2
		MYSQL="$MYSQL -P$mysql_port"
		MYSQLSHOW="$MYSQLSHOW -P$mysql_port"
		;;
	--mysql-root-password)
		rt_passwd="$2";shift 2
		;;
	--mysql-httpd-host)
		mysql_httpd_host="$2";shift 2 ;;
	-h|--help)
		usage $0 ;;
        --)
		shift 1; break ;;
        *)
		break ;;
    esac
done

if [ ! -z "$mysql_host" ]; then
    test_mysql_host
else
    if ! $RPM -q mysql-server 2>&1 >/dev/null; then
	die "No --mysql-host nor local mysql server installed, exit. Please install 'mysql-server' package"
    fi
fi

##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done


##############################################
# Check release
#
RH_RELEASE="5"
RH_UPDATE="6"

minor_version=`$RPM -qa | grep -P "^(centos|redhat)-release-${RH_RELEASE}" | sed -rn 's/(centos|redhat)-release-5-(.).*/\2/p'`
if [ "x$minor_version" != x ] && [ "$minor_version" -ge "$RH_UPDATE" ]; then
    echo "Running on RHEL or CentOS ${RH_RELEASE}... good!"
else
  cat << EOF
This machine is not running RedHat Enterprise Linux or CentOS ${RH_RELEASE}.${RH_UPDATE}
You should consider to upgrade your system before going any further (yum upgrade).
EOF
  read -p "Continue? [y|n]: " yn
  if [ "$yn" = "n" ]; then
      echo "Bye now!"
      exit 1
  fi
fi

# Check if IM plugin is installed
enable_plugin_im="false"
if [ -d "$INSTALL_DIR/plugins/IM" ]; then
    enable_plugin_im="true"
fi

enable_plugin_tracker="false"
if [ -d "$INSTALL_DIR/plugins/tracker" ]; then
    enable_plugin_tracker="true"
fi

enable_plugin_graphontrackersv5="false"
if [ -d "$INSTALL_DIR/plugins/graphontrackersv5" ]; then
    enable_plugin_graphontrackersv5="true"
fi


# Check if mailman is installed
enable_core_mailman="false"
if $RPM -q mailman-tuleap 2>&1 >/dev/null; then
    enable_core_mailman="true"
fi

# Check if munin is installed
enable_munin="false"
if $RPM -q munin 2>&1 >/dev/null; then
    enable_munin="true"
fi



rm -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE TULEAP INSTALLATION (see $TODO_FILE)"

echo
echo "Configuration questions"
echo

# Ask for domain name and other installation parameters
if [ -z "$sys_default_domain" ]
then
	read -p "Tuleap Domain name: " sys_default_domain
fi
if [ -z "$sys_fullname" ]
then
	read -p "Tuleap Server fully qualified machine name: " sys_fullname
fi
if [ -z "$sys_ip_address" ]
then
	read -p "Tuleap Server IP address: " sys_ip_address
fi
if [ -z "$sys_org_name" ]
then
	read -p "Your Company short name: " sys_org_name
fi
if [ -z "$sys_long_org_name" ]
then
	read -p "Your Company long name: " sys_long_org_name
fi
if [ -z "$disable_subdomains" ]
then
	read -p "Disable sub-domain management (no DNS delegation)? [y|n]:" disable_subdomains
fi

if [ "$disable_subdomains" != "y" ]; then
    if [ "$configure_bind" != "false" ]; then
        configure_bind="true"
    fi
else
    configure_bind="false"
fi

if [ "$auto_passwd" = "true" ]; then
    # Save in /root/.tuleap_passwd
    passwd_file=/root/.tuleap_passwd
    $RM -f $passwd_file
    touch $passwd_file
    $CHMOD 0600 $passwd_file

    # Mysql Root password (what if remote DB ?)
    if [ -z "$rt_passwd" ]; then
        rt_passwd=$(generate_passwd)
        echo "Mysql root (root): $rt_passwd" >> $passwd_file
    fi

    # For both DB and system
    codendiadm_passwd=$(generate_passwd)
    echo "Codendiadm unix & DB (codendiadm): $codendiadm_passwd" >> $passwd_file

    # Mailman (only if installed)
    if [ "$enable_core_mailman" = "true" ]; then
        mm_passwd=$(generate_passwd)
        echo "Mailman siteadmin: $mm_passwd" >> $passwd_file
    fi

    # Openfire (only if installed)
    if [ "$enable_plugin_im" = "true" ]; then
        openfire_passwd=$(generate_passwd)
        echo "Openfire DB user (openfireadm): $openfire_passwd" >> $passwd_file
    fi

    # Only for ftp/ssh/cvs
    dbauth_passwd=$(generate_passwd)
    echo "Libnss-mysql DB user (dbauthuser): $dbauth_passwd" >> $passwd_file

    # Ask for site admin ?

    todo "Automatically generated passwords are stored in $passwd_file"
else
    # Ask for user passwords

    if [ -z "$rt_passwd" ]; then
        rt_passwd="a"; rt_passwd2="b";
        while [ "$rt_passwd" != "$rt_passwd2" ]; do
            read -s -p "Password for MySQL root: " rt_passwd
            echo
            read -s -p "Retype MySQL root password: " rt_passwd2
            echo
        done
    fi

codendiadm_passwd="a"; codendiadm_passwd2="b";
while [ "$codendiadm_passwd" != "$codendiadm_passwd2" ]; do
    read -s -p "Password for user codendiadm: " codendiadm_passwd
    echo
    read -s -p "Retype codendiadm password: " codendiadm_passwd2
    echo
done

if [ "$enable_core_mailman" = "true" ]; then
    mm_passwd="a"; mm_passwd2="b";
    while [ "$mm_passwd" != "$mm_passwd2" ]; do
        read -s -p "Password for user mailman: " mm_passwd
        echo
        read -s -p "Retype mailman password: " mm_passwd2
        echo
    done
fi

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

fi

# Update tuleap user password
echo "$codendiadm_passwd" | passwd --stdin codendiadm

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

# Reduce default inactive timeout (prevent DB overload in case of nscd
# crash)
set-variable=wait_timeout=180

# Innodb settings
innodb_file_per_table

[mysql.server]
user=mysql
basedir=/var/lib

[mysqld_safe]
err-log=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EOF

if [ -z "$mysql_host" ]; then
    echo "Initializing MySQL: You can ignore additionnal messages on MySQL below this line:"
    echo "***************************************"
    $SERVICE mysqld start
    echo "***************************************"
fi


##############################################
# Install the Tuleap software 
#

echo "Installing configuration files..."
#echo " You should overwrite existing files"
make_backup /etc/httpd/conf/httpd.conf
for f in /etc/httpd/conf/httpd.conf \
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

# Bind config
if [ "$configure_bind" = "true" ]; then
    setup_bind
fi

###
#
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

# TODO: package it
# #Copying perl codendi module for mod_dav_svn authentication
# codendi_perl_module_dir='/usr/lib/perl5/vendor_perl/5.8.8/Apache'
# if [ ! -d $codendi_perl_module_dir ];then
#     $MKDIR -p $codendi_perl_module_dir
# fi
# $CP $INSTALL_DIR/src/utils/svn/Codendi.pm $codendi_perl_module_dir/Codendi.pm
# TODO: /etc/httpd/conf.d/perl.conf
# TODO: mod_perl perl-BSD-Resource libdbi-dbd-mysql libdbi libdbi-drivers 

# replace string patterns in local.inc
substitute '/etc/codendi/conf/local.inc' '%sys_default_domain%' "$sys_default_domain" 
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
substitute '/etc/codendi/conf/database.inc' 'localhost' "$mysql_host" 

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
if [ -f '/etc/httpd/conf.d/munin.conf' ]; then
    substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 
fi

# Make sure SELinux contexts are valid
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/codendi
fi

todo "Customize /etc/codendi/conf/local.inc and /etc/codendi/conf/database.inc"
todo "You may also want to customize /etc/httpd/conf/httpd.conf"

##############################################
# Installing phpMyAdmin
#

# Make codendiadm a member of the apache group
# This is needed to use the php session at /var/lib/php/session (e.g. for phpwiki)
$USERMOD -a -G apache codendiadm
# Allow read/write access to DAV lock dir for codendiadm in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

##############################################
# Installing the Tuleap database
#
setup_mysql

##############################################
# SSL Certificate creation

if [ "$create_ssl_certificate" = "y" ]; then
    $INSTALL_DIR/src/utils/generate_ssl_certificate.sh
fi


##############################################
# Mailman configuration
# RPM was intalled previously
#
if [ "$enable_core_mailman" = "true" ]; then
    setup_mailman
fi

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

#todo "Finish sendmail settings (see installation Guide). By default, emails sent to codendi-admin are redirected to root (see /etc/aliases)"

##############################################
# CVS
if $RPM -q cvs-tuleap 2>&1 >/dev/null; then
    setup_cvs
fi

##############################################
# Make the system daily cronjob run at 23:58pm
echo "Updating daily cron job in system crontab..."
$PERL -i'.orig' -p -e's/\d+ \d+ (.*daily)/58 23 \1/g' /etc/crontab

##############################################
# FTP
if $RPM -q vsftpd 2>&1 >/dev/null; then
    setup_vsftpd
fi

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

#todo "Customize /etc/codendi/site-content/en_US/others/default_page.php (project web site default home page)"
todo "Customize /etc/codendi/site-content information for your site."
todo "  For instance: contact/contact.txt "
todo ""
todo "Default admin credentials are login: admin / password: siteadmin"
todo "CHANGE DEFAULT CREDENTIALS BEFORE FIRST USAGE"

##############################################
# Crontab configuration
#
echo "Installing root user crontab..."
crontab -u root -l > /tmp/cronfile

$GREP -q "Tuleap" /tmp/cronfile
if [ $? -ne 0 ]; then
    $CAT <<'EOF' >>/tmp/cronfile
# Tuleap: weekly backup preparation (mysql shutdown, file dump and restart)
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

##############################################
# Create Tuleap profile script
#

# customize the global profile 
$GREP profile_codendi /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' >>/etc/profile
# Now the Part specific to Tuleap users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_codendi
fi
EOF

$CAT <<'EOF' >/etc/profile_codendi
# /etc/profile_codendi
#
# Specific login set up and messages for Tuleap users`
 
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
 
-----------------------------------
W E L C O M E   T O   T U L E A P !
-----------------------------------

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
The Tuleap server hosts very valuable yet publicly available
data. Therefore we recommend that you keep working only in
the directories listed above for which you have full rights
and responsibilities.

EOM
EOF

##############################################
# Make sure all major services are on
#
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysqld on
$CHKCONFIG crond on

/etc/init.d/codendi start

$SERVICE httpd restart
$SERVICE crond restart

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss-mysql is used for authentication
$CHKCONFIG nscd on
$SERVICE nscd start

if [ "$enable_munin" = "true" ]; then
    $CHKCONFIG munin-node on
    $SERVICE munin-node start
fi

##############################################
# Set SELinux contexts and load policies
#
if [ $SELINUX_ENABLED ]; then
    echo "Set SELinux contexts and load policies"
    $INSTALL_DIR/src/utils/fix_selinux_contexts.pl
fi

##############################################
# Install & configure forgeupgrade for Tuleap
#

$MYSQL -ucodendiadm -p$codendiadm_passwd codendi < /usr/share/forgeupgrade/db/install-mysql.sql
$INSTALL --group=codendiadm --owner=codendiadm --mode=0755 --directory /etc/codendi/forgeupgrade
$INSTALL --group=codendiadm --owner=codendiadm --mode=0644 $INSTALL_DIR/src/etc/forgeupgrade-config.ini.dist /etc/codendi/forgeupgrade/config.ini


##############################################
# *Last* step: install plugins
#

echo "Install tuleap plugins"
# docman plugin
$CAT $INSTALL_DIR/plugins/docman/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
$CAT <<EOF | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('docman', '1');
EOF
build_dir /etc/codendi/plugins/docman/etc codendiadm codendiadm 755
$CP $INSTALL_DIR/plugins/docman/etc/docman.inc.dist /etc/codendi/plugins/docman/etc/docman.inc
$CHOWN codendiadm.codendiadm /etc/codendi/plugins/docman/etc/docman.inc
$CHMOD 644 /etc/codendi/plugins/docman/etc/docman.inc
echo "path[]=\"$INSTALL_DIR/plugins/docman\"" >> /etc/codendi/forgeupgrade/config.ini

# Tracker plugin
if [ "$enable_plugin_tracker" = "true" ]; then
    build_dir /etc/codendi/plugins/tracker/etc codendiadm codendiadm 755
    $CAT $INSTALL_DIR/plugins/tracker/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('tracker', '1');
EOF
    echo "path[]=\"$INSTALL_DIR/plugins/tracker\"" >> /etc/codendi/forgeupgrade/config.ini

    # Import all templates
    template_base_dir="$INSTALL_DIR/plugins/tracker/www/resources/templates"
    /bin/ls $template_base_dir/Tracker_*.xml | while read xml_template; do
	$INSTALL_DIR/src/utils/php-launcher.sh $INSTALL_DIR/plugins/tracker/bin/import_tracker_xml_template.php "$xml_template"
    done
fi

# GraphOnTrackersv5 plugin
if [ "$enable_plugin_graphontrackersv5" = "true" ]; then
    build_dir /etc/codendi/plugins/graphontrackersv5/etc codendiadm codendiadm 755
    $CAT $INSTALL_DIR/plugins/graphontrackersv5/db/install.sql | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u codendiadm codendi --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('graphontrackersv5', '1');
EOF
    echo "path[]=\"$INSTALL_DIR/plugins/graphontrackersv5\"" >> /etc/codendi/forgeupgrade/config.ini
fi

# IM plugin
if [ "$enable_plugin_im" = "true" ]; then
    # Create openfireadm MySQL user
    $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT ALL PRIVILEGES on openfire.* to 'openfireadm'@'$mysql_httpd_host' identified by '$openfire_passwd';
GRANT SELECT ON codendi.user to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON codendi.groups to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON codendi.user_group to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON codendi.session to 'openfireadm'@'$mysql_httpd_host';
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
    $PHP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/install.php -a -orp $rt_passwd -uod openfireadm -pod $openfire_passwd -ucd openfireadm -pcd $openfire_passwd -odb jdbc:mysql://$mysql_host:3306/openfire -cdb jdbc:mysql://$mysql_host:3306/codendi -ouri $sys_default_domain -gjx $IM_ADMIN_GROUP -ujx $IM_ADMIN_USER -pjx $IM_ADMIN_USER_PW -pmuc $IM_MUC_PW
    echo "path[]=\"$INSTALL_DIR/plugins/IM\"" >> /etc/codendi/forgeupgrade/config.ini

    # Enable service
    $CHKCONFIG openfire on
    $SERVICE openfire start
fi


##############################################
# Register buckets in forgeupgrade
#
/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/codendi/forgeupgrade/config.ini record-only


##############################################
# End of installation
#
todo ""
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE."

# End of it
echo "=============================================="
echo "Installation completed successfully!"
$CAT $TODO_FILE

exit 0