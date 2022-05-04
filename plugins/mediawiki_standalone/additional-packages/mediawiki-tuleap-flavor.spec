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

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%prep
%setup -qc tuleap-mediawiki-flavor

%build

%install
mkdir -p %{buildroot}%{_datadir}/%{name}
cp -a * %{buildroot}%{_datadir}/%{name}

%clean

%files
%defattr(-,root,root)
%{_datadir}/%{name}
