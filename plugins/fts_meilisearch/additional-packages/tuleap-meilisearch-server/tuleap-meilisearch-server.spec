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
Source1:  tuleap-meilisearch.service

Requires: tuleap, tuleap-plugin-fts-meilisearch

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%prep
%setup -q -c -T
cp %{SOURCE0} .
cp %{SOURCE1} .

%build

%install
mkdir -p %{buildroot}/var/lib/tuleap/fts_meilisearch_server/
mkdir -p %{buildroot}%{_bindir}/
cp tuleap-meilisearch %{buildroot}%{_bindir}/
mkdir -p %{buildroot}%{_unitdir}/
cp tuleap-meilisearch.service %{buildroot}%{_unitdir}/

%preun
if [ $1 -eq "0" ]; then
    /usr/bin/systemctl stop tuleap-meilisearch.service ||:

    /usr/bin/systemctl disable tuleap-meilisearch.service ||:
fi

%post
/usr/bin/systemctl daemon-reload &> /dev/null || :
if [ $1 -eq "1" ]; then
    /usr/bin/systemctl enable tuleap-meilisearch.service ||:
fi

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-meilisearch
%{_unitdir}/tuleap-meilisearch.service
%attr(-,codendiadm,codendiadm) /var/lib/tuleap/fts_meilisearch_server
