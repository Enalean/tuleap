# Define variables
%define PKG_NAME @@PKG_NAME@@

Summary: Tuleap dependencies
Name: %{PKG_NAME}-all-deps
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: README.all-deps
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

# Python
Requires: python, python-devel
# FTP
Requires: vsftpd
# Mysql
Requires: mysql-server
# Bind
Requires: bind, bind-chroot, caching-nameserver
# SELinux
Requires: policycoreutils, coreutils, selinux-policy, selinux-policy-targeted, libselinux
# Munin
#Requires: munin = 1.2.5-1.codendi, munin-node = 1.2.5-1.codendi

##
## Inherited from old install script, possibly useless of just dependencies...
##
Requires: ntp
Requires: sysstat
# CLI (build only?)
Requires: zip, unzip
# Java (for documentation building ?)
Requires: java-1.6.0-openjdk, jpackage-utils, giflib

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

