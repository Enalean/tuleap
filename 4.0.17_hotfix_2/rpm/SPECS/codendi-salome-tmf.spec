%define codendi_plugin_dir /usr/share/codendi/plugins
%define plugin_name salome

Summary: Codendi Salome-TMF Client
Name: codendi-salome-tmf
Version: 1.0
Release: 0
License: GPL
URL: http://www.codendi.com
Group: Development/Tools

Source: http://www.codendi.com/releases/%{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch
BuildRequires:  %{__perl}

%description
Salome TMF is a test management platform. This package is a codendi-specific version
that integrates nicely with Codendi.

%prep
%setup -n %{name}-%{version}

%build

%install
%{__rm} -rf %{buildroot}
%{__install} -d -m0755 %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}
%{__cp} -ar * %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}
# Remove zipped plugins if any
%{__rm} -rf %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}/www/webapps/jdbc_client/plugins/*.zip
# Correct plugins JNLP (remove unwanted tags)
%{__perl} -p -i.orig -e 's/.*codebase.*//'  %{buildroot}/%{codendi_plugin_dir}/%{plugin_name}/www/webapps/jdbc_client/plugins/*/*.jnlp 


%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, codendiadm, codendiadm, 0755)
%{codendi_plugin_dir}/%{plugin_name}

%changelog
* Mon Jul 4 2008 Nicolas GUERIN <nicolas.guerin@xrce.xerox.com> - 1.0
- Initial package.
