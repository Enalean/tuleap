%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d
%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Summary: Set and compare baselines
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
%{__cp} -ar db include frontend-assets site-content templates vendor VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline
echo @@VERSION@@-@@RELEASE@@ > $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/baseline/VERSION

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/baseline
