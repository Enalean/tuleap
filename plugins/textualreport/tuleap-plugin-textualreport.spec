Name:		tuleap-plugin-textualreport
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Textual Report

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/textualreport
%{__cp} -ar include site-content templates README.mkd VERSION vendor $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/textualreport

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/textualreport
