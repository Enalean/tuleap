%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

%define __os_install_post %{nil}

Name:          tuleap-mathoid
Version:       %{tuleap_version}
Release:       1%{?dist}
Summary:       Mathoid for MediaWiki Tuleap flavor
Group:         Development/Tools
License:       GPLv2

Source0: tuleap-mathoid.tar

AutoReqProv: no

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%prep
%setup -qc tuleap-mathoid

%install
mkdir -p %{buildroot}/usr/lib/tuleap/mathoid/bin/
cp -a mathoid-cli %{buildroot}/usr/lib/tuleap/mathoid/bin/
mkdir -p %{buildroot}/usr/share/tuleap-mathoid/
cp -a config.yaml %{buildroot}/usr/share/tuleap-mathoid/

%clean

%files
%defattr(-,root,root)
/usr/lib/tuleap/mathoid/*
/usr/share/tuleap-mathoid/*
