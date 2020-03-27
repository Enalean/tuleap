#!/bin/bash
#
# Copyright (c) Enalean, Tuleap 2011-2018
# Copyright (c) STMicroelectronics, Codex 2009,2010
# Copyright (c) Xerox Corporation, Codendi 2001-2009.
#
# This file is licensed under the GNU General Public License version 2.
# See the file COPYING.
#
#      Originally written by Laurent Julliard 2004, Codendi Team, Xerox
#

# In order to keep a log of the installation, you may run the script with:
# ./setup.sh 2>&1 | tee /root/tuleap_install.log

###############################################################################
#
# Variables
#
###############################################################################

# path to directorie and files
RH_RELEASE='/etc/redhat-release'
SE_FILE='/etc/selinux/config'
TODO_FILE='/root/todo_tuleap.txt'
TULEAP_CACHE_DIR="/var/tmp/tuleap_cache"

# services name
CROND_SERVICE="crond"
HTTPD_SERVICE="httpd"
NAMED_SERVICE="named"
PROJECT_ADMIN="codendiadm"
SSHD_SERVICE="sshd"

# path to command line tools
AWK='/bin/awk'
CAT='/bin/cat'
CHGRP='/bin/chgrp'
CHKCONFIG='/sbin/chkconfig'
CHMOD='/bin/chmod'
CHOWN='/bin/chown'
CP='/bin/cp'
DIG='/usr/bin/dig'
FIND='/usr/bin/find'
GREP='/bin/grep'
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
INSTALL='/usr/bin/install'
IPCALC='/bin/ipcalc'
LN='/bin/ln'
LS='/bin/ls'
MKDIR='/bin/mkdir'
MV='/bin/mv'
PERL='/usr/bin/perl'
PHP='/opt/remi/php73/root/usr/bin/php'
RM='/bin/rm'
RPM='/bin/rpm'
SERVICE='/sbin/service'
SESTATUS='/usr/sbin/sestatus'
TAIL='/usr/bin/tail'
TOUCH='/bin/touch'
USERADD='/usr/sbin/useradd'
USERDEL='/usr/sbin/userdel'
USERMOD='/usr/sbin/usermod'

CMD_LIST=('AWK' 'CAT' 'CHGRP' 'CHKCONFIG' 'CHMOD' 'CHOWN' 'CP' 'DIG' 'FIND'
          'GREP' 'GROUPADD' 'GROUPDEL' 'INSTALL' 'IPCALC' 'LN' 'LS' 'MKDIR'
          'MV' 'PERL' 'PHP' 'RM' 'RPM' 'SERVICE' 'SESTATUS'
          'TAIL' 'TOUCH' 'USERADD' 'USERDEL' 'USERMOD')

# default parameter
generate_ssl_certificate="n"

# SELinux mode
SELINUX_MODE=$(${SESTATUS} | ${AWK} '/mode/ {print $NF}')

# check the version of RH/CentOS
if [ -e "${RH_RELEASE}" ]
then
    ${GREP} -i -q centos ${RH_RELEASE}
    if [ "${?}" -eq 0 ]
    then
        RH_FULL_VERSION=($(${AWK} '{print $1, $3}' ${RH_RELEASE}))
    else
        RH_FULL_VERSION=($(${AWK} '{print $1$2, $7}' ${RH_RELEASE}))
    fi
    RH_VERSION=$(echo ${RH_FULL_VERSION[1]})
else
    echo -e "\033[31mSorry, Tuleap is running only on RedHat/CentOS.\033[0m"
    exit 1
fi

PROJECT_NAME="tuleap"
INSTALL_DIR="/usr/share/${PROJECT_NAME}"

###############################################################################
#
# Functions
#
###############################################################################

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

info() {
    echo -e "\033[32m$@\033[0m"
}

error() {
    echo -e "\033[31m*** ERROR ***: $@\033[0m"
}

warning() {
    echo -e "\033[33m*** WARNING ***: $@\033[0m"
}

die() {
  error $@
  exit 1
}

recoverable_error() {
    if [ "$error_mode" == "force" ]; then
	warning $@
    else
	error $@
	exit 1
    fi
}

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
    $CAT /dev/urandom | tr -dc "a-zA-Z0-9" | fold -w 15 | head -1
}

has_package() {
    local pkg=$1
    $RPM -q $pkg >/dev/null 2>&1
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
    if [ "$enable_chkconfig" = "true" ]; then
        local service="$1"
        $CHKCONFIG $service on
    fi
}

dns_check() {
    if [ -z "${1}" ]
    then
        ${DIG} +short . A . AAAA
    else
        ${DIG} +short ${1} A ${1} AAAA | ${TAIL} -1
    fi
}

ip_check() {
    ${IPCALC} --silent --check "${1}"
    echo ${?}
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

    # Start service
    enable_service vsftpd
    control_service vsftpd restart
}

###############################################################################
#
# Bind DNS server configuration
#
setup_bind() {
	ZONE_DIR="/var/named/chroot/var/named"
	NAMED_GID="named"
	ETC_DIR="/var/named/chroot/etc"
    if [ -f $ZONE_DIR/$PROJECT_NAME.zone ]; then
        $CP -af $ZONE_DIR/$PROJECT_NAME.zone $ZONE_DIR/$PROJECT_NAME.zone.orig
    fi
    $CP -f $INSTALL_DIR/src/etc/codendi.zone.dist $ZONE_DIR/$PROJECT_NAME.zone

    $CHOWN root:$NAMED_GID $ZONE_DIR/$PROJECT_NAME.zone
    if [ -f "$ETC_DIR/named.conf" ]; then
        $CHGRP $NAMED_GID $ETC_DIR/named.conf
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
        LIST_OWNER=$PROJECT_NAME-admin@$sys_default_domain
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
# Apache setup
#
setup_apache() {
    # Move away useless Apache configuration files
    # before installing our own config files.
    local httpdconf="/etc/httpd/conf"
    local httpdconfd="/etc/httpd/conf.d"
    local logrtd="/etc/logrotate.d"
    local filesconf=('httpd.conf')
    local filesconfd=('subversion.conf' 'tuleap-vhost.conf' 'tuleap-svnroot.conf')

    echo "Renaming existing Apache configuration files..."
    cd ${httpdconfd}
    for f in *.conf
    do
        # Do not erase conf files provided by "our" packages and for which
        # we don't have a .dist version
        case "${f}" in
            "viewvc.conf"|"munin.conf"|"mailman.conf"|"perl.conf")
            continue;;
        esac

        yn="0"
        current_name="${f}"
        orig_name="${f}.rhel"

        if [ -f "${orig_name}" ]
        then
            read -p "${orig_name} already exist. Overwrite? [y|n]:" yn
        fi

        if [ "${yn,,}" = "n" ]
        then
            ${RM} -f ${current_name}
        else
            ${MV} -f ${current_name} ${orig_name}
        fi

        # In order to prevent RedHat from reinstalling those files during an
        # RPM update, re-create an empty file for each file deleted
        ${TOUCH} ${current_name}
    done
    cd - > /dev/null

    echo "Installing Apache configuration files..."
    make_backup ${httpdconf}/httpd.conf

    for i in ${filesconf[@]}
    do
        install_dist_conf ${httpdconf}/${i} ${i}.rhel6.dist
    done

    for f in ${filesconfd[@]}
    do
        install_dist_conf ${httpdconfd}/${f}
        fix_paths ${httpdconfd}/${f}
    done

    # replace string patterns in httpd.conf
    substitute ${httpdconf}/httpd.conf '%sys_default_domain%' \
        "$sys_default_domain"

    substitute ${httpdconfd}/tuleap-vhost.conf '%sys_default_domain%' \
        "$sys_default_domain"

        # replace string patterns in munin.conf (for MySQL authentication)
    if [ -f ${httpdconfd}/munin.conf ]
    then
        substitute ${httpdconfd}/munin.conf '%sys_dbauth_passwd%' \
            "${dbauth_passwd}"
    fi

    # Make $PROJECT_ADMIN a member of the apache group
    # This is needed to use the php session at /var/lib/php/session
    # (e.g. for phpwiki)
    ${USERMOD} -a -G apache ${PROJECT_ADMIN}

    # Log Files rotation configuration
    echo "Installing log files rotation..."

    ${CP} -f ${INSTALL_DIR}/src/etc/logrotate.httpd.conf ${logrtd}/httpd
    substitute "${logrtd}/httpd" "%PROJECT_NAME%" "${PROJECT_NAME}"

    ${CHOWN} root:root ${logrtd}/httpd
    ${CHMOD} 644 ${logrtd}/httpd
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
    substitute '/etc/libnss-mysql.cfg' '%sys_dbport%' "$mysql_port"
    if [ ! -z "$mysql_port" ]; then
        substitute '/etc/libnss-mysql.cfg' '#port' 'port'
    fi
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
    substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_mail_secure_mode = 0' 'sys_mail_secure_mode = 1'
    substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_create_project_in_one_step = 0' 'sys_create_project_in_one_step = 1'
    substitute "/etc/$PROJECT_NAME/conf/local.inc" 'sys_plugins_editable_configuration = 1' 'sys_plugins_editable_configuration = 0'
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
    substitute "/etc/$PROJECT_NAME/conf/database.inc" 'localhost' "$mysql_host_and_port"
}

###############################################################################
#
# Usage
#
usage() {
    cat <<EOF
Usage: $1 [options]
Options:
  --disable-auto-passwd            Do not automaticaly generate random passwords
  --disable-httpd-restart          Do not restart httpd during the setup
  --disable-generate-ssl-certs     Do not generate a new ssl certificate
  --disable-mysql-configuration    Do not modify my.cnf (not recommended)
  --disable-domain-name-check      Do not Domain Name check

  --sys-default-domain=<domain>    Server Domain name
  --sys-org-name=<string>          Your Company short name
  --sys-long-org-name=<string>     Your Company long name

  DNS delegation configuration:
  --enable-bind-config             Configure DNS server (only useful if you have subdomain delegation)
  --enable-subdomains              Setup subdomain usage (for project home pages)
  --sys-fullname=<fqdn>            Server fully qualified machine name
  --sys-ip-address=<ip address>    Server IP address

  Mysql local server configuration:
  --mysql-server-package=<package> Name of mysql server package name. Default: mysql-server

  Mysql remote server configuration:
  --mysql-host=<host>              Hostname (or IP) of mysql server
  --mysql-port=<integer>           Port if not default (3306)
  --mysql-user=<user>              Mysql User used to create database and grant permissions (default: root)
  --mysql-user-password=<password> Mysql user password on remote host
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
disable_subdomains="y"
disable_domain_name_check="n"

auto=""
auto_passwd="true"
configure_bind="false"
mysql_host="localhost"
mysql_port=""
mysql_default_port="3306"
mysql_httpd_host="localhost"
mysql_remote_server=""
mysql_my_cnf="y"
mysql_package_name="mysql-server"
mysql_user="root"
rt_passwd=""
restart_httpd="y"
error_mode=""
passwd_file=/root/.tuleap_passwd
enable_chkconfig="true"
unix_users_and_groups="true"
options_getopt=('auto,' 'disable-auto-passwd,' 'enable-bind-config,'
                'mysql-host:,' 'mysql-port:,' 'mysql-user:,'
                'mysql-user-password:,' 'mysql-httpd-host:,'
                'sys-default-domain:,' 'sys-fullname:,'
                'sys-ip-address:,' 'sys-org-name:,' 'sys-long-org-name:,'
                'enable-subdomains,' 'disable-httpd-restart,'
                'disable-generate-ssl-certs,' 'mysql-server-package:,'
                'disable-mysql-configuration,' 'disable-domain-name-check,'
                'disable-selinux,' 'force', 'password-file:', 'disable-chkconfig',
                'disable-unix-groups')

options=$(getopt -o h -l $(printf "%s" ${options_getopt[@]}) -- "$@")

if [ $? != 0 ] ; then echo "Terminating..." >&2 ; usage $0 ;exit 1 ; fi

eval set -- "${options}"

while true
do
    case "$1" in
    --force)
        error_mode='force'
        shift 1;;
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
        shift 1 ;;
    --password-file)
        passwd_file="$2"
        shift 2 ;;
    --disable-unix-groups)
        unix_users_and_groups="false"; shift 1;;
    --disable-chkconfig)
        enable_chkconfig="false"; shift 1;;
    --disable-auto-passwd)
        auto_passwd="false"; shift 1 ;;
    --enable-bind-config)
        configure_bind="true"; shift 1 ;;
    --enable-subdomains)
        disable_subdomains="n"; shift 1 ;;
    --disable-httpd-restart)
        restart_httpd="n"; shift 1 ;;
    --disable-generate-ssl-certs)
        generate_ssl_certificate="n"; shift 1 ;;
    --disable-mysql-configuration)
        mysql_my_cnf="n"; shift 1 ;;
    --disable-domain-name-check)
        disable_domain_name_check="y"; shift 1 ;;
    --disable-selinux)
        warning "The disable-selinux is deprecated"; shift 1 ;;
    --sys-default-domain)
        sys_default_domain="$2"; shift 2 ;;
    --sys-fullname)
        sys_fullname="$2"; shift 2 ;;
    --sys-ip-address)
        sys_ip_address="$2"; shift 2 ;;
    --sys-org-name)
        sys_org_name="$2"; shift 2 ;;
    --sys-long-org-name)
        sys_long_org_name="$2"; shift 2 ;;
    --mysql-host)
        mysql_host="$2";shift 2
        mysql_remote_server=true ;;
    --mysql-port)
        mysql_port="$2";shift 2
        mysql_remote_server=true ;;
    --mysql-user)
        mysql_user="$2"; shift 2 ;;
    --mysql-user-password)
        rt_passwd="$2"; shift 2 ;;
    --mysql-httpd-host)
        mysql_httpd_host="$2"; shift 2 ;;
    --mysql-server-package)
        mysql_package_name="$2"; shift 2 ;;
    -h|--help)
        usage $0 ;;
    --)
        shift 1; break ;;
    *)
        break ;;
    esac
done

mysql_host_and_port="$mysql_host"
if [ ! -z "$mysql_port" ]; then
    mysql_host_and_port="$mysql_host_and_port:$mysql_port"
fi

if [ -z "$mysql_remote_server" ]; then
    die "You must provide a --mysql-host"
fi

# SELinux status
if [ "${SELINUX_MODE}" = "enforcing" ]
then
    warning "Your SELinux is in ${SELINUX_MODE} mode!"
    warning "Tuleap does not currently support SELinux in enforcing mode."
    info "Set your SELinux in permissive mode."
    info "To achieve this, use setenforce 0 to enter permissive mode."
    info "Edit the ${SE_FILE} file for a permanent change."
    exit 1
fi

#############################################
# Check that all command line tools we need are available
#
for cmd in ${CMD_LIST[@]}
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done


##############################################
# Check release
#
RH_UPDATE="3"
if [ "x$RH_VERSION" != x ] && [ "$(echo -e "$RH_VERSION\n6.$RH_UPDATE" | sort -V | head -n 1)" != "$RH_VERSION" ]; then
    echo "Good! You are running ${RH_FULL_VERSION[@]}"
else
    echo "This machine is not running RedHat Enterprise Linux or CentOS 6.${RH_UPDATE}"
    echo "You should consider to upgrade your system before going any further (yum upgrade)."
    read -p "Continue? [y|n]: " yn
    if [ "$yn" = "n" ]; then
        echo "Bye now!"
        exit 1
    fi
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

${RM} -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE TULEAP INSTALLATION (see $TODO_FILE)"

echo
echo "Configuration questions"
echo

while :
do
    if [ "${disable_domain_name_check}" = "y" ]
    then
        todo "Your domain name is $sys_default_domain"
        sys_fullname=$sys_default_domain
        break
    fi

    if [ -z "${sys_default_domain}" ]
    then
        read -p "Tuleap domain name: (e.g. mytuleap.example.com): " \
            sys_default_domain
    fi

    if [ "$(ip_check ${sys_default_domain})" -eq 0 ]
    then
        todo "Your domain name is $sys_default_domain"
        sys_fullname=$sys_default_domain
        break
    fi

    if [ -z "$(dns_check ${sys_default_domain})" ]
    then
        yn="n"
        warning "Your domain name is not valid!"
        read -p "Do you want to continue[y/n]? " yn

        if [ ${yn,,} = "y" ]
        then
            todo "Your domain name is $sys_default_domain"
            sys_fullname=$sys_default_domain
            break
        fi
    fi

    if [ ! -z "${sys_default_domain}" -a \
        ! -z "$(dns_check ${sys_default_domain})" ]
    then
        todo "Your domain name is $sys_default_domain"
        sys_fullname=$sys_default_domain
        break
    fi

    sys_default_domain=""
done

if [ -z "$sys_org_name" ]
then
    read -p "Your company name: (e.g. My Company): " sys_org_name
    sys_long_org_name=$sys_org_name
fi

if [ "$configure_bind" != "false" ]; then
    configure_bind="true"

    echo "Bind (DNS server) configuration questions"

    if [ -z "$sys_fullname" ]
    then
            read -p "Fully qualified machine name: " sys_fullname
    fi

    if [ -z "$sys_ip_address" ]
    then
            read -p "IP address: " sys_ip_address
    fi
fi

if [ "$auto_passwd" = "true" ]; then
    # Save in /root/.tuleap_passwd
    $RM -f $passwd_file
    touch $passwd_file
    $CHMOD 0600 $passwd_file

    # Mysql Root password (what if remote DB ?)
    if [ -z "$rt_passwd" ]; then
        rt_passwd=$(generate_passwd)
        echo "Mysql user ($mysql_user) : $rt_passwd" >> $passwd_file
    fi

    # For both DB and system
    codendiadm_passwd=$(generate_passwd)
    echo "Codendiadm unix & DB ($PROJECT_ADMIN): $codendiadm_passwd" >> $passwd_file

    # Mailman (only if installed)
    if [ "$enable_core_mailman" = "true" ]; then
        mm_passwd=$(generate_passwd)
        echo "Mailman siteadmin: $mm_passwd" >> $passwd_file
    fi

    # Only for ftp/ssh/cvs
    dbauth_passwd=$(generate_passwd)
    echo "Libnss-mysql DB user (dbauthuser): $dbauth_passwd" >> $passwd_file

    # Ask for site admin ?
    siteadmin_password=$(generate_passwd)
    echo "Site admin password (admin): $siteadmin_password" >> $passwd_file

    todo "Automatically generated passwords are stored in $passwd_file"
else
    # Ask for user passwords

    if [ -z "$rt_passwd" ]; then
        rt_passwd=$(input_password "MySQL $mysql_user")
    fi

    codendiadm_passwd=$(input_password "$PROJECT_ADMIN user")

    siteadmin_password=$(input_password "Site admin password (admin)")

    if [ "$enable_core_mailman" = "true" ]; then
	mm_passwd=$(input_password "mailman user")
    fi

    echo "DB authentication user: MySQL user that will be used for user authentication"
    echo "  Please do not reuse a password here, as this password will be stored in clear on the filesystem and will be accessible to all logged-in user."
    dbauth_passwd=$(input_password "DB Authentication user")
fi

set -e

echo "Initialize MySQL database"
/usr/bin/tuleap-cfg setup:mysql-init \
    --host="${mysql_host}" \
    --admin-user="${mysql_user}" \
    --admin-password="${rt_passwd}" \
    --db-name="${PROJECT_NAME}" \
    --app-user="${PROJECT_ADMIN}@%" \
    --app-password="${codendiadm_passwd}"

echo "Load MySQL database"
/usr/bin/tuleap-cfg setup:mysql \
    --host="${mysql_host}" \
    --user="${PROJECT_ADMIN}" \
    --password="${codendiadm_passwd}" \
    --dbname="${PROJECT_NAME}" \
    "${siteadmin_password}" \
    "${sys_default_domain}"

set +e

# Update codendiadm user password
echo "$codendiadm_passwd" | passwd --stdin $PROJECT_ADMIN
build_dir /home/$PROJECT_ADMIN $PROJECT_ADMIN $PROJECT_ADMIN 700

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
build_dir $TULEAP_CACHE_DIR $PROJECT_ADMIN $PROJECT_ADMIN 755
# config dirs
build_dir /etc/skel_$PROJECT_NAME root root 755
build_dir /etc/$PROJECT_NAME $PROJECT_ADMIN $PROJECT_ADMIN 755
build_dir /etc/$PROJECT_NAME/conf $PROJECT_ADMIN $PROJECT_ADMIN 700
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

##############################################
# Install the Tuleap software
#

setup_tuleap

if [ "$generate_ssl_certificate" = "y" ]; then
    $INSTALL_DIR/src/utils/generate_ssl_certificate.sh
fi

setup_apache

if [ "$unix_users_and_groups" = "true" ]; then
    setup_nss
else
    sed -i \
        -e 's/\/home\/users//' \
        -e 's/\/home\/groups//' \
        /etc/$PROJECT_NAME/conf/local.inc
fi

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

todo "Customize /etc/$PROJECT_NAME/conf/local.inc and /etc/$PROJECT_NAME/conf/database.inc"
todo "You may also want to customize /etc/httpd/conf/httpd.conf"

##############################################
# Installing phpMyAdmin
#

# Allow read/write access to DAV lock dir for $PROJECT_ADMIN in case we want ot enable WebDAV.
$CHMOD 770 /var/lib/dav/

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

todo "Customize /etc/$PROJECT_NAME/site-content information for your site."
todo "  For instance: contact/contact.txt "
todo ""
todo "Default admin credentials are login: admin / password: $siteadmin_password"
todo "CHANGE DEFAULT CREDENTIALS BEFORE FIRST USAGE"

##############################################
# Create Tuleap profile script
#

# customize the global profile
if [ "$unix_users_and_groups" = "true" ]; then
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

# Remove the last comma causing a bug on Centos6
field_list=`echo $field_list | sed 's/,$//'`

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
fi

##############################################
# Make sure all major services are on
#
enable_service $SSHD_SERVICE
enable_service $HTTPD_SERVICE
enable_service $CROND_SERVICE

/etc/init.d/$PROJECT_NAME start

if [ "$restart_httpd" = "y" ]; then
    control_service $HTTPD_SERVICE restart
fi

control_service $CROND_SERVICE restart

# NSCD is the Name Service Caching Daemon.
# It is very useful when libnss-mysql is used for authentication
if [ "$unix_users_and_groups" = "true" ]; then
    enable_service nscd
    control_service nscd restart
fi

if [ "$enable_munin" = "true" ]; then
    enable_service munin-node
    control_service munin-node restart
fi

##############################################
# Install & configure forgeupgrade for Tuleap
#

$INSTALL --group=$PROJECT_ADMIN --owner=$PROJECT_ADMIN --mode=0755 --directory /etc/$PROJECT_NAME/forgeupgrade
$INSTALL --group=$PROJECT_ADMIN --owner=$PROJECT_ADMIN --mode=0644 $INSTALL_DIR/src/etc/forgeupgrade-config.ini.dist /etc/$PROJECT_NAME/forgeupgrade/config.ini
substitute /etc/$PROJECT_NAME/forgeupgrade/config.ini "%project_name%" "$PROJECT_NAME"

##############################################
# *Last* step: install plugins
#

echo "Install tuleap plugins"

echo "Install Docman"
su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php docman' -l codendiadm

# Tracker plugin
if [ "$enable_plugin_tracker" = "true" ]; then
    echo "Install tracker"
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php tracker' -l codendiadm
fi

# GraphOnTrackersv5 plugin
if [ "$enable_plugin_graphontrackersv5" = "true" ]; then
    echo "Install Graphontrackersv5"
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php graphontrackersv5' -l codendiadm
fi

# Agile Dashboard plugin
if [ -d "$INSTALL_DIR/plugins/agiledashboard" ]; then
    echo "Install AgileDashboard"
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php cardwall' -l codendiadm
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php agiledashboard' -l codendiadm
fi

# Git plugin
if [ -d "$INSTALL_DIR/plugins/git" ]; then
    echo "Install Git"
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php git' -l codendiadm
fi

if [ -d "$INSTALL_DIR/plugins/svn" ]; then
    echo "Install SVN"
    su -c '/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn' -l codendiadm
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

if [ "$auto_passwd" = "true" ]; then
    todo "Auto generated passwords (mysql, application, etc) are stored in $passwd_file"
fi

################### Switch to PHP 7.3 + nginx
control_service httpd stop
$INSTALL_DIR/tools/utils/php73/run.php
control_service httpd start
enable_service nginx
control_service nginx start
enable_service php73-php-fpm
control_service php73-php-fpm start

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
