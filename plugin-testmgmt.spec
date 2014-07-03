Summary: Test and tracability plugin for Tuleap
Name: tuleap-plugin-testmgmt
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@enalean.com>

AutoReqProv: no

Requires: php(language) >= 5.3
Requires: tuleap, tuleap-plugin-tracker, tuleap-core-rest

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

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/testmgmt
%{__cp} -ar * $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/testmgmt

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
%{_datadir}/tuleap/plugins/testmgmt

%changelog
* Wed Jul 2 2014 Manuel VACELET <manuel.vacelet@enalean.com> -
- First package
