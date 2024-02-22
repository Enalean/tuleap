%define _buildhost tuleap-builder
%define _source_payload w9T8.xzdio
%define _binary_payload w9T8.xzdio
%define APP_NAME tuleap-documentation
%define APP_DIR %{_datadir}/%{APP_NAME}

Name:            tuleap-documentation
Version:         %{tuleap_version}
Release:         1%{?dist}
Summary:         The documentation of Tuleap

Group:           Development/Tools
License:         GPLv2+
URL:             https://tuleap.net
Source0:         tuleap-documentation.tar.gz
BuildRoot:       %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:       noarch

%description
tuleap-documentation is the official documentation of the Tuleap Platform, including
installation guide, administration guide, user guide and programmer guide.

%prep
%setup -q -c tuleap-documentation-src

%install
%{__rm} -rf $RPM_BUILD_ROOT

# deploying documentation
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/
%{__cp} -ar en $RPM_BUILD_ROOT/%{APP_DIR}/
%{__cp} -ar fr $RPM_BUILD_ROOT/%{APP_DIR}/

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{APP_DIR}/
