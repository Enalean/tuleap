Name:		tuleap-plugin-prometheus-metrics
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Prometheus metrics end point

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

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/prometheus_metrics
%{__cp} -ar include site-content vendor README.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/prometheus_metrics

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/prometheus_metrics
