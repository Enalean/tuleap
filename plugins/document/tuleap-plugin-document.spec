Name:		tuleap-plugin-document
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Document UI

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}
%if ! 0%{?el6}
Requires: tuleap-plugin-docman
%endif

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/document
%{__cp} -ar include site-content templates vendor README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/document
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/document

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/document
%{_datadir}/tuleap/src/www/assets/document
