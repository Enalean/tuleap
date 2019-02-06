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

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline
%{__cp} -ar db include site-content templates vendor $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/baseline
