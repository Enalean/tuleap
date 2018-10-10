Summary: Test Management plugin for Tuleap
Name: tuleap-plugin-testmanagement
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPLv2
Group: Development/Tools
URL: https://enalean.com
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

Provides: tuleap-plugin-testing-backend = %{version}-%{release}, tuleap-plugin-trafficlights-backend = %{version}-%{release}, tuleap-plugin-trafficlights-frontend = %{version}-%{release}
Obsoletes: tuleap-plugin-testing-backend <= 0.3, tuleap-plugin-trafficlights-backend <= 0.9.4, tuleap-plugin-trafficlights-frontend <= 0.9.4

AutoReqProv: no

Requires: tuleap >= 9.11, tuleap-plugin-tracker, tuleap-plugin-agiledashboard

%description
%{summary}.

%prep
%setup -q

%build
find www/themes -name '*.scss' | xargs rm -f

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/testmanagement
%{__cp} -ar include site-content templates db glyphs resources README VERSION www $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/testmanagement

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/testmanagement
