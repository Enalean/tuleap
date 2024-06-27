%define _buildhost tuleap-builder
%define _source_payload w22T8.zstdio
%define _binary_payload w22T8.zstdio

Name: tuleap-node
Version: %{node_version}
Release: 1%{?nixpkgs_epoch}%{?dist}
Summary: NodeJS used by Tuleap services

License: MIT
Source0: node

%description
%{summary}.

%prep
%setup -q -c -T
cp %{SOURCE0} .

%install
mkdir -p %{buildroot}%{_bindir}/
cp node %{buildroot}%{_bindir}/tuleap-node
chmod 755 %{buildroot}%{_bindir}/tuleap-node

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-node
