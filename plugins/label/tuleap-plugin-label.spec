Name:		tuleap-plugin-label
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Label widget

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

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/label
%{__cp} -ar vendor db include site-content templates README.mkd VERSION www $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/label
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/label

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/label
%{_datadir}/tuleap/src/www/assets/label
