%define _buildhost tuleap-builder
%define _source_payload w16T8.zstdio
%define _binary_payload w16T8.zstdio

%define base_name mediawiki-tuleap-flavor

Name:          %{base_name}-%{mw_tuleap_usage}
Version:       %{tuleap_version}
Release:       1.%{mw_version}%{?dist}
Summary:       MediaWiki Tuleap flavor
Group:         Development/Tools
License:       GPLv2

Source0: mediawiki-tuleap-flavor-%{mw_tuleap_usage}.tar

BuildArch:      noarch

AutoReqProv: no

Obsoletes: mediawiki-tuleap-flavor <= 999
Provides: mediawiki-tuleap-flavor

Requires: tuleap-mathoid = %{tuleap_version}
Requires: php84-php-cli php84-php-xml php84-php-intl php84-php-json php84-php-ldap php84-php-mbstring php84-php-mysqlnd php84-php-process php84-php-pdo php84-php-opcache php84-php-fpm php84-php-cli php84-php-sodium
# Used by the mdpf extension
Requires: php84-php-gd

%description
%{summary}.

%prep
%setup -qc tuleap-mediawiki-flavor

%build

%install
mkdir -p %{buildroot}%{_datadir}/%{base_name}/%{mw_tuleap_usage}
cp -a * %{buildroot}%{_datadir}/%{base_name}/%{mw_tuleap_usage}
ln -s /etc/tuleap/plugins/mediawiki_standalone/LocalSettings.php %{buildroot}%{_datadir}/%{base_name}/%{mw_tuleap_usage}/LocalSettings.php
ln -s /etc/tuleap/plugins/mediawiki_standalone/LocalSettings.local.php %{buildroot}%{_datadir}/%{base_name}/%{mw_tuleap_usage}/LocalSettings.local.php

%clean

%files
%defattr(-,root,root)
%{_datadir}/%{base_name}/%{mw_tuleap_usage}
