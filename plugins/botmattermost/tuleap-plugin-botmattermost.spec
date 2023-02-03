%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d
%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:		tuleap-plugin-botmattermost
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Bot Mattermost management for Tuleap

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap >= 9.11

%description
Bot Mattermost management for Tuleap

%prep
%setup -q


%build

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost
%{__cp} -ar db frontend-assets include site-content templates vendor README.mkd VERSION .use-front-controller $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost

# logrotate
%{__mkdir} -p $RPM_BUILD_ROOT/%{_sysconfdir}/logrotate.d
%{__cp} -ar %{name}.conf $RPM_BUILD_ROOT/%{_sysconfdir}/logrotate.d

%pre

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/botmattermost
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}.conf


%changelog
* Tue Dec 20 2016 Humbert MOREAUX <humbert.moreaux@enalean.com> -
- First package
