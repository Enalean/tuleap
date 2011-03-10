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
Requires: ntp
Requires: sysstat, curl, aspell
Requires: java-1.6.0-openjdk, jpackage-utils, giflib
Requires: enscript
# Apache
Requires: httpd, mod_ssl, openssl
# Perl
Requires: perl, perl-DBI, perl-DBD-MySQL, perl-suidperl, perl-URI, perl-HTML-Tagset, perl-HTML-Parser, perl-libwww-perl, perl-DateManip
# Python
Requires: python, python-devel
# FTP
Requires: vsftpd
# PHP
Requires: php, php-mysql, php-xml, php-mbstring, php-gd, php-soap, php-pear, gd
# Mysql
Requires: mysql, MySQL-python, mysql-server
# Bind
Requires: bind, bind-chroot, caching-nameserver
# jpgraph
Requires: dejavu-lgc-fonts
Requires: jpgraph = 2.3.4-0.codendi
# SELinux
Requires: policycoreutils, coreutils, selinux-policy, selinux-policy-targeted, libselinux
# CLI (build only?)
Requires: zip, unzip
# CVS
Requires: xinetd, rcs, cvs = 1.11.22-5.codendi
# SVN
#Conflicts: cadaver
#Requires: subversion, subversion-tools, mod_dav_svn, subversion-perl, subversion-python
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql, mod_auth_mysql, nss, nscd
# cvsgraph
Requires: cvsgraph
# highlight
Requires: highlight
# APC
Requires: php-pecl-apc
# Munin
Requires: munin = 1.2.5-1.codendi, munin-node = 1.2.5-1.codendi
# HTML Purifier
Requires: htmlpurifier


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

