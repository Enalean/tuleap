%define _buildhost tuleap-builder
%define _source_payload w16T8.zstdio
%define _binary_payload w16T8.zstdio
%define _sysconfdir /etc
%define NAME tuleap-community-release

%define _rpmfilename %{NAME}.rpm

Name: tuleap-community-release
Version: %{VERSION}
Release: 1%{?dist}
Summary: Tuleap Community repository configuration
Group: Development/Tools
Requires: epel-release, remi-release
BuildArch: noarch
License: GPLv2+

Source0: RPM-GPG-KEY-Tuleap
Source1: tuleap.repo

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

%description
%{summary}.

%prep
%setup -q -c -T
install -pm 644 %{SOURCE0} .
install -pm 644 %{SOURCE1} .

%install
rm -rf $RPM_BUILD_ROOT
install -dm 755 $RPM_BUILD_ROOT%{_sysconfdir}/pki/rpm-gpg
install -Dpm 644 %{SOURCE0} $RPM_BUILD_ROOT%{_sysconfdir}/pki/rpm-gpg
install -dm 755 $RPM_BUILD_ROOT%{_sysconfdir}/yum.repos.d
install -pm 644 %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/yum.repos.d

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%config(noreplace) /etc/yum.repos.d/*
/etc/pki/rpm-gpg/*
