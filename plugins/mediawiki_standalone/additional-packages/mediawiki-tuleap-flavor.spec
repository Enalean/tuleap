%define _buildhost tuleap-builder
%define _source_payload w9T8.xzdio
%define _binary_payload w9T8.xzdio

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
Requires: php82-php-cli php82-php-xml php82-php-intl php82-php-json php82-php-ldap php82-php-mbstring php82-php-mysqlnd php82-php-process php82-php-pdo php82-php-opcache php82-php-fpm php82-php-cli php82-php-sodium
# Used by the mdpf extension
Requires: php82-php-gd

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
