%{!?APP_NAME: %define APP_NAME tuleap}

Summary: Test and tracability plugin for Tuleap (Web front end)
Name: tuleap-plugin-testing-frontend
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: Enalean
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@enalean.com>

AutoReqProv: no

Requires: tuleap-plugin-testing-backend

%description
Test and tracability plugin

# 
# Package setup
%prep
%setup -q

#
# Build
%build
# Nothing to do

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/%{APP_NAME}/plugins/testing/www/scripts/angular
%{__cp} -ar www/scripts/angular/bin $RPM_BUILD_ROOT/%{_datadir}/%{APP_NAME}/plugins/testing/www/scripts/angular

%pre
if [ "$1" -eq "1" ]; then
    # Install
    true
else
    # Update
    true
fi

%clean
%{__rm} -rf $RPM_BUILD_ROOT


#
#
#
%files
%defattr(-,root,root,-)
%{_datadir}/%{APP_NAME}/plugins/testing/www/scripts/angular

%changelog
* Mon Sep 15 2014 Manuel VACELET <manuel.vacelet@enalean.com> -
- First package
