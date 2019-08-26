Name:		tuleap-plugin-taskboard
Version:	@@VERSION@@
Release:	@@TULEAP_VERSION@@_@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Taskboard

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap = @@TULEAP_VERSION@@-@@RELEASE@@%{?dist}
%if ! 0%{?el6}
Requires: tuleap-plugin-agiledashboard
%endif

%description
Keep track of things that need to get done in a task board.

%prep
%setup -q

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/taskboard
%{__cp} -ar include site-content vendor Readme.mkd VERSION $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/taskboard

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/taskboard
