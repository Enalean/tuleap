Name:		tuleap-plugin-project-ownership
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Project ownership

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}

Obsoletes: tuleap-plugin-project-certification

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/project_ownership
%{__cp} -ar db include site-content templates themes vendor VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/project_ownership
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/project_ownership
%{__cp} -ar assets/* $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/project_ownership

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/project_ownership
%{_datadir}/tuleap/src/www/assets/project_ownership
