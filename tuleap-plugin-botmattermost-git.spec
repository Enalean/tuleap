%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d
%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:		tuleap-plugin-botmattermost-git
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Bot Mattermost git - Git Notification

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap-plugin-git, tuleap-plugin-botmattermost

%description
Bot Mattermost git - Git Notification

%prep
%setup -q


%build

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git
%{__cp} -ar db frontend-assets include site-content templates vendor README.mkd VERSION .use-front-controller $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git

%pre

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/botmattermost_git


%changelog
* Tue Dec 20 2016 Humbert MOREAUX <humbert.moreaux@enalean.com> -
- First package
