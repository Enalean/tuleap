Name:		tuleap-plugin-dynamic-credentials
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@
BuildArch:	noarch
Summary:	Dynamic credentials generation

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap = @@TULEAP_VERSION@@-@@RELEASE@@

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials
%{__cp} -ar . $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/

%{__rm} -r $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/phpunit/
%{__rm} -r $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/tests/
%{__rm} -r $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/rpm/
%{__rm} $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/Makefile
%{__rm} $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/build-rpm.sh
%{__rm} $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/tuleap-plugin-dynamic-credentials.spec
%{__rm} $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/dynamic_credentials/composer.json

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/dynamic_credentials/db/
%{_datadir}/tuleap/plugins/dynamic_credentials/etc/
%{_datadir}/tuleap/plugins/dynamic_credentials/include/
%{_datadir}/tuleap/plugins/dynamic_credentials/site-content/
%{_datadir}/tuleap/plugins/dynamic_credentials/vendor/
%{_datadir}/tuleap/plugins/dynamic_credentials/README.md
%{_datadir}/tuleap/plugins/dynamic_credentials/VERSION
