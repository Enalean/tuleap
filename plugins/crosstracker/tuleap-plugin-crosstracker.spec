Name:		tuleap-plugin-crosstracker
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Cross tracker search widget

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	php(language) >= 5.3, tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/crosstracker
%{__cp} -ar bin db include site-content templates README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/crosstracker
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/crosstracker

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/crosstracker
%{_datadir}/tuleap/src/www/assets/crosstracker
