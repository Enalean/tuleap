%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d
%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:		tuleap-plugin-enalean-licensemanager
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Manage usage of license for Tuleap

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap >= 9.8

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/enalean_licensemanager
%{__cp} -ar include vendor site-content etc README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/enalean_licensemanager

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/enalean_licensemanager
