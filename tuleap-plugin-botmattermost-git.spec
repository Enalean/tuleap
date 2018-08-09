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
npm install
npm run build
find www/themes -name '*.scss' | xargs rm -f

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git
%{__cp} -ar db include site-content template README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git

# www
%{__mkdir} -p $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git/www
%{__cp} -ar www/index.php www/themes www/scripts $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_git/www


%pre

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/botmattermost_git


%changelog
* Tue Dec 20 2016 Humbert MOREAUX <humbert.moreaux@enalean.com> -
- First package
