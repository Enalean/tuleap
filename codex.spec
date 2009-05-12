
%define sys_default_domain 		%sys_default_domain%
%define sys_org_name 			%sys_org_name%
%define sys_long_org_name 		%sys_long_org_name%
%define sys_fullname 				%sys_fullname%
%define sys_ip_address 			%sys_ip_address%
%define sys_ldap_server 			%sys_ldap_server%
%define sys_win_domain 			%sys_win_domain%
%define active_shell 				%active_shell%
%define create_ssl_certificate 		%create_ssl_certificate%
%define disable_subdomains 		%disable_subdomains%

%define rt_passwd 				%rt_passwd%
%define codexadm_passwd 		%codexadm_passwd%
%define mm_passwd 				%mm_passwd%
%define slm_passwd 				%slm_passwd%
%define openfire_passwd 			%openfire_passwd%


Name:           codendi
Version:        3.6
Release:        1%{?dist}
Summary:     Codendi Collaborative Development Environment

BuildArch: noarch
License: GPL
Group: Development/Tools
Source0: %{name}-%{version}.tar.bz2
URL: http://www.codendi.org/
BuildRoot: %{_tmppath}/%{name}-%{version}-root

BuildRequires:  perl
      
Requires: openssh-server openssh openssh-clients
Requires: httpd  apr apr-util 
Requires: mod_ssl vsftpd
Requires: openssl openldap perl 
Requires: perl-DBI perl-DBD-MySQL gd
Requires: sendmail telnet bind 
Requires: bind-chroot caching-nameserver ntp 
Requires: samba python perl-suidperl
Requires: python-devel rcs sendmail-cf 
Requires: perl-URI perl-HTML-Tagset perl-Digest-SHA1 
Requires: perl-Digest-HMAC perl-Socket6 perl-HTML-Parser 
Requires: perl-libwww-perl php php-ldap 
Requires: php-mysql mysql-server mysql MySQL-python 
Requires: php-mbstring php-gd php-soap 
Requires: php-xml perl-DateManip sysstat 
Requires: curl aspell gd-devel 
Requires: freetype-devel libpng-devel libjpeg-devel 
Requires: libart_lgpl dump dejavu-lgc-fonts
Requires: compat-libstdc++-33 policycoreutils coreutils 
Requires: selinux-policy selinux-policy-targeted libselinux
Requires: zip unzip enscript 
Requires: xinetd


%description
Codendi is a web-based Collaborative Development Environment offering easy access to CVS, Subversion, mailing lists, bug tracking, 
message boards/forums, task management, permanent file archival, and total web-based administration.

###Define our function
# %1: groupname, %2: groupid
%define create_group() groupdel "%1" 2>/dev/null; groupadd -g "%2" "%1" 2>/dev/null 1>&2;
# %1: dir path, %2: user, %3: group, %4: permission
%define build_dir() mkdir -p "%1" 2>/dev/null; chown "%2.%3" "%1" 2>/dev/null 1>&2; chmod "%4" "%1";
# %1: filename, %2: string to match, %3: replacement string
%define substitute() sed -i "s|%2|%3|g" %1;

%prep
%setup 

%build

%pre

###Create group
%create_group codexadm 104 2>/dev/null 1>&2
%create_group dummy 103 2>/dev/null 1>&2
%create_group mailman 106 2>/dev/null 1>&2
%create_group ftpadmin 95 2>/dev/null 1>&2
%create_group ftp 50 2>/dev/null 1>&2

###Create user
userdel codexadm 2>/dev/null 1>&2
useradd -c 'Owner of CodeX directories' -M -d '/home/codexadm' -p "%codexadm_passwd" -u 104 -g 104 -s '/bin/bash' -G ftpadmin,mailman codexadm 2>/dev/null 1>&2

userdel mailman 2>/dev/null 1>&2
useradd -c 'Owner of Mailman directories' -M -d '/usr/lib/mailman' -p "%mm_passwd" -u 106 -g 106 -s '/sbin/nologin' mailman 2>/dev/null 1>&2

userdel ftpadmin 2>/dev/null 1>&2
useradd -c 'FTP Administrator' -M -d '/var/lib/codex/ftp' -u 95 -g 95 ftpadmin 2>/dev/null 1>&2

userdel ftp 2>/dev/null 1>&2
useradd -c 'FTP User' -M -d '/var/lib/codex/ftp' -u 14 -g 50 ftp 2>/dev/null 1>&2

userdel dummy 2>/dev/null 1>&2
useradd -c 'Dummy CodeX User' -M -d '/var/lib/codex/dumps' -u 103 -g 103 dummy 2>/dev/null 1>&2



### Build file structure
%build_dir /usr/share/codex codexadm codexadm 775
%build_dir /home/users codexadm codexadm 775
%build_dir /home/groups codexadm codexadm 775
# home directories
%build_dir /home/codexadm codexadm codexadm 700
# data dirs
%build_dir /var/lib/codex codexadm codexadm 755
%build_dir /var/lib/codex/dumps dummy dummy 755
%build_dir /var/lib/codex/ftp root ftp 755
%build_dir /var/lib/codex/ftp/codex root root 711
%build_dir /var/lib/codex/ftp/pub ftpadmin ftpadmin 755
%build_dir /var/lib/codex/ftp/incoming ftpadmin ftpadmin 3777
%build_dir /var/lib/codex/wiki codexadm codexadm 700
%build_dir /var/lib/codex/backup codexadm codexadm 711
%build_dir /var/lib/codex/backup/mysql mysql mysql 770 
%build_dir /var/lib/codex/backup/mysql/old root root 700
%build_dir /var/lib/codex/backup/subversion root root 700
%build_dir /var/lib/codex/docman codexadm codexadm 700
# log dirs
%build_dir /var/log/codex codexadm codexadm 755
%build_dir /var/log/codex/cvslogs codexadm codexadm 775
%build_dir /var/tmp/codex_cache codexadm codexadm 755
# bin dirs
%build_dir /usr/lib/codex codexadm codexadm 755
%build_dir /usr/lib/codex/bin codexadm codexadm 755
# config dirs
%build_dir /etc/skel_codex root root 755
%build_dir /etc/codex codexadm codexadm 755
%build_dir /etc/codex/conf codexadm codexadm 700
%build_dir /etc/codex/documentation codexadm codexadm 755
%build_dir /etc/codex/documentation/user_guide codexadm codexadm 755
%build_dir /etc/codex/documentation/user_guide/xml codexadm codexadm 755
%build_dir /etc/codex/documentation/cli codexadm codexadm 755
%build_dir /etc/codex/documentation/cli/xml codexadm codexadm 755
%build_dir /etc/codex/site-content codexadm codexadm 755
%build_dir /etc/codex/site-content/en_US codexadm codexadm 755
%build_dir /etc/codex/site-content/en_US/others codexadm codexadm 755
%build_dir /etc/codex/site-content/fr_FR codexadm codexadm 755
%build_dir /etc/codex/site-content/fr_FR/others codexadm codexadm 755
%build_dir /etc/codex/themes codexadm codexadm 755
%build_dir /etc/codex/plugins codexadm codexadm 755
%build_dir /etc/codex/plugins/docman codexadm codexadm 755
%build_dir /etc/codex/plugins/pluginsadministration codexadm codexadm 755
%build_dir /etc/codex/plugins/serverupdate codexadm codexadm 755
# SCM dirs
%build_dir /var/run/log_accum root root 777
%build_dir /var/lib/codex/cvsroot codexadm codexadm 755
%build_dir /var/lib/codex/svnroot codexadm codexadm 755
%build_dir /var/lock/cvs root root 751
ln -sf /var/lib/codex/cvsroot /cvsroot
ln -sf /var/lib/codex/svnroot /svnroot

touch /var/lib/codex/ftp/incoming/.delete_files
chown codexadm.ftpadmin /var/lib/codex/ftp/incoming/.delete_files
chmod 750 /var/lib/codex/ftp/incoming/.delete_files
touch /var/lib/codex/ftp/incoming/.delete_files.work
chown codexadm.ftpadmin /var/lib/codex/ftp/incoming/.delete_files.work
chmod 750 /var/lib/codex/ftp/incoming/.delete_files.work
%build_dir /var/lib/codex/ftp/codex/DELETED codexadm codexadm 750

touch /etc/httpd/conf.d/codex_svnroot.conf

### SELinux specific
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
grep -i -q '^SELINUX=disabled' /etc/selinux/config
if [ $? -eq 0 ] || [ ! -e chcon ] || [ ! -e "/etc/selinux/config" ] ; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi
if [ $SELINUX_ENABLED ]; then
    chcon -R -h $SELINUX_CONTEXT /usr/share/codex
    chcon -R -h $SELINUX_CONTEXT /etc/codex
    chcon -R -h $SELINUX_CONTEXT /var/lib/codex
    chcon -R -h $SELINUX_CONTEXT /home/groups
    chcon -R -h $SELINUX_CONTEXT /home/codexadm
    chcon -h $SELINUX_CONTEXT /svnroot
    chcon -h $SELINUX_CONTEXT /cvsroot
fi


%install
#Clean build environment
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT
### Copy CodeX source
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/codex  
cp -pr * ${RPM_BUILD_ROOT}%{_datadir}/codex




%post
### Enable right
chown -R codexadm.codexadm %{_datadir}/codex
find %{_datadir}/codex -type f -exec chmod u+rw,g+rw,o-w+r {} \;
find %{_datadir}/codex -type d -exec chmod 775 {} \;

make_backup() {
    # $1: file name, $2: extension for old file (optional)
    file="$1"
    ext="$2"
    if [ -z $ext ]; then
	ext="nocodex"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && cp "$file" "$backup_file"
}

cp /etc/httpd/conf/httpd.conf /etc/httpd/conf/httpd.conf.nocodex

for f in /etc/httpd/conf/httpd.conf /var/named/chroot/var/named/codex_full.zone \
/etc/httpd/conf/ssl.conf \
/etc/httpd/conf.d/php.conf /etc/httpd/conf.d/subversion.conf \
/etc/codex/conf/local.inc /etc/codex/conf/database.inc /etc/httpd/conf.d/codex_aliases.conf; do
	
	fn=`basename $f`
	cp -f /usr/share/codex/src/etc/$fn.dist $f
    	chown codexadm.codexadm $f
    	chmod 640 $f
done


chown root:named /var/named/chroot/var/named/codex_full.zone
if [ -f "/var/named/chroot/etc/named.conf" ]; then
   chgrp named /var/named/chroot/etc/named.conf
fi


### SELinux specific
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
grep -i -q '^SELINUX=disabled' /etc/selinux/config
if [ $? -eq 0 ] || [ ! -e chcon ] || [ ! -e "/etc/selinux/config" ] ; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi
if [ $SELINUX_ENABLED ]; then
    chcon -h system_u:object_r:named_zone_t /var/named/chroot/var/named/codex_full.zone
    if [ -f "/var/named/chroot/etc/named.conf" ]; then
        chcon -h system_u:object_r:named_conf_t /var/named/chroot/etc/named.conf
    fi
fi


#chown -R codexadm.codexadm /etc/codex/documentation
#chown -R codexadm.codexadm /usr/share/codex/documentation
cp /usr/share/codex/src/utils/backup_job /usr/lib/codex/bin
chown root.root /usr/lib/codex/bin/backup_job
chmod 740 /usr/lib/codex/bin/backup_job
cp /usr/share/codex/src/utils/svn/backup_subversion.sh /usr/lib/codex/bin
chown root.root /usr/lib/codex/bin/backup_subversion.sh
chmod 740 /usr/lib/codex/bin/backup_subversion.sh
# needed by newparse.pl
touch /etc/httpd/conf/htpasswd
chmod 644 /etc/httpd/conf/htpasswd


### replace string patterns in local.inc
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_default_domain% %sys_default_domain
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_ldap_server% %sys_ldap_server
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_org_name% %sys_org_name
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_long_org_name% %sys_long_org_name
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_fullname% %sys_fullname
sh %{_datadir}/codex/sed.sh /etc/codex/conf/local.inc sys_win_domain% %sys_win_domain
if [ "%disable_subdomains" = "y" ]; then
  %substitute '/etc/codex/conf/local.inc' 'sys_lists_host = "lists.' 'sys_lists_host = "'
  %substitute '/etc/codex/conf/local.inc' 'sys_disable_subdomains = 0' 'sys_disable_subdomains = 1'
fi

### replace string patterns in database.inc
sh %{_datadir}/codex/sed.sh /etc/codex/conf/database.inc sys_dbpasswd% %codexadm_passwd

### replace string patterns in httpd.conf
sh %{_datadir}/codex/sed.sh /etc/httpd/conf/httpd.conf sys_default_domain% %sys_default_domain
sh %{_datadir}/codex/sed.sh /etc/httpd/conf/httpd.conf sys_ip_address% %sys_ip_address

if [ "%disable_subdomains" != "y" ]; then
  # replace string patterns in codex_full.zone
  sys_shortname=`echo %sys_fullname | perl -pe 's/\.(.*)//'`
  dns_serial=`date +%Y%m%d`01
  sh %{_datadir}/codex/sed.sh /var/named/chroot/var/named/codex_full.zone sys_default_domain% %sys_default_domain
  sh %{_datadir}/codex/sed.sh /var/named/chroot/var/named/codex_full.zone sys_fullname% %sys_fullname
  sh %{_datadir}/codex/sed.sh /var/named/chroot/var/named/codex_full.zone sys_ip_address% %sys_ip_address
  sh %{_datadir}/codex/sed.sh /var/named/chroot/var/named/codex_full.zone sys_shortname% $sys_shortname
  sh %{_datadir}/codex/sed.sh /var/named/chroot/var/named/codex_full.zonec dns_serial% $dns_serial
fi


#### Creating MySQL conf file
cat <<'EOF' >/etc/my.cnf
[client]
default-character-set=utf8

[mysqld]
default-character-set=utf8
log-bin=codex-bin
skip-bdb
set-variable = max_allowed_packet=128M
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
# Default to using old password format for compatibility with mysql 3.x
# clients (those using the mysqlclient10 compatibility package).
old_passwords=1

[mysql.server]
user=mysql
basedir=/var/lib

[mysqld_safe]
err-log=/var/log/mysqld.log
pid-file=/var/run/mysqld/mysqld.pid

EOF

# Start database
service mysqld start 2>/dev/null 1>&2
mysql -u root --password='%rt_passwd' -e "create database codex DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci"
cat <<EOF | mysql -u root mysql --password='%rt_passwd'
GRANT ALL PRIVILEGES on *.* to codexadm@localhost identified by '%codexadm_passwd' WITH GRANT OPTION;
GRANT ALL PRIVILEGES on *.* to root@localhost identified by '%rt_passwd';
FLUSH PRIVILEGES;
EOF


mysql -u codexadm codex --password='%codexadm_passwd' < /usr/share/codex/src/db/mysql/database_structure.sql   # create the DB
cp /usr/share/codex/src/db/mysql/database_initvalues.sql /tmp/database_initvalues.sql
%substitute "/tmp/database_initvalues.sql" "_DOMAIN_NAME_" "%sys_default_domain"
mysql -u codexadm codex --password='%codexadm_passwd' < /tmp/database_initvalues.sql  # populate with init values.
rm -f /tmp/database_initvalues.sql

##############################################
# Create the custom default page for the project Web sites
#
def_page=/etc/codex/site-content/en_US/others/default_page.php
yn="y"
[ -f "$def_page" ] && read -p "Custom Default Project Home page already exists. Overwrite? [y|n]:" yn
if [ "$yn" = "y" ]; then
    mkdir -p /etc/codex/site-content/en_US/others
    chown codexadm.codexadm /etc/codex/site-content/en_US/others
    cp /usr/share/codex/site-content/en_US/others/default_page.php /etc/codex/site-content/en_US/others/default_page.php
fi

if [ "%disable_subdomains" = "y" ]; then
  mysql -u codexadm codex --password='%codexadm_passwd' -e "UPDATE service SET link = '/www/$projectname' WHERE short_name = 'homepage'"
fi



##############################################
# Create CodeX profile script
#

# customize the global profile 
grep profile_codex /etc/profile 1>/dev/null
[ $? -ne 0 ] && \
    cat <<'EOF' >>/etc/profile
# Now the Part specific to CodeX users
#
if [ `id -u` -gt 20000 -a `id -u` -lt 50000 ]; then
        . /etc/profile_codex
fi
EOF

cat <<'EOF' >/etc/profile_codex
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

cat <<EOM
Corresponding CVS and Subversion repositories are in /cvsroot and /svnroot
                                                                               
             *** IMPORTANT REMARK ***
The CodeX server hosts very valuable yet publicly available
data. Therefore we recommend that you keep working only in
the directories listed above for which you have full rights
and responsibilities.
                                                                               
EOM
EOF

##############################################
# Make sure all major services are on
#
if [ "%disable_subdomains" != "y" ]; then
  chkconfig named on
fi
chkconfig sshd on
chkconfig httpd on
chkconfig mysqld on
chkconfig cvs on
chkconfig mailman on
chkconfig munin-node on
chkconfig smb on
chkconfig vsftpd on
chkconfig openfire on

##############################################
# Set SELinux contexts and load policies
#
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
grep -i -q '^SELINUX=disabled' /etc/selinux/config
if [ $? -eq 0 ] || [ ! -e chcon ] || [ ! -e "/etc/selinux/config" ] ; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi
if [ $SELINUX_ENABLED ]; then
    perl /usr/share/codex/src/utils/fix_selinux_contexts.pl
fi







%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc
%{_datadir}/codex


%changelog
