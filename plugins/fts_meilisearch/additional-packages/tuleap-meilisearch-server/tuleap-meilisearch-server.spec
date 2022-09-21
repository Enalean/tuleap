%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

%define __os_install_post %{nil}

Name:		  tuleap-meilisearch-server
Version:	%{tuleap_version}
Release:	1%{?nixpkgs_epoch}%{?dist}
Summary:	Meilisearch (full-text search) server for Tuleap

License:	MIT
Source0:	tuleap-meilisearch

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%prep
%setup -q -c -T
cp %{SOURCE0} .

%build

%install
mkdir -p %{buildroot}%{_bindir}/
cp tuleap-meilisearch %{buildroot}%{_bindir}/

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-meilisearch
