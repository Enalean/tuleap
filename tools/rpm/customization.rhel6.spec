# Platform variables
%define CODENDI_PLATFORM @@PLATFORM@@

# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME tuleap
%define APP_DIR %{_datadir}/%{APP_NAME}

Summary: Tuleap customization for @@PLATFORM@@ platform
Name: %{PKG_NAME}-customization-@@PLATFORM@@
Provides: %{PKG_NAME}-customization
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{PKG_NAME}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

%description
This package provides themes modifications
that customize the Tuleap application for "@@PLATFORM@@" platform.

%prep
%setup -q -n %{PKG_NAME}-%{version}

%install
%{__rm} -rf $RPM_BUILD_ROOT

# Custom logo
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/images

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)

#%doc
#%config

%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

