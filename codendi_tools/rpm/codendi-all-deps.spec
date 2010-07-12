# Define variables
%define PKG_NAME @@PKG_NAME@@

Summary: Codendi dependencies
Name: %{PKG_NAME}-all-deps
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: README.all-deps
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

Requires: openssh-server, openssh, openssh-clients
Requires: httpd, mod_ssl, vsftpd
Requires: openssl, openldap, perl, perl-DBI, perl-DBD-MySQL, gd
Requires: bind, bind-chroot, caching-nameserver, ntp, python, perl-suidperl
Requires: python-devel, rcs, perl-URI, perl-HTML-Tagset
Requires: perl-HTML-Parser, perl-libwww-perl, php, php-ldap, php-mysql, mysql-server
Requires: mysql, MySQL-python, php-mbstring, php-gd, php-soap, php-pear
Requires: perl-DateManip, sysstat, curl, aspell
Requires: java-1.6.0-openjdk, jpackage-utils, giflib
Requires: enscript, mod_auth_mysql, nss, nscd
# jabbex
Requires: php-xml
# jpgraph
Requires: dejavu-lgc-fonts
Requires: jpgraph = 2.3.4-0.codendi
# SELinux
Requires: policycoreutils, coreutils, selinux-policy, selinux-policy-targeted, libselinux
# CLI (build only?)
Requires: zip, unzip
# CVS
Requires: xinetd, cvs = 1.11.22-5.codendi
# SVN
Conflicts: cadaver
Requires: subversion, subversion-tools, mod_dav_svn, subversion-perl, subversion-python
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql
# cvsgraph
Requires: cvsgraph
# highlight
Requires: highlight
# phpMyAdmin
#Requires: phpmyadmin
# APC
Requires: php-pecl-apc
# Munin
Requires: munin = 1.2.5-1.codendi, munin-node = 1.2.5-1.codendi
# HTML Purifier
Requires: htmlpurifier
# ForgeUpgrade
Requires: forgeupgrade

# Openfire
#Requires: openfire
#echo "Installing OpenFire plugins"
# To be delivered as rpm too
#cd ${newest_rpm}
#$CP helga.jar presence.jar subscription.jar monitoring.jar /opt/openfire/plugins


%description
This package gather all dependencies of @@PKG_NAME@@.
Will be useless as soon as the application is properly cut into smaller
packages.

#%prep

#%build

%install
%{__rm} -rf $RPM_BUILD_ROOT
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_defaultdocdir}/%{name}
%{__cp} %{SOURCE0} $RPM_BUILD_ROOT/%{_defaultdocdir}/%{name}

#%post


%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc %{_defaultdocdir}/%{name}

%changelog
* Wed Jul  7 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.

