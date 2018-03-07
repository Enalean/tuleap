Name:		tuleap-plugin-velocity
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Velocity chart

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	php(language) >= 5.6, tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}

%description
%{summary}.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/velocity
%{__cp} -ar db include site-content templates README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/velocity

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/velocity
