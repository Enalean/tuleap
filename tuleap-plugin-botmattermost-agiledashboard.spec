Name:		tuleap-plugin-botmattermost-agiledashboard
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Bot Mattermost AgileDashboard - Stand up summary

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap-plugin-agiledashboard, tuleap-plugin-botmattermost


%description
Bot Mattermost AgileDashboard - Stand up summary

%prep
%setup -q


%build
npm install
npm run build
find www/themes -name '*.scss' | xargs rm -f

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard
%{__cp} -ar db include site-content template README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard

# www
%{__mkdir} -p $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard/www
%{__cp} -ar www/index.php www/themes www/scripts $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard/www


%pre

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/botmattermost_agiledashboard


%changelog
* Tue Dec 20 2016 Humbert MOREAUX <humbert.moreaux@enalean.com> -
- First package
