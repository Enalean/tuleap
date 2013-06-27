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

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

die() {
  # $1: message to prompt before exiting
  echo -e "**ERROR** $1"; exit 1
}


if [ -e /etc/debian_version ]; then
    INSTALL_PROFILE="debian"
    PROJECT_NAME="tuleap"
    PROJECT_ADMIN="www-data"
    TULEAP_CACHE_DIR="/var/cache/tuleap"
    NAMED_SERVICE="bind9"
    MYSQLD_SERVICE="mysql"
    SSHD_SERVICE="ssh"
    CROND_SERVICE="cron"
    HTTPD_SERVICE="apache2"
else
    lsb_release=$(which lsb_release)
    if [ ! -x $lsb_release ]; then
	die "lsb_release is missing, please install it first (yum install redhat-lsb)"
    fi
    if lsb_release -s -i | grep -i -P '(centos|redhatenterprise)'; then
	RH_VERSION=$(lsb_release -s -r)
	RH_MAJOR_VERSION=$(echo $RH_VERSION | cut -d'.' -f1)
	RH_MINOR_VERSION=$(echo $RH_VERSION | cut -d'.' -f2)
    fi

    INSTALL_PROFILE="rhel"

    if [ $RH_MAJOR_VERSION = "6" ]; then
	PROJECT_NAME="tuleap"
    
    else
	PROJECT_NAME="codendi"
    fi
    PROJECT_ADMIN="codendiadm"
    TULEAP_CACHE_DIR="/var/tmp/tuleap_cache"
    NAMED_SERVICE="named"
    MYSQLD_SERVICE="mysqld"
    SSHD_SERVICE="sshd"
    CROND_SERVICE="crond"
    HTTPD_SERVICE="httpd"
fi

export INSTALL_DIR="/usr/share/$PROJECT_NAME"

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
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'
PHP='/usr/bin/php'
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


CMD_LIST="GROUPADD GROUPDEL USERADD USERDEL USERMOD MV CP LN LS RM \
MKDIR CHOWN CHGRP CHMOD FIND TOUCH CAT GREP PERL PHP INSTALL"

if [ "$INSTALL_PROFILE" = "rhel" ]; then
    CMD_LIST="$CMD_LIST RPM CHKCONFIG SERVICE"
fi

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
	ext="no$PROJECT_NAME"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && $CP "$file" "$backup_file"
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

fix_paths() {
    $PERL -pi -E 'my %h = qw(/usr/share/codendi /usr/share/tuleap /etc/codendi /etc/tuleap /usr/lib/codendi /usr/lib/tuleap /var/lib/codendi /var/lib/tuleap /codendi_cache /tuleap_cache /var/log/codendi /var/log/tuleap /ftp/codendi /ftp/tuleap); s%(/usr/share/codendi|/etc/codendi|/usr/lib/codendi|/var/lib/codendi|/codendi_cache|/var/log/codendi|/ftp/codendi)%$h{$1}%ge;' $1
}

generate_passwd() {
    $CAT /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 9 | head -1
}

has_package() {
    local pkg=$1
    if [ "$INSTALL_PROFILE" = "debian" ]; then
	dpkg -s $pkg 2>/dev/null | grep -q "Status: install ok installed"
    else
	$RPM -q $pkg >/dev/null 2>&1
    fi
}

input_password() {
    local label="$1"
    local pass1="a"
    local pass2="b"
    while [ "$pass1" != "$pass2" ]; do
	read -s -p "Password for $label: " pass1
	read -s -p "Retype $label password: " pass2
    done
    echo "$pass1"
}

install_dist_conf() {
    local target="$1"
    local template="$2"
    local fn
    if [ -z "$template" ]; then
	fn=$(basename "$target")
	fn="$fn.dist"
    else
	fn="$template"
    fi
    # Keep backup file
    if [ -e "$target" ]; then
	cp -f $target $target.orig
    fi
    # Install file
    src="$INSTALL_DIR/src/etc/$fn"
    if [ -e "$src" ]; then
	cp -a $src $target
    else
	echo "ERROR: $src doesn't exist" >&2
	exit 1
    fi
}

control_service() {
    local service="$1"
    local command="$2"
    if [ -x $SERVICE ]; then
	$SERVICE $service $command
    else
	if [ -x /etc/init.d/$service ]; then
	    /etc/init.d/$service $command
	else
	    echo "ERROR: found no way to control service $service" >&2
	    exit 1
	fi
    fi
}

enable_service() {
    local service="$1"
    if [ "$INSTALL_PROFILE" = "rhel" ]; then
	$CHKCONFIG $service on
    else
	: # On Debian, services are enabled by default
    fi
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
    $CHOWN $PROJECT_ADMIN.$PROJECT_ADMIN /etc/cvs_root_allow
    $CHMOD 644 /etc/cvs_root_allow

    $CP /etc/xinetd.d/cvs /root/cvs.xinetd.ori

    $CAT <<EOF >/etc/xinetd.d/cvs
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

    $CAT <<EOF >> /etc/shells
/usr/lib/$PROJECT_NAME/bin/cvssh
/usr/lib/$PROJECT_NAME/bin/cvssh-restricted
EOF

    enable_service cvs
    enable_service xinetd

    control_service xinetd restart
}

###############################################################################
#
# FTP server configuration
#
setup_vsftpd() {
    for conf in /etc/vsftpd.conf /etc/vsftpd/vsftpd.conf; do
	if [ -e "$conf" ]; then
	    VSFTPD_CONF="$conf"
	fi
    done
    # Configure vsftpd
    $PERL -i'.orig' -p -e "s/^#?anon_upload_enable=.*/anon_upload_enable=YES/g" $VSFTPD_CONF
    $PERL -pi -e "s/^#?ftpd_banner=.*/ftpd_banner=Welcome to Tuleap FTP service./g" $VSFTPD_CONF
    $PERL -pi -e "s/^#?local_umask=.*/local_umask=002/g" $VSFTPD_CONF

    # Add welcome messages
    $CAT <<EOF > /var/lib/$PROJECT_NAME/ftp/.message
********************************************************************
Welcome to Tuleap FTP server

On This Site:
/incoming          Place where to upload your new file release
/pub               Projects Anonymous FTP space
*********************************************************************

EOF
    $CHOWN ftpadmin.ftpadmin /var/lib/$PROJECT_NAME/ftp/.message

    # Add welcome messages
    $CAT <<EOF >/var/lib/$PROJECT_NAME/ftp/incoming/.message

Upload new file releases here

EOF
    $CHOWN ftpadmin.ftpadmin /var/lib/$PROJECT_NAME/ftp/incoming/.message

    if [ "$INSTALL_PROFILE" = "rhel" ]; then
	# Debian provides a logrotate file by default
	# Log Rotate
	$CAT <<'EOF' | sed -e "s/@@PROJECT_NAME@@/$PROJECT_NAME/g" >/etc/logrotate.d/vsftpd.log
/var/log/xferlog {
    # ftpd doesn't handle SIGHUP properly
    nocompress
    missingok
    daily
    postrotate
     year=`date +%Y`
     month=`date +%m`
     day=`date +%d`
     destdir="/var/log/@@PROJECT_NAME@@/$year/$month"
     destfile="ftp_xferlog_$year$month$day.log"
     mkdir -p $destdir
     cp /var/log/xferlog.1 $destdir/$destfile
    endscript
}
EOF
	$CHOWN root:root /etc/logrotate.d/vsftpd.log
	$CHMOD 644 /etc/logrotate.d/vsftpd.log
    fi

    # Start service
    enable_service vsftpd
    control_service vsftpd restart
}


###############################################################################
#
# Bind DNS server configuration
#
setup_bind() {
    if [ "$INSTALL_PROFILE" = "rhel" ]; then
	ZONE_DIR="/var/named/chroot/var/named"
	NAMED_GID="named"
	ETC_DIR="/var/named/chroot/etc"
    else
	ZONE_DIR="/etc/bind"
	NAMED_GID="bind"
	ETC_DIR="/etc/bind"
    fi
    if [ -f $ZONE_DIR/$PROJECT_NAME.zone ]; then
        $CP -af $ZONE_DIR/$PROJECT_NAME.zone $ZONE_DIR/$PROJECT_NAME.zone.orig
    fi
    $CP -f $INSTALL_DIR/src/etc/codendi.zone.dist $ZONE_DIR/$PROJECT_NAME.zone

    $CHOWN root:$NAMED_GID $ZONE_DIR/$PROJECT_NAME.zone
    if [ -f "$ETC_DIR/named.conf" ]; then
        $CHGRP $NAMED_GID $ETC_DIR/named.conf
    fi

    if [ $SELINUX_ENABLED ]; then
        $CHCON -h system_u:object_r:named_zone_t $ZONE_DIR/$PROJECT_NAME.zone
        if [ -f "$ETC_DIR/named.conf" ]; then
            $CHCON -h system_u:object_r:named_conf_t $ETC_DIR/named.conf
        fi
    fi

    # replace string patterns in $PROJECT_NAME.zone
    sys_shortname=`echo $sys_fullname | $PERL -pe 's/\.(.*)//'`
    dns_serial=`date +%Y%m%d`01
    substitute "$ZONE_DIR/$PROJECT_NAME.zone" '%sys_default_domain%' "$sys_default_domain"
    substitute "$ZONE_DIR/$PROJECT_NAME.zone" '%sys_fullname%' "$sys_fullname"
    substitute "$ZONE_DIR/$PROJECT_NAME.zone" '%sys_ip_address%' "$sys_ip_address"
    substitute "$ZONE_DIR/$PROJECT_NAME.zone" '%sys_shortname%' "$sys_shortname"
    substitute "$ZONE_DIR/$PROJECT_NAME.zone" '%dns_serial%' "$dns_serial"

    todo "Create the DNS configuration files as explained in the Tuleap Installation Guide:"
    todo "    update $ZONE_DIR/$PROJECT_NAME.zone - replace all words starting with %%."
    todo "    make sure the file is readable by 'other':"
    todo "      > chmod o+r $ZONE_DIR/$PROJECT_NAME.zone"

    if [ -e /etc/bind/named.conf.local ]; then
	if ! grep -q "$PROJECT_NAME.zone" /etc/bind/named.conf.local; then
	    echo "zone \"$sys_default_domain\" { type master; file \"$ZONE_DIR/$PROJECT_NAME.zone\"; };" >> /etc/bind/named.conf.local
	fi
    else
	todo "    edit $ETC_DIR/named.conf to create the new zone."
    fi

    enable_service $NAMED_SERVICE
    control_service $NAMED_SERVICE restart
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
    if [ -n "$(which pycompile 2>/dev/null)" ]; then
	pycompile /usr/lib/mailman/Mailman/mm_cfg.py
    else
	`python -O /usr/lib/mailman/Mailman/mm_cfg.py`
    fi

    # Create site wide ML
    # Note that if sys_default_domain is not a domain, the script will complain
    LIST_OWNER=$PROJECT_NAME-admin@$sys_default_domain
    if [ "$disable_subdomains" = "y" ]; then
        LIST_OWNER=$PROJECT_NAME-admin@$sys_fullname
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

    # Subscribe $PROJECT_NAME-admin to this ML
    echo $LIST_OWNER | /usr/lib/mailman/bin/add_members -r - mailman

    enable_service mailman
    control_service mailman restart </dev/null >/dev/null 2>/dev/null &
}

###############################################################################
#
# Mysql configuration
#
setup_mysql_cnf() {
    if [ "$INSTALL_PROFILE" = "debian" ]; then
	return # This configuration is RHEL specific
    fi

    echo "Creating MySQL conf file..."
    local template_file
    if [ "$RH_MAJOR_VERSION" = "5" ]; then
	template_file="my.cnf.rhel5.dist"
    else
	template_file="my.cnf.rhel6.dist"
    fi

    install_dist_conf "/etc/my.cnf" "$template_file"
    substitute "/etc/my.cnf" '%PROJECT_NAME%' "$PROJECT_NAME"

    if [ -z "$mysql_host" ]; then
	echo "Initializing MySQL: You can ignore additionnal messages on MySQL below this line:"
	echo "***************************************"
	control_service $MYSQLD_SERVICE restart
	echo "***************************************"
    fi
}

setup_mysql() {

    setup_mysql_cnf

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
    if $MYSQLSHOW $pass_opt | $GREP $PROJECT_NAME 2>&1 >/dev/null; then
        read -p "Tuleap Database already exists. Overwrite? [y|n]:" yn
    fi

    # Delete the Tuleap DB if asked for
    if [ "$yn" = "y" ]; then
        $MYSQL $pass_opt -e "DROP DATABASE $PROJECT_NAME"
    fi

    # If no $PROJECT_NAME, create it!
    if ! $MYSQLSHOW $pass_opt | $GREP $PROJECT_NAME 2>&1 >/dev/null; then
        freshdb=1
        $MYSQL $pass_opt -e "CREATE DATABASE $PROJECT_NAME DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
        $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT ALL PRIVILEGES on *.* to '$PROJECT_ADMIN'@'$mysql_httpd_host' identified by '$codendiadm_passwd' WITH GRANT OPTION;
REVOKE SUPER ON *.* FROM '$PROJECT_ADMIN'@'$mysql_httpd_host';
GRANT ALL PRIVILEGES on *.* to 'root'@'$mysql_httpd_host' identified by '$rt_passwd';
FLUSH PRIVILEGES;
EOF
    fi
    # Password has changed
    pass_opt="-uroot --password=$rt_passwd"

    if [ $freshdb -eq 1 ]; then
        echo "Populating the Tuleap database..."
        cd $INSTALL_DIR/src/db/mysql/
        $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd < database_structure.sql   # create the DB
        cp database_initvalues.sql /tmp/database_initvalues.sql
        substitute '/tmp/database_initvalues.sql' '_DOMAIN_NAME_' "$sys_default_domain"
        $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd < /tmp/database_initvalues.sql  # populate with init values.
        rm -f /tmp/database_initvalues.sql

        # Create dbauthuser
        $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT SELECT ON $PROJECT_NAME.user to dbauthuser@$mysql_httpd_host identified by '$dbauth_passwd';
GRANT SELECT ON $PROJECT_NAME.groups to dbauthuser@$mysql_httpd_host;
GRANT SELECT ON $PROJECT_NAME.user_group to dbauthuser@$mysql_httpd_host;
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
# Apache setup
#
setup_apache_rhel() {
    # Move away useless Apache configuration files
    # before installing our own config files.
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

    $TOUCH /etc/httpd/conf.d/codendi_svnroot.conf

    echo "Installing Apache configuration files..."
    make_backup /etc/httpd/conf/httpd.conf

    if [ "$RH_MAJOR_VERSION" = 5 ]; then
	install_dist_conf /etc/httpd/conf/httpd.conf
    else
	install_dist_conf /etc/httpd/conf/httpd.conf httpd.conf.rhel6.dist
    fi

    for f in /etc/httpd/conf/ssl.conf \
	     /etc/httpd/conf.d/php.conf /etc/httpd/conf.d/subversion.conf \
	     /etc/httpd/conf.d/auth_mysql.conf \
	     /etc/httpd/conf.d/codendi_aliases.conf; do
	install_dist_conf $f
	fix_paths $f
    done

    # replace string patterns in codendi_aliases.conf
    substitute "/etc/httpd/conf.d/codendi_aliases.conf" '%sys_default_domain%' "$sys_default_domain"

    # replace string patterns in httpd.conf
    substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
    substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

    # replace string patterns in munin.conf (for MySQL authentication)
    if [ -f '/etc/httpd/conf.d/munin.conf' ]; then
	substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 
    fi

    # Make $PROJECT_ADMIN a member of the apache group
    # This is needed to use the php session at /var/lib/php/session (e.g. for phpwiki)
    $USERMOD -a -G apache $PROJECT_ADMIN

    # Log Files rotation configuration
    echo "Installing log files rotation..."

    cp -f $INSTALL_DIR/src/etc/logrotate.httpd.conf /etc/logrotate.d/httpd
    substitute '/etc/logrotate.d/httpd' "%PROJECT_NAME%" "$PROJECT_NAME"

    $CHOWN root:root /etc/logrotate.d/httpd
    $CHMOD 644 /etc/logrotate.d/httpd
}

setup_apache_debian() {
    # Enabled by subversion.conf on RHEL setup
    a2enmod dav
    a2enmod dav_svn
    a2enmod authz_svn
    # Enabled by ssl.conf on RHEL setup
    a2enmod ssl
    # Enabled by php.conf on RHEL setup
    a2enmod php5
    # Enabled by auth_mysql.conf on RHEL setup
    a2enmod auth_mysql
    # Required by codendi_aliases.conf
    a2enmod rewrite
    a2enmod proxy
    a2enmod proxy_http
    # Listed in http.conf on RHEL setup
    # XXX: Find out which ones are really required
    a2enmod alias
    a2enmod autoindex
    a2enmod negotiation
    a2enmod mime
    a2enmod deflate
    a2enmod vhost_alias

    install_dist_conf /etc/apache2/sites-available/tuleap
    mkdir -p /etc/apache2/tuleap
    install_dist_conf /etc/apache2/tuleap/codendi_aliases.conf
    install_dist_conf /etc/apache2/tuleap/php.conf
    install_dist_conf /etc/apache2/tuleap/auth_mysql.conf

    touch /etc/apache2/tuleap_svnroot.conf

    substitute "/etc/apache2/tuleap/codendi_aliases.conf" '%sys_default_domain%' "$sys_default_domain"
    substitute '/etc/apache2/sites-available/tuleap' '%sys_default_domain%' "$sys_default_domain"
    if [ -f '/etc/apache2/conf.d/munin.conf' ]; then
	substitute '/etc/apache2/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 
    fi

    a2ensite tuleap
    a2dissite default
}

setup_apache() {
    if [ "$INSTALL_PROFILE" = "debian" ]; then
	setup_apache_debian
    else
	setup_apache_rhel
    fi
}

###############################################################################
#
# NSS setup
#
setup_nss() {
    echo "Installing NSS configuration files..."
    for f in /etc/libnss-mysql.cfg  /etc/libnss-mysql-root.cfg; do
	install_dist_conf $f
    done
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
	    $PERL -i -p -e "s/^shadow(.*)/shadow\1 mysql/g" /etc/nsswitch.conf
	fi

	# group
	$GREP ^group  /etc/nsswitch.conf | $GREP -q mysql
	if [ $? -ne 0 ]; then
	    $PERL -i -p -e "s/^group(.*)/group\1 mysql/g" /etc/nsswitch.conf
	fi
    else
	echo '/etc/nsswitch.conf does not exist. Cannot use MySQL authentication!'
    fi

    # replace strings in libnss-mysql config files
    substitute '/etc/libnss-mysql.cfg' '%sys_dbhost%' "$mysql_host"
    substitute '/etc/libnss-mysql.cfg' '%sys_dbname%' "$PROJECT_NAME"
    substitute '/etc/libnss-mysql.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
    substitute '/etc/libnss-mysql-root.cfg' '%sys_dbauth_passwd%' "$dbauth_passwd" 
    $CHOWN root:root /etc/libnss-mysql.cfg /etc/libnss-mysql-root.cfg
    $CHMOD 644 /etc/libnss-mysql.cfg
    $CHMOD 600 /etc/libnss-mysql-root.cfg
}

###############################################################################
#
# Tuleap setup
#
setup_tuleap() {
    echo "Installing Tuleap configuration files..."
    for f in /etc/$PROJECT_NAME/conf/local.inc \
	     /etc/$PROJECT_NAME/conf/database.inc; do
	install_dist_conf $f
    done
    # replace string patterns in local.inc
    substitute "/etc/$PROJECT_NAME/conf/local.inc" '%sys_default_domain%' "$sys_default_domain" 
    substitute "/etc/$PROJECT_NAME/conf/local.inc" '%sys_org_name%' "$sys_org_name" 
    substitute "/etc/$PROJECT_NAME/conf/local.inc" '%sys_long_org_name%' "$sys_long_org_name" 
    substitute "/etc/$PROJECT_NAME/conf/local.inc" '%sys_fullname%' "$sys_fullname" 
    substitute "/etc/$PROJECT_NAME/conf/local.inc" '%sys_dbauth_passwd%' "$dbauth_passwd" 
    substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_create_project_in_one_step = 0' 'sys_create_project_in_one_step = 1'
    if [ "$disable_subdomains" = "y" ]; then
	substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_lists_host = "lists.' 'sys_lists_host = "'
	substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_disable_subdomains = 0' 'sys_disable_subdomains = 1'
    fi

    substitute "/etc/$PROJECT_NAME/conf/local.inc" 'codendiadm' "$PROJECT_ADMIN"
    fix_paths "/etc/$PROJECT_NAME/conf/local.inc"

    # replace string patterns in database.inc
    substitute "/etc/$PROJECT_NAME/conf/database.inc" '%sys_dbpasswd%' "$codendiadm_passwd" 
    substitute "/etc/$PROJECT_NAME/conf/database.inc" '%sys_dbuser%' "$PROJECT_ADMIN" 
    substitute "/etc/$PROJECT_NAME/conf/database.inc" '%sys_dbname%' "$PROJECT_NAME" 
    substitute "/etc/$PROJECT_NAME/conf/database.inc" 'localhost' "$mysql_host" 

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
    if ! has_package mysql-server; then
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
if [ "$INSTALL_PROFILE" = "rhel" ]; then
    if [ "$RH_MAJOR_VERSION" = "5" ]; then
	RH_UPDATE="6"
    else
	RH_UPDATE="3"
    fi

    if [ "x$RH_MINOR_VERSION" != x ] && [ "$RH_MINOR_VERSION" -ge "$RH_UPDATE" ]; then
	echo "Running on RHEL or CentOS ${RH_RELEASE}... good!"
    else
	cat <<-EOF
	This machine is not running RedHat Enterprise Linux or CentOS ${RH_RELEASE}.${RH_UPDATE}
	You should consider to upgrade your system before going any further (yum upgrade).
	EOF
	read -p "Continue? [y|n]: " yn
	if [ "$yn" = "n" ]; then
	    echo "Bye now!"
	    exit 1
	fi
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
if has_package mailman-tuleap; then
    enable_core_mailman="true"
fi

# Check if munin is installed
enable_munin="false"
if has_package munin; then
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
    echo "Codendiadm unix & DB ($PROJECT_ADMIN): $codendiadm_passwd" >> $passwd_file

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
        rt_passwd=$(input_password "MySQL root")
    fi

    codendiadm_passwd=$(input_password "$PROJECT_ADMIN user")

    if [ "$enable_core_mailman" = "true" ]; then
	mm_passwd=$(input_password "mailman user")
    fi

    if [ "$enable_plugin_im" = "true" ]; then
	openfire_passwd=$(input_password "Openfire DB user")
    fi

    echo "DB authentication user: MySQL user that will be used for user authentication"
    echo "  Please do not reuse a password here, as this password will be stored in clear on the filesystem and will be accessible to all logged-in user."
    dbauth_passwd=$(input_password "DB Authentication user")
fi

if [ "$INSTALL_PROFILE" = "rhel" ]; then
    # Update codendiadm user password
    echo "$codendiadm_passwd" | passwd --stdin $PROJECT_ADMIN
    build_dir /home/$PROJECT_ADMIN $PROJECT_ADMIN $PROJECT_ADMIN 700
    if [ $SELINUX_ENABLED ]; then
	$CHCON -R -h $SELINUX_CONTEXT /home/$PROJECT_ADMIN
    fi
else
    : # The debian profile uses www-data which is a pre-existing system account
fi

# Build file structure

build_dir /home/users $PROJECT_ADMIN $PROJECT_ADMIN 771
build_dir /home/groups $PROJECT_ADMIN $PROJECT_ADMIN 771

# data dirs
build_dir /var/lib/$PROJECT_NAME $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /var/lib/$PROJECT_NAME/dumps dummy dummy 755
build_dir /var/lib/$PROJECT_NAME/ftp root ftp 755
build_dir /var/lib/$PROJECT_NAME/ftp/$PROJECT_NAME root root 711
build_dir /var/lib/$PROJECT_NAME/ftp/pub ftpadmin ftpadmin 755
build_dir /var/lib/$PROJECT_NAME/ftp/incoming ftpadmin ftpadmin 3777
build_dir /var/lib/$PROJECT_NAME/wiki $PROJECT_ADMIN $PROJECT_ADMIN 700
build_dir /var/lib/$PROJECT_NAME/backup $PROJECT_ADMIN $PROJECT_ADMIN 711
build_dir /var/lib/$PROJECT_NAME/backup/mysql mysql mysql 770 
build_dir /var/lib/$PROJECT_NAME/backup/mysql/old root root 700
build_dir /var/lib/$PROJECT_NAME/backup/subversion root root 700
build_dir /var/lib/$PROJECT_NAME/docman $PROJECT_ADMIN $PROJECT_ADMIN 700
# log dirs
if [ "$RH_MAJOR_VERSION" != "6" ] ; then #if rhel6, built in rpm
    build_dir /var/log/$PROJECT_NAME $PROJECT_ADMIN $PROJECT_ADMIN 755
    build_dir /var/log/$PROJECT_NAME/cvslogs $PROJECT_ADMIN $PROJECT_ADMIN 775
fi
build_dir $TULEAP_CACHE_DIR $PROJECT_ADMIN $PROJECT_ADMIN 755
# config dirs
build_dir /etc/skel_$PROJECT_NAME root root 755
build_dir /etc/$PROJECT_NAME $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/conf $PROJECT_ADMIN $PROJECT_ADMIN 700
build_dir /etc/$PROJECT_NAME/documentation $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/documentation/user_guide $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/documentation/user_guide/xml $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/documentation/cli $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/documentation/cli/xml $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/site-content $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/site-content/en_US $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/site-content/en_US/others $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/site-content/fr_FR $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/site-content/fr_FR/others $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/themes $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/plugins $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/plugins/docman $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/plugins/pluginsadministration $PROJECT_ADMIN $PROJECT_ADMIN 755
# SCM dirs
build_dir /var/run/log_accum root root 777
build_dir /var/lib/$PROJECT_NAME/cvsroot $PROJECT_ADMIN $PROJECT_ADMIN 751
build_dir /var/lib/$PROJECT_NAME/svnroot $PROJECT_ADMIN $PROJECT_ADMIN 751
build_dir /var/lock/cvs root root 751
$LN -sf /var/lib/$PROJECT_NAME/cvsroot /cvsroot
$LN -sf /var/lib/$PROJECT_NAME/svnroot /svnroot


$TOUCH /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files
$CHOWN $PROJECT_ADMIN.ftpadmin /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files
$CHMOD 750 /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files
$TOUCH /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files.work
$CHOWN $PROJECT_ADMIN.ftpadmin /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files.work
$CHMOD 750 /var/lib/$PROJECT_NAME/ftp/incoming/.delete_files.work
build_dir /var/lib/$PROJECT_NAME/ftp/$PROJECT_NAME/DELETED $PROJECT_ADMIN $PROJECT_ADMIN 750


# SELinux specific
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/$PROJECT_NAME
    $CHCON -R -h $SELINUX_CONTEXT /etc/$PROJECT_NAME
    $CHCON -R -h $SELINUX_CONTEXT /var/lib/$PROJECT_NAME
    $CHCON -R -h $SELINUX_CONTEXT /home/groups
    if [ -d /home/codendiadm ]; then
	$CHCON -R -h $SELINUX_CONTEXT /home/codendiadm
    fi
    $CHCON -h $SELINUX_CONTEXT /svnroot
    $CHCON -h $SELINUX_CONTEXT /cvsroot
fi

##############################################
# Install the Tuleap software 
#

setup_tuleap

$INSTALL_DIR/src/utils/generate_ssl_certificate.sh
setup_apache
setup_nss

# Bind config
if [ "$configure_bind" = "true" ]; then
    setup_bind
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

# Make sure SELinux contexts are valid
if [ $SELINUX_ENABLED ]; then
    $CHCON -R -h $SELINUX_CONTEXT /usr/share/$PROJECT_NAME
fi

todo "Customize /etc/$PROJECT_NAME/conf/local.inc and /etc/$PROJECT_NAME/conf/database.inc"
todo "You may also want to customize /etc/httpd/conf/httpd.conf"

##############################################
# Installing phpMyAdmin
#

# Allow read/write access to DAV lock dir for $PROJECT_ADMIN in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

##############################################
# Installing the Tuleap database
#
setup_mysql


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
# $LN -sf /usr/lib/$PROJECT_NAME/bin/gotohell
# #$LN -sf $MAILMAN_DIR/mail/mailman Now done in RPM install

# $PERL -i'.orig' -p -e's:^O\s*AliasFile.*:O AliasFile=/etc/aliases,/etc/aliases.$PROJECT_NAME:' /etc/mail/sendmail.cf
# cat <<EOF >/etc/mail/local-host-names
# # local-host-names - include all aliases for your machine here.
# $sys_default_domain
# lists.$sys_default_domain
# users.$sys_default_domain
# EOF


# Default: codex-admin is redirected to root
# TODO check if already there
echo "$PROJECT_NAME-admin:          root" >> /etc/aliases

#todo "Finish sendmail settings (see installation Guide). By default, emails sent to $PROJECT_NAME-admin are redirected to root (see /etc/aliases)"

##############################################
# CVS
if has_package cvs-tuleap; then
    setup_cvs
fi

##############################################
# Make the system daily cronjob run at 23:58pm
echo "Updating daily cron job in system crontab..."
$PERL -i'.orig' -p -e's/\d+ \d+ (.*daily)/58 23 \1/g' /etc/crontab

##############################################
# FTP
if has_package vsftpd; then
    setup_vsftpd
fi

##############################################
# Create the custom default page for the project Web sites
#
echo "Creating the custom default page for the project Web sites..."
def_page=/etc/$PROJECT_NAME/site-content/en_US/others/default_page.php
yn="y"
[ -f "$def_page" ] && read -p "Custom Default Project Home page already exists. Overwrite? [y|n]:" yn
if [ "$yn" = "y" ]; then
    $MKDIR -p /etc/$PROJECT_NAME/site-content/en_US/others
    $CHOWN $PROJECT_ADMIN.$PROJECT_ADMIN /etc/$PROJECT_NAME/site-content/en_US/others
    $CP $INSTALL_DIR/site-content/en_US/others/default_page.php /etc/$PROJECT_NAME/site-content/en_US/others/default_page.php
fi

if [ "$disable_subdomains" = "y" ]; then
  echo "Use same-host project web sites"
  $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd -e "UPDATE service SET link = IF(group_id = 1, '/www/$PROJECT_NAME', '/www/\$projectname/') WHERE short_name = 'homepage' "
fi

#todo "Customize /etc/$PROJECT_NAME/site-content/en_US/others/default_page.php (project web site default home page)"
todo "Customize /etc/$PROJECT_NAME/site-content information for your site."
todo "  For instance: contact/contact.txt "
todo ""
todo "Default admin credentials are login: admin / password: siteadmin"
todo "CHANGE DEFAULT CREDENTIALS BEFORE FIRST USAGE"

##############################################
# Crontab configuration
#

# XXX: Writing to /tmp/foo as root is a security issue, should use mktemp
# or similar, or better we should use /etc/cron.d/tuleap

echo "Installing root user crontab..."
crontab -u root -l > /tmp/cronfile

$GREP -q "Tuleap" /tmp/cronfile
if [ $? -ne 0 ]; then
    $CAT <<EOF >>/tmp/cronfile
# Tuleap: weekly backup preparation (mysql shutdown, file dump and restart)
45 0 * * Sun /usr/lib/$PROJECT_NAME/bin/backup_job
EOF
    crontab -u root /tmp/cronfile
fi

##############################################
# Create Tuleap profile script
#

# customize the global profile 
$GREP profile_$PROJECT_NAME /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' | sed -e "s/@@PROJECT_NAME@@/$PROJECT_NAME/" >>/etc/profile
# Now the Part specific to Tuleap users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_@@PROJECT_NAME@@
fi
EOF

$CAT <<'EOF' | sed -e "s/@@PROJECT_NAME@@/$PROJECT_NAME/" >/etc/profile_$PROJECT_NAME
# /etc/profile_@@PROJECT_NAME@@
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
enable_service $SSHD_SERVICE
enable_service $HTTPD_SERVICE
enable_service $MYSQLD_SERVICE
enable_service $CROND_SERVICE

/etc/init.d/$PROJECT_NAME start

control_service $HTTPD_SERVICE restart
control_service $CROND_SERVICE restart

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss-mysql is used for authentication
enable_service nscd
control_service nscd restart

if [ "$enable_munin" = "true" ]; then
    enable_service munin-node
    control_service munin-node restart
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

$MYSQL -u$PROJECT_ADMIN -p$codendiadm_passwd $PROJECT_NAME < /usr/share/forgeupgrade/db/install-mysql.sql
$INSTALL --group=$PROJECT_ADMIN --owner=$PROJECT_ADMIN --mode=0755 --directory /etc/$PROJECT_NAME/forgeupgrade
if [ $INSTALL_PROFILE = "rhel" -a $RH_MAJOR_VERSION = 6 ]; then
    forge_upgrade_config_dist=$INSTALL_DIR/src/etc/forgeupgrade-config.ini.rhel6.dist
else
    forge_upgrade_config_dist=$INSTALL_DIR/src/etc/forgeupgrade-config.ini.dist
fi
$INSTALL --group=$PROJECT_ADMIN --owner=$PROJECT_ADMIN --mode=0644 $forge_upgrade_config_dist /etc/$PROJECT_NAME/forgeupgrade/config.ini

##############################################
# *Last* step: install plugins
#

echo "Install tuleap plugins"

echo "Install Docman"
$CAT $INSTALL_DIR/plugins/docman/db/install.sql | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
$CAT <<EOF | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('docman', '1');
EOF
build_dir /etc/$PROJECT_NAME/plugins/docman/etc $PROJECT_ADMIN $PROJECT_ADMIN 755
$CP $INSTALL_DIR/plugins/docman/etc/docman.inc.dist /etc/$PROJECT_NAME/plugins/docman/etc/docman.inc
$CHOWN $PROJECT_ADMIN.$PROJECT_ADMIN /etc/$PROJECT_NAME/plugins/docman/etc/docman.inc
$CHMOD 644 /etc/$PROJECT_NAME/plugins/docman/etc/docman.inc
echo "path[]=\"$INSTALL_DIR/plugins/docman\"" >> /etc/$PROJECT_NAME/forgeupgrade/config.ini

# Tracker plugin
if [ "$enable_plugin_tracker" = "true" ]; then
    echo "Install tracker"
    build_dir /etc/$PROJECT_NAME/plugins/tracker/etc $PROJECT_ADMIN $PROJECT_ADMIN 755
    $CAT $INSTALL_DIR/plugins/tracker/db/install.sql | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('tracker', '1');
EOF
    echo "path[]=\"$INSTALL_DIR/plugins/tracker\"" >> /etc/$PROJECT_NAME/forgeupgrade/config.ini

    # Import all templates
    template_base_dir="$INSTALL_DIR/plugins/tracker/www/resources/templates"
    /bin/ls $template_base_dir/Tracker_*.xml | while read xml_template; do
	$INSTALL_DIR/src/utils/php-launcher.sh $INSTALL_DIR/plugins/tracker/bin/import_tracker_xml_template.php "$xml_template"
    done
fi

# GraphOnTrackersv5 plugin
if [ "$enable_plugin_graphontrackersv5" = "true" ]; then
    echo "Install Graphontrackersv5"
    build_dir /etc/$PROJECT_NAME/plugins/graphontrackersv5/etc $PROJECT_ADMIN $PROJECT_ADMIN 755
    $CAT $INSTALL_DIR/plugins/graphontrackersv5/db/install.sql | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('graphontrackersv5', '1');
EOF
    echo "path[]=\"$INSTALL_DIR/plugins/graphontrackersv5\"" >> /etc/$PROJECT_NAME/forgeupgrade/config.ini
fi

# IM plugin
if [ "$enable_plugin_im" = "true" ]; then
    echo "Install IM"

    # Create openfireadm MySQL user
    $CAT <<EOF | $MYSQL $pass_opt mysql
GRANT ALL PRIVILEGES on openfire.* to 'openfireadm'@'$mysql_httpd_host' identified by '$openfire_passwd';
GRANT SELECT ON $PROJECT_NAME.user to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON $PROJECT_NAME.groups to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON $PROJECT_NAME.user_group to 'openfireadm'@'$mysql_httpd_host';
GRANT SELECT ON $PROJECT_NAME.session to 'openfireadm'@'$mysql_httpd_host';
FLUSH PRIVILEGES;
EOF
    # Install plugin
    build_dir /etc/$PROJECT_NAME/plugins/IM/etc $PROJECT_ADMIN $PROJECT_ADMIN 755
    $CAT $INSTALL_DIR/plugins/IM/db/install.sql | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
    $CAT <<EOF | $MYSQL -u $PROJECT_ADMIN $PROJECT_NAME --password=$codendiadm_passwd
INSERT INTO plugin (name, available) VALUES ('IM', '1');
EOF
    # Initialize Jabbex
    IM_ADMIN_GROUP='imadmingroup'
    IM_ADMIN_USER='imadmin-bot'
    IM_ADMIN_USER_PW='1M@dm1n'
    IM_MUC_PW='Mu6.4dm1n' # Doesn't need to change
    $PHP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/install.php -a -orp $rt_passwd -uod openfireadm -pod $openfire_passwd -ucd openfireadm -pcd $openfire_passwd -odb jdbc:mysql://$mysql_host:3306/openfire -cdb jdbc:mysql://$mysql_host:3306/$PROJECT_NAME -ouri $sys_default_domain -gjx $IM_ADMIN_GROUP -ujx $IM_ADMIN_USER -pjx $IM_ADMIN_USER_PW -pmuc $IM_MUC_PW -fdn $PROJECT_NAME
    echo "path[]=\"$INSTALL_DIR/plugins/IM\"" >> /etc/$PROJECT_NAME/forgeupgrade/config.ini

    # Enable service
    enable_service openfire
    control_service openfire restart
fi


##############################################
# Register buckets in forgeupgrade
#
/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/$PROJECT_NAME/forgeupgrade/config.ini record-only


# Ensure /etc/sudoers.d is used
if [ -e /etc/sudoers ]; then
    if ! grep -q /etc/sudoers.d /etc/sudoers; then
	echo "#includedir /etc/sudoers.d" >>/etc/sudoers
    fi
fi

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
