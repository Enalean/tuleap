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
Source2:  tuleap-meilisearch-config-change.service
Source3:  tuleap-meilisearch-config-change.path

Requires: tuleap, tuleap-plugin-fts-meilisearch

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%prep
%setup -q -c -T
cp %{SOURCE0} .
cp %{SOURCE1} .
cp %{SOURCE2} .
cp %{SOURCE3} .

%build

%install
mkdir -p %{buildroot}/var/lib/tuleap/fts_meilisearch_server/
mkdir -p %{buildroot}%{_bindir}/
cp tuleap-meilisearch %{buildroot}%{_bindir}/
mkdir -p %{buildroot}%{_unitdir}/
install --mode=644 tuleap-meilisearch.service %{buildroot}%{_unitdir}/
install --mode=644 tuleap-meilisearch-config-change.service %{buildroot}%{_unitdir}/
install --mode=644 tuleap-meilisearch-config-change.path %{buildroot}%{_unitdir}/

%preun
if [ $1 -eq "0" ]; then
    /usr/bin/systemctl stop tuleap-meilisearch.service ||:
    /usr/bin/systemctl stop tuleap-meilisearch-config-change.path ||:

    /usr/bin/systemctl disable tuleap-meilisearch.service ||:
    /usr/bin/systemctl disable tuleap-meilisearch-config-change.path ||:
fi

%post
/usr/bin/systemctl daemon-reload &> /dev/null || :
if [ $1 -eq "1" ]; then
    /usr/bin/systemctl enable tuleap-meilisearch.service ||:
    /usr/bin/systemctl enable --now tuleap-meilisearch-config-change.path ||:
fi

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-meilisearch
%{_unitdir}/tuleap-meilisearch.service
%{_unitdir}/tuleap-meilisearch-config-change.service
%{_unitdir}/tuleap-meilisearch-config-change.path
%attr(-,codendiadm,codendiadm) /var/lib/tuleap/fts_meilisearch_server
