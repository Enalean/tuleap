# Define variables
%define PKG_NAME @@PKG_NAME@@

Summary: Tuleap meta package with all dependencies
Name: %{PKG_NAME}-all
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: README.all
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

Requires: @@PKG_NAME@@-install
Requires: @@PKG_NAME@@-core-mailman
Requires: @@PKG_NAME@@-core-subversion
Requires: @@PKG_NAME@@-plugin-forumml
Requires: @@PKG_NAME@@-plugin-git
Requires: @@PKG_NAME@@-plugin-ldap
Requires: @@PKG_NAME@@-plugin-hudson
Requires: @@PKG_NAME@@-plugin-tracker
Requires: @@PKG_NAME@@-plugin-graphontrackers
Requires: @@PKG_NAME@@-plugin-agiledashboard
Requires: @@PKG_NAME@@-theme-flamingparrot
Requires: @@PKG_NAME@@-theme-burningparrot
Requires: @@PKG_NAME@@-customization-default

Requires: vsftpd
Requires: mysql-server

%description
This package install all @@PKG_NAME@@ component to have a large view of
what the platform propose.

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
* Tue Aug 23 2011 Manuel VACELET <manuel.vacelet@enalean.com> -
- Initial build.

