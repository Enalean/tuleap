Summary: Test Management plugin for Tuleap
Name: tuleap-plugin-baseline
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPLv2
Group: Development/Tools
URL: https://enalean.com
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

AutoReqProv: no

Requires: tuleap-plugin-tracker

%description
%{summary}.

%prep
%setup -q

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline
%{__cp} -ar db include site-content templates vendor Readme.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/baseline
echo @@VERSION@@-@@RELEASE@@ > $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline/VERSION

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/baseline
%{_datadir}/tuleap/src/www/assets/baseline
