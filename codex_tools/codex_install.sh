#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2003. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# THIS FILE IS THE PROPERTY OF XEROX AND IS ONLY DISTRIBUTED WITH A
# COMMERCIAL LICENSE OF CODEX. IT IS *NOT* DISTRIBUTED UNDER THE GNU
# PUBLIC LICENSE.
#
#  $Id$
#
#      Originally written by Laurent Julliard 2003, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be place at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#

progname=$0
scriptdir=`dirname $progname`
cd ${scriptdir};TOP_DIR=`pwd`;cd -
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/tmp/todo_codex.txt
CODEX_TOPDIRS="SF site-content documentation cgi-bin codex_tools"

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
    $MKDIR -p "$1" 2>/dev/null; chown "$2.$3" "$1";chmod "$4" "$1";
}

make_backup() {
    # $1: file name
    file="$1"
    backup_file="$1.nocodex"
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
  $PERL -pi -e 's/$2/$3/g' $1
}

##############################################
# CodeX installation
##############################################

##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Check we are running on RH 7.3
#
RH_RELEASE="3ES"
yn="y"
rpm -q redhat-release-${RH_RELEASE} 2>/dev/null 1>&2
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

rm -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX INSTALLATION (see $TODO_FILE)"


##############################################
# Check Required Stock RedHat RPMs are installed
#
rpms_ok=1
for rpm in openssh-server openssh openssh-clients openssh-askpass \
   openssl openldap perl perl-DBI perl-CGI \
   sendmail telnet bind ntp samba python php php-mysql php-ldap
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
# Create Groups and Users
#

make_backup /etc/passwd
make_backup /etc/shadow
make_backup /etc/group

# Delete users that could be part of the groups (otherwise groupdel fails!)
for u in mailman dummy sourceforge ftp ftpadmin
do
    $USERDEL $u 2>/dev/null 1>&2
done

# Create Groups
create_group sourceforge 104
create_group dummy 103
create_group mailman 106
create_group ftpadmin 96
create_group ftp 50

# Ask for domain name
read -p "CodeX Domain name: " sys_default_domain
# Ask for domain name
read -p "Your Company short name (e.g. Xerox): " sys_org_name
read -p "Your Company long name (e.g. Xerox Corporation): " sys_long_org_name
read -p "Codex Server fully qualified machine name: " sys_fullname
read -p "Codex Server IP address: " sys_ip_address
read -p "LDAP server name: " sys_ldap_server
read -p "Windows domain (Samba): " sys_win_domain

# Ask for user passwords
rt_passwd="a"; rt_passwd2="b";
while [ "$rt_passwd" != "$rt_passwd2" ]; do
    read -p "Password for user root: " rt_passwd
    read -p "Retype root password: " rt_passwd2
done

sf_passwd="a"; sf_passwd2="b";
while [ "$sf_passwd" != "$sf_passwd2" ]; do
    read -p "Password for user sourceforge: " sf_passwd
    read -p "Retype sourceforge password: " sf_passwd2
done

mm_passwd="a"; mm_passwd2="b";
while [ "$mm_passwd" != "$mm_passwd2" ]; do
    read -p "Password for user mailman: " mm_passwd
    read -p "Retype mailman password: " mm_passwd2
done

py_cmd="import crypt; print crypt.crypt(\"$rt_passwd\",\"\$1\$e4h67niB\$\")"
rt_encpasswd=`python -c "$py_cmd"`
py_cmd="import crypt; print crypt.crypt(\"$sf_passwd\",\"\$1\$h67e4niB\$\")"
sf_encpasswd=`python -c "$py_cmd"`
py_cmd="import crypt; print crypt.crypt(\"$mm_passwd\",\"\$1\$eniB4h67\$\")"
mm_encpasswd=`python -c "$py_cmd"`

# Create Users
$USERMOD -p "$rt_encpasswd" root

$USERDEL sourceforge 2>/dev/null 1>&2
$USERADD -c 'Owner of CodeX directories' -M -d '/home/httpd' -p "$sf_encpasswd" -u 104 -g 104 -s '/bin/bash' -G ftpadmin sourceforge

$USERDEL mailman 2>/dev/null 1>&2
$USERADD -c 'Owner of Mailman directories' -M -d '/home/mailman' -p "$mm_encpasswd" -u 106 -g 106 -s '/bin/bash' mailman

$USERDEL ftpadmin 2>/dev/null 1>&2
$USERADD -c 'FTP Administrator' -M -d '/home/ftp' -u 96 -g 96 ftpadmin

$USERDEL ftp 2>/dev/null 1>&2
$USERADD -c 'FTP User' -M -d '/home/ftp' -u 14 -g 50 ftp

$USERDEL dummy 2>/dev/null 1>&2
$USERADD -c 'Dummy CodeX User' -M -d '/home/dummy' -u 103 -g 103 dummy

# Build file structure

build_dir /home/users sourceforge sourceforge 775
build_dir /home/groups sourceforge sourceforge 775

build_dir /home/dummy dummy dummy 700
build_dir /home/dummy/dumps dummy dummy 755

build_dir /home/ftp root ftp 755
build_dir /home/ftp/bin ftpadmin ftpadmin 111
build_dir /home/ftp/etc ftpadmin ftpadmin 111
build_dir /home/ftp/lib ftpadmin ftpadmin 755
build_dir /home/ftp/codex root root 755
build_dir /home/ftp/pub ftpadmin ftpadmin 755
build_dir /home/ftp/incoming ftpadmin ftpadmin 3777

build_dir /home/large_tmp root root 1777
build_dir /home/log sourceforge sourceforge 755
build_dir /home/log/cvslogs sourceforge sourceforge 775
build_dir /home/mailman mailman mailman 2775
build_dir /home/sfcache sourceforge sourceforge 755
build_dir /home/tools root root 755
build_dir /home/var root root 755
build_dir /home/var/lib root root 755
#build_dir /home/var/lib/mysql mysql bin 755 # see CodeX DB installation
build_dir /etc/skel_codex root root 755
build_dir /etc/codex sourceforge sourceforge 755
build_dir /etc/codex/conf sourceforge sourceforge 755
build_dir /etc/codex/documentation sourceforge sourceforge 755
build_dir /etc/codex/site-content sourceforge sourceforge 755
build_dir /etc/codex/site-content/en_US sourceforge sourceforge 755
build_dir /etc/codex/themes sourceforge sourceforge 755

build_dir /var/run/log_accum root root 1777
build_dir /cvsroot sourceforge sourceforge 755
build_dir /cvsroot/.mysql_backup mysql mysql 755
build_dir /cvsroot/.mysql_backup/old root root 775



######
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

# wu-ftpd
echo "Removing Redhat vsftp daemon.."
$RPM -e --nodeps vsftpd 2>/dev/null
echo "Installing wu-ftpd..."
cd ${RPMS_DIR}/others
$RPM -Uvh --force wu-ftpd*.i386.rpm

# -> perlsuid
echo "Removing Perl suid if any..."
$RPM -e --nodeps perlsuid-perl 2>/dev/null
echo "Installing Perl suid..."
cd ${RPMS_DIR}/others
$RPM -Uvh --force perl-suidperl*.i386.rpm

# -> Perl DBD for MySQL
echo "Removing Redhat Perl DBD MySQL if any..."
$RPM -e --nodeps perl-DBD-MySQL 2>/dev/null
echo "Installing Perl DBD MySQL..."
cd ${RPMS_DIR}/perl-dbd-mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-DBD-MySQL-*.i386.rpm

# -> mysql
echo "Removing RedHat MySQL..."
$SERVICE mysql stop 2>/dev/null
sleep 2
[ -e /usr/bin/mysqladmin ] && /usr/bin/mysqladmin shutdown 2>/dev/null
sleep 2
$RPM -e --nodeps mysql mysql-devel mysql-server 2>/dev/null
echo "Installing MySQL RPMs for CodeX...."
cd ${RPMS_DIR}/mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/MySQL-*.i386.rpm
$SERVICE mysql start
$CHKCONFIG mysql on

# -> apache
echo "Removing RedHat Apache..."
$SERVICE httpd stop
$RPM -e --nodeps httpd httpd-devel httpd-manual 2>/dev/null
echo "Installing Apache RPMs for CodeX...."
cd ${RPMS_DIR}/apache
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/httpd-*.i386.rpm
$CHKCONFIG httpd on
# restart Apache after PHP installation - see below

# -> jre
echo "Removing RedHat Java JRE..."
$RPM -e --nodeps jre j2re 2>/dev/null
echo "Installing Java JRE RPMs for CodeX...."
cd ${RPMS_DIR}/jre
newest_rpm=`$LS -1 -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/j2re-*.i?86.rpm
cd /usr/java
newest_jre=`$LS -1d jre* | $TAIL -1`
$LN -sf $newest_jre jre

# -> cvs
echo "Removing RedHat CVS .."
$RPM -e --nodeps cvs 2>/dev/null
echo "Installing CVS RPMs for CodeX...."
cd ${RPMS_DIR}/cvs
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/cvs-*.i386.rpm

# Restart Apache after PHP is installed
$SERVICE httpd restart

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
# Install the CodeX software 
#
echo "Installing the CodeX software..."
cd /home/httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge /home/httpd
$FIND /home/httpd -type f -exec $CHMOD u+rw,g+rw,o-w+r, {} \;
$FIND /home/httpd -type d -exec $CHMOD 775 {} \;

make_backup /etc/httpd/conf/httpd.conf
for f in /etc/httpd/conf/cvsweb.conf /etc/httpd/conf/httpd.conf \
/etc/httpd/conf/mailman.conf /etc/httpd/conf.d/ssl.conf \
/etc/httpd/conf.d/php.conf /etc/codex/conf/local.inc; do
    yn="y"
    fn=`basename $f`
    [ -f "$f" ] && read -p "$f already exist. Overwrite? [y|n]:" yn

    if [ "$yn" = "y" ]; then
	$CP -f $f $f.orig
	$CP -f /home/httpd/SF/etc/$fn.dist $f
    fi

    $CHOWN sourceforge.sourceforge $f
    $CHMOD 640 $f
done

# CodeX User Guide
# a) copy the local parameters file in custom area
# b) create the html target directory
# c) create the PDF target directory
#
$MKDIR -p  /etc/codex/documentation/user_guide/xml/en_US
$CHOWN -R sourceforge.sourceforge /etc/codex/documentation
$CP /home/httpd/documentation/user_guide/xml/en_US/ParametersLocal.dtd /etc/codex/documentation/user_guide/xml/en_US
$MKDIR -p  /home/httpd/documentation/user_guide/html/en_US
$CHOWN -R sourceforge.sourceforge /home/httpd/documentation/user_guide/html/en_US
$MKDIR -p  /home/httpd/documentation/user_guide/pdf/en_US
$CHOWN -R sourceforge.sourceforge /home/httpd/documentation/user_guide/pdf/en_US
$TOUCH /etc/httpd/conf/codex_vhosts.conf
$CP /home/httpd/codex_tools/backup_job /home/tools
$CHOWN root.root /home/tools/backup_job
$CHMOD 740 /home/tools/backup_job

# replace string patterns in local.inc
substitute '/etc/codex/conf/local.inc' '%sys_default_domain%' "$sys_default_domain" 
substitute '/etc/codex/conf/local.inc' '%sys_dbpasswd%' "$sf_passwd" 
substitute '/etc/codex/conf/local.inc' '%sys_ldap_server%' "$sys_ldap_server" 
substitute '/etc/codex/conf/local.inc' '%sys_org_name%' "$sys_org_name" 
substitute '/etc/codex/conf/local.inc' '%sys_long_org_name%' "$sys_long_org_name" 
substitute '/etc/codex/conf/local.inc' '%sys_fullname%' "$sys_fullname" 
substitute '/etc/codex/conf/local.inc' '%sys_win_domain%' "$sys_win_domain" 

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

# replace string patterns in ssl.conf
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Customize /etc/codex/conf/local.inc"
todo "Customize /etc/httpd/conf/httpd.conf"
todo "Customize /etc/httpd/conf/cvsweb.conf. Edit %CVSROOT (if needed) and $logo"
todo "Customize /etc/httpd/conf/mailman.conf"
todo "Customize /home/httpd/documentation/user_guide/xml/en_US/ParametersLocal.dtd"
todo "Customize /home/tools/backup_job"

##############################################
# Installing the CodeX database
#
echo "Installing phpMyAdmin..."
cd /home/httpd
$RM -rf phpMyAdmin*
$TAR xfj ${nonRPMS_DIR}/phpMyAdmin/phpMyAdmin-*
dir_entry=`$LS -1d phpMyAdmin-*`
$LN -sf ${dir_entry} phpMyAdmin
$CHOWN -R sourceforge.sourceforge /home/httpd/phpMyAdmin*

todo "Customize phpMyAdmin. Edit /home/httpd/phpMyAdmin/config.inc.php"
todo "  - $cfg['PmaAbsoluteUri'] = 'http://$sys_default_domain/phpMyAdmin';"
todo "  - $cfg['Servers'][$i]['auth_type']     = 'http'; "
todo "  - $cfg['Servers'][$i]['user']          = 'sourceforge';"
todo "  - $cfg['Servers'][$i]['only_db']       = 'sourceforge';";

##############################################
# Installing the CodeX database
#
echo "Creating the CodeX database..."

yn="-"
freshdb=0
pass_opt=""
if [ -d "/var/lib/mysql/sourceforge" ]; then
    read -p "CodeX Database already exists. Overwrite? [y|n]:" yn
fi

# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

# Delete the CodeX DB if asked for
if [ "$yn" = "y" ]; then
    $MYSQL -u root $pass_opt -e "drop database sourceforge"
fi

if [ ! -d "/var/lib/mysql/sourceforge" ]; then
    freshdb=1
    $MYSQL -u root $pass_opt -e "create database sourceforge"
    $CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT ALL PRIVILEGES on *.* to sourceforge@localhost identified by '$sf_passwd' WITH GRANT OPTION;
GRANT ALL PRIVILEGES on *.* to root@localhost identified by '$rt_passwd';
FLUSH PRIVILEGES;
EOF
fi

if [ $freshdb -eq 1 ]; then
echo "Populating the CodeX database..."
cd /home/httpd/SF/db/mysql/
$MYSQL -u sourceforge sourceforge --password=$sf_passwd < database_structure.sql   # create the DB
cp database_initvalues.sql /tmp/database_initvalues.sql
substitute '/tmp/database_initvalues.sql' '_DOMAIN_NAME_' "$sys_default_domain"
$MYSQL -u sourceforge sourceforge --password=$sf_passwd < /tmp/database_initvalues.sql  # populate with init values.
rm -f /tmp/database_initvalues.sql
fi

echo "Creating MySQL conf file..."
$CAT <<'EOF' >/etc/my.cnf
# The MySQL server
[mysqld]
log-bin=/cvsroot/.mysql_backup/codex-bin
skip-innodb
# file attachment can be 2M in size so take a bit of slack
# on the mysql packet size
set-variable = max_allowed_packet=3M

[safe_mysqld]
err-log=/var/log/mysqld.log
EOF

todo "You may want to move /var/lib/mysql to a larger file system (e.g. /home/var/lib/mysql) and create a symbolic link"

##############################################
# Mailman installation
#
MAILMAN_DIR="/home/mailman"
echo "Installing the mailman software in $MAILMAN_DIR..."
yn="-"
[ -d "$MAILMAN_DIR/bin" ] && read -p "Mailman already installed. Overwrite? [y|n]:" yn

if [ "$yn" = "y" -o "$yn" = "-" ]; then
    $RM -rf /tmp/mailman; $MKDIR -p /tmp/mailman; cd /tmp/mailman;
    $RM -rf $MAILMAN_DIR/*
    $TAR xvfz $nonRPMS_DIR/mailman/mailman-*.tgz
    newest_ver=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
    cd $newest_ver
    mail_gid=`id -g mail`
    cgi_gid=`id -g sourceforge`
    ./configure --prefix=$MAILMAN_DIR --with-mail-gid=$mail_gid --with-cgi-gid=$cgi_gid
    $MAKE install
fi
$CHOWN -R mailman.mailman $MAILMAN_DIR
$CHMOD a+rx,g+ws $MAILMAN_DIR
$MAILMAN_DIR/bin/mmsitepass $mm_passwd
$LN -sf $MAILMAN_DIR /usr/local/mailman
todo "Edit $MAILMAN_DIR/Mailman/mm_cfg.py and setup DEFAULT_HOST_NAME\n\
and DEFAULT_URL variables (overrides Defaults.py settings). Recompile with python -O mm_cfg.py"

##############################################
# Installing Sendmail
#
echo "Installing sendmail shell wrappers..."
cd /etc/smrsh
$LN -sf /usr/local/bin/gotohell
$LN -sf $MAILMAN_DIR/mail/mailman

todo "Finish sendmail settings (see installation Guide) and create codex-contact and codex-admin aliases in /etc/aliases"

##############################################
# CVS configuration
#
echo "Configuring the CVS server and CVS tracking tools..."
$TOUCH /etc/cvs_root_allow
$CHOWN sourceforge.sourceforge /etc/cvs_root_allow
$CHMOD 644 /etc/cvs_root_allow

make_backup /etc/xinetd.d/cvs
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

cd /home/httpd/SF/utils/cvs1
$CP log_accum /usr/local/bin
$CP commit_prep /usr/local/bin
cd /usr/local/bin
$CHOWN sourceforge.sourceforge log_accum commit_prep
$CHMOD 755 log_accum commit_prep
$CHMOD u+s log_accum   # sets the uid bit (-rwsr-xr-x)

cd /home/httpd/SF/etc
$CP cvsweb.conf.dist /etc/httpd/conf/cvsweb.conf
$CHOWN root.root /etc/httpd/conf/cvsweb.conf
$CHMOD 644 /etc/httpd/conf/cvsweb.conf

##############################################
# FTP server configuration
#
make_backup "/etc/xinetd.d/wu-ftpd"
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
# Create the custom default page for the project Web sites
#
echo "Creating the custom default page for the project Web sites..."
def_page=/home/httpd/SF/utils/custom/default_page.php
yn="y"
[ -f "$def_page" ] && read -p "Custom Default Project Home page already exists. Overwrite? [y|n]:" yn
if [ "$yn" = "y" ]; then
    $MKDIR -p /home/httpd/SF/utils/custom
    $CHOWN sourceforge.sourceforge /home/httpd/SF/utils/custom
    $CP /home/httpd/SF/utils/default_page.php /home/httpd/SF/utils/custom/default_page.php
fi
todo "Customize /home/httpd/SF/utils/custom/default_page.php (project web site default home page)"

##############################################
# Shell Access configuration
#
echo "Shell access configuration defaulted to 'No shell account'..."
$MYSQL -u sourceforge sourceforge --password=$sf_passwd -e "ALTER TABLE user ALTER COLUMN shell SET DEFAULT '/bin/false'"

##############################################
# DNS Configuration
#
todo "Create the DNS configuration files as explained in the CodeX Installation Guide"

##############################################
# Crontab configuration
#
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
EOF
crontab -u root /tmp/cronfile

echo "Installing  sourceforge user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# Re-generate the CodeX User Guide on a daily basis
00 03 * * * /home/httpd/SF/utils/generate_doc.sh
EOF
crontab -u sourceforge /tmp/cronfile

echo "Installing  mailman user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# At 5PM every day, mail reminders to admins as to pending requests
0 17 * * * /usr/bin/python -S /home/mailman/cron/checkdbs
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
#0,5,10,15,20,25,30,35,40,45,50,55 * * * * /usr/bin/python /home/mailman/cron/gate_news
#
# Every minute flush the all  queues only once.
* * * * * /usr/bin/python /home/mailman/bin/qrunner -o -r All
#
# At 3:27am every night, regenerate the gzip'd archive file.  Only
# turn this on if the internal archiver is used and
# GZIP_ARCHIVE_TXT_FILES is false in mm_cfg.py
27 3 * * * /usr/bin/python /home/mailman/cron/nightly_gzip
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
make_backup "/etc/logrotate.d/httpd"
echo "Installing log files rotation..."
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
$CHOWN root.root /etc/logrotate.d/apache
$CHMOD 644 /etc/logrotate.d/apache


make_backup "/etc/logrotate.d/ftpd"
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

##############################################
# Create CodeX Shell skeleton files
#
echo "Create CodeX Shell skeleton files..."
$MKDIR -p /etc/skel_codex

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

$CAT <<'EOF' >/etc/profile_codex
# /etc/profile_codex
#
# Specific login set up and messages for CodeX users`
 
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
 
---------------------------------
W E L C O M E   T O   C O D E X !
---------------------------------
                                                                               
You are currently in your user home directory: $HOME
EOM
                                                                               
echo "Your project home directories (Web site) are in:"
for i in $grplist
do
        echo "    - /home/groups/$i"
done
                                                                               
echo "Your project CVS root directories are in:"
for i in $grplist
do
        echo "    - /cvsroot/$i"
done
                                                                               
cat <<EOM
                                                                               
             *** IMPORTANT REMARK ***
The CodeX server hosts very valuable yet publicly available
data. Therefore we recommend that you keep working only in
the directories listed above for which you have full rights
and responsibilities.
                                                                               
EOM
EOF

todo "Create the shell login files for CodeX users in /etc/skel_codex"
todo "Customize /etc/php.ini: "
todo "  - register_globals = On"
todo "  - memory_limit = 30M"
todo "  - post_max_size = 20M"
todo "  - upload_max_file_size = 20M"
todo "  - include_path = .:/home/httpd/SF/www/include:/home/httpd/SF/www/phpMyAdmin"

# things to do by hand
todo "Change the default login shell if needed in the database (/sbin/nologin or /usr/local/bin/cvssh, etc."

# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 0
