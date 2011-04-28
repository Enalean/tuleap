%define codendi_plugin_dir /usr/share/codendi/plugins
%define plugin_name eclipse

Summary: Codendi Eclipse Plugin
Name: codendi-eclipse
Version: 1.1.0
Release: 0
License: GPL
URL: http://www.codendi.com
Group: Development/Tools

Source: http://www.codendi.com/releases/%{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch

%description
The Codendi Eclipse plugin let you work with your trackers and artifacts within the Eclipse IDE environment.

%prep
%setup -n %{name}-%{version}

%build

%install
%{__rm} -rf %{buildroot}
%{__install} -d -m0755 %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}
%{__cp} -ar * %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, codendiadm, codendiadm, 0755)
%{codendi_plugin_dir}/%{plugin_name}

%changelog
* Fri Jul 4 2008 Nicolas GUERIN <nicolas.guerin@xrce.xerox.com> - 1.1.0
- Initial package.
