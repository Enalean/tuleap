%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:          mediawiki-tuleap-flavor
Version:       %{tuleap_version}
Release:       1.%{mw_version}%{?dist}
Summary:       MediaWiki Tuleap flavor
Group:         Development/Tools
License:       GPLv2

Source0: mediawiki-tuleap-flavor.tar

BuildArch:      noarch

AutoReqProv: no

Requires: tuleap-mathoid = %{tuleap_version}
Requires: php74-php-xml php74-php-intl php74-php-json php74-php-ldap php74-php-mbstring php74-php-mysqlnd php74-php-process php74-php-pdo php74-php-opcache php74-php-fpm php74-php-cli php74-php-sodium
Requires: php81-php-xml php81-php-intl php81-php-json php81-php-ldap php81-php-mbstring php81-php-mysqlnd php81-php-process php81-php-pdo php81-php-opcache php81-php-fpm php81-php-cli php81-php-sodium
# Used by the mdpf extension
Requires: php74-php-gd
Requires: php81-php-gd

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%prep
%setup -qc tuleap-mediawiki-flavor

%build

%install
mkdir -p %{buildroot}%{_datadir}/%{name}/1.35
cp -a * %{buildroot}%{_datadir}/%{name}/1.35
ln -s %{_datadir}/%{name}/1.35 %{buildroot}%{_datadir}/%{name}/current
ln -s /etc/tuleap/plugins/mediawiki_standalone/LocalSettings.php %{buildroot}%{_datadir}/%{name}/1.35/LocalSettings.php
ln -s /etc/tuleap/plugins/mediawiki_standalone/LocalSettings.local.php %{buildroot}%{_datadir}/%{name}/1.35/LocalSettings.local.php

%clean

%files
%defattr(-,root,root)
%{_datadir}/%{name}/1.35
%{_datadir}/%{name}/current
