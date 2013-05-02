# Platform variables
%define CODENDI_PLATFORM @@PLATFORM@@
%define SYS_DEFAULT_DOMAIN @@SYS_DEFAULT_DOMAIN@@
%define SYS_HTTPS_HOST @@SYS_HTTPS_HOST@@

# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME codendi
%define APP_USER codendiadm
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
Source1: cli_ParametersLocal.dtd
Source2: user_guide_ParametersLocal.dtd
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildRequires: zip

%description
This package provides the documentation, CLI package and themes modifications
that customize the Tuleap application for "@@PLATFORM@@" platform.

%prep
%setup -q -n %{PKG_NAME}-%{version}

%build
cat > local.inc <<EOF
\$codendi_documentation_prefix = "$PWD/documentation";
\$codendi_dir = "$PWD";
\$tmp_dir = "$RPM_BUILD_ROOT";
\$sys_default_domain = "%{SYS_DEFAULT_DOMAIN}";
\$sys_https_host = "%{SYS_HTTPS_HOST}";
\$codendi_downloads_dir = "$PWD/downloads";
EOF

%{__cp} %{SOURCE1} cli_ParametersLocal.dtd
%{__cp} %{SOURCE2} user_guide_ParametersLocal.dtd

tools/rpm/build_release.sh

%install
%{__rm} -rf $RPM_BUILD_ROOT

# Doc: CLI
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli
%{__cp} -ar documentation/cli/html $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli
%{__cp} -ar documentation/cli/icons $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli

# Doc: Programmer guide
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/documentation/programmer_guide
%{__cp} -ar documentation/programmer_guide/html $RPM_BUILD_ROOT/%{APP_DIR}/documentation/programmer_guide
%{__cp} -ar documentation/programmer_guide/icons $RPM_BUILD_ROOT/%{APP_DIR}/documentation/programmer_guide
%{__cp} -ar documentation/programmer_guide/screenshots $RPM_BUILD_ROOT/%{APP_DIR}/documentation/programmer_guide
%{__cp} -ar documentation/programmer_guide/slides $RPM_BUILD_ROOT/%{APP_DIR}/documentation/programmer_guide

# Doc: User Guide
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/documentation/user_guide
%{__cp} -ar documentation/user_guide/html $RPM_BUILD_ROOT/%{APP_DIR}/documentation/user_guide
%{__cp} -ar documentation/user_guide/icons $RPM_BUILD_ROOT/%{APP_DIR}/documentation/user_guide
%{__cp} -ar documentation/user_guide/screenshots $RPM_BUILD_ROOT/%{APP_DIR}/documentation/user_guide
%{__cp} -ar documentation/user_guide/slides $RPM_BUILD_ROOT/%{APP_DIR}/documentation/user_guide

# Doc: Administration Guide
%{__cp} -ar documentation/administration_guide $RPM_BUILD_ROOT/%{APP_DIR}/documentation

# Doc: Installation Guide
%{__cp} -ar documentation/installation_guide $RPM_BUILD_ROOT/%{APP_DIR}/documentation

# CLI package
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/downloads
%{__cp} -ar downloads/* $RPM_BUILD_ROOT/%{APP_DIR}/downloads

# Custom logo
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/images

%post
/usr/bin/chcon -R root:object_r:httpd_sys_content_t %{APP_DIR}/documentation %{APP_DIR}/downloads || true


%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/documentation
%{APP_DIR}/downloads

#%doc
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

