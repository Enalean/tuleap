Name:		tuleap-plugin-mytuleap-contact-support
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	myTuleap Contact support

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap >= 9.11


%description
%{summary}.

%prep
%setup -q


%build
find www/themes -type f -name '*.scss' -exec rm -f {} \;

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/mytuleap_contact_support
%{__cp} -ar include site-content templates README.md VERSION www $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/mytuleap_contact_support

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/mytuleap_contact_support
