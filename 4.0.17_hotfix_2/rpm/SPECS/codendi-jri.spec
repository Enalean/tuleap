%define codendi_plugin_dir /usr/share/codendi/plugins
%define plugin_name codendijri

Summary: Codendi Java Runtime Environment
Name: codendi-jri
Version: 1.1.0
Release: 0
License: GPL
URL: http://www.codendi.com
Group: Development/Tools

Source: http://www.codendi.com/releases/%{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch

%description
The Codendi JRI (Java Remote Interface) is the Java API for Codendi.
For now, it allows you to work with trackers.

%prep
%setup -n %{name}-%{version}

%build

%install
%{__rm} -rf %{buildroot}
%{__install} -d -m0755 %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}
%{__cp} -ar * %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}
cd %{buildroot}%{codendi_plugin_dir}/%{plugin_name}/www/jars; /bin/ln -s com.xerox.xrce.codendi.jri_%{version}.jar com.xerox.xrce.codendi.jri.jar; cd -

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, codendiadm, codendiadm, 0755)
%{codendi_plugin_dir}/%{plugin_name}

%changelog
* Fri Jul 4 2008 Nicolas GUERIN <nicolas.guerin@xrce.xerox.com> - 1.1.0
- Initial package.
