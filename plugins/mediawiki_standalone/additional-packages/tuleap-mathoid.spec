%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:          tuleap-mathoid
Version:       %{tuleap_version}
Release:       1%{?dist}
Summary:       Mathoid for MediaWiki Tuleap flavor
Group:         Development/Tools
License:       GPLv2

Requires: tuleap-node

Source0: tuleap-mathoid.tar

AutoReqProv: no

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%prep
%setup -qc tuleap-mathoid

%install
mkdir -p %{buildroot}/usr/share/tuleap-mathoid/
cp -a mathoid/lib/node_modules/mathoid/ %{buildroot}/usr/share/tuleap-mathoid/src
cp -a config.yaml %{buildroot}/usr/share/tuleap-mathoid/

%clean

%files
%defattr(-,root,root)
/usr/share/tuleap-mathoid/*
