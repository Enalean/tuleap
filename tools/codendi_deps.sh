#!/bin/sh

echo "Enabling EPEL repository and installing RpmDevTools... "
rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-3.noarch.rpm
yum -y install rpmdevtools

echo "Installing Codex Dependencies... "
yum -y install openssh-server openssh openssh-clients \
   httpd  apr apr-util mod_ssl vsftpd \
   openssl openldap perl perl-DBI perl-DBD-MySQL gd \
   sendmail telnet bind bind-chroot caching-nameserver ntp samba python perl-suidperl \
   python-devel rcs sendmail-cf perl-URI perl-HTML-Tagset perl-Digest-SHA1 perl-Digest-HMAC perl-Socket6 \
   perl-HTML-Parser perl-libwww-perl php php-ldap php-mysql mysql-server \
   mysql MySQL-python php-mbstring php-gd php-soap php-xml \
   perl-DateManip sysstat curl aspell \
   gd-devel freetype-devel libpng-devel libjpeg-devel \
   libart_lgpl  \
   dump \
   dejavu-lgc-fonts \
   compat-libstdc++-33 \
   policycoreutils coreutils selinux-policy selinux-policy-targeted libselinux \
   zip unzip enscript xinetd
