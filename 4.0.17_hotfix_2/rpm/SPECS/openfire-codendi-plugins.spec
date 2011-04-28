Summary: Openfire codendi plugins
Name: openfire-codendi-plugins
Version: 3.6.4
Release: 1
License: GPL
URL: http://www.igniterealtime.org/
Source0: helga.jar
Source1: presence.jar
Source2: subscription.jar
Source3: monitoring.jar
Group: Network Servers
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch
Requires: openfire

%description
A set of openfire plugins requested for integration with Codendi

%prep
# -n: set the name of the directory
# -T: to disable automatic unpacking of Source0
# -c: to create the directory
%setup -q -c -T -n %{name}-%{version}

%build
# Nothing to do

%install
rm -rf $RPM_BUILD_ROOT
%{__install} -d $RPM_BUILD_ROOT/opt/openfire/plugins
%{__install} %{SOURCE0} $RPM_BUILD_ROOT/opt/openfire/plugins
%{__install} %{SOURCE1} $RPM_BUILD_ROOT/opt/openfire/plugins
%{__install} %{SOURCE2} $RPM_BUILD_ROOT/opt/openfire/plugins
%{__install} %{SOURCE3} $RPM_BUILD_ROOT/opt/openfire/plugins

%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(00644,daemon,daemon,-)
/opt/openfire/plugins/helga.jar
/opt/openfire/plugins/presence.jar
/opt/openfire/plugins/subscription.jar
/opt/openfire/plugins/monitoring.jar

%changelog
* Tue Aug 17 2010 Manuel VACELET <manuel.vacelet@st.com> - 3.6.4-1
- Initial build.

