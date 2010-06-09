# Platform variables
%define CODENDI_PLATFORM @@PLATFORM@@
%define SYS_DEFAULT_DOMAIN @@SYS_DEFAULT_DOMAIN@@
%define SYS_HTTPS_HOST @@SYS_HTTPS_HOST@@

# Define variables
%define CODENDI_DIR %{_datadir}/codendi

Summary: Codendi customization for @@PLATFORM@@ platform
Name: codendi_st_customization_@@PLATFORM@@
Provides: codendi_st_customization
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: codendi_st-%{version}.tar.gz
Source1: %{CODENDI_PLATFORM}_cli_ParametersLocal.dtd
Source2: %{CODENDI_PLATFORM}_user_guide_ParametersLocal.dtd
Source3: %{CODENDI_PLATFORM}_organization_logo.png
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root


%description
This package provides the documentation, CLI package and themes modifications
that customize the Codendi application for "@@PLATFORM@@" platform.

%prep
%setup -q -n codendi_st-%{version}

%build
cat > local.inc <<EOF
\$codendi_documentation_prefix = "$PWD/documentation";
\$codendi_dir = "$PWD";
\$tmp_dir = "$RPM_BUILD_ROOT";
\$sys_default_domain = "%{SYS_DEFAULT_DOMAIN}";
\$sys_https_host = "%{SYS_HTTPS_HOST}";
\$codendi_downloads_dir = "$PWD/downloads";
EOF

cp %{SOURCE1} cli_ParametersLocal.dtd
cp %{SOURCE2} user_guide_ParametersLocal.dtd

codendi_tools/rpm/build_release.sh

%install
rm -rf $RPM_BUILD_ROOT

# Doc: CLI
install -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/cli
cp -ar documentation/cli/html $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/cli
cp -ar documentation/cli/pdf $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/cli
cp -ar documentation/cli/icons $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/cli

# Doc: Programmer guide
install -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide
cp -ar documentation/programmer_guide/html $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide
cp -ar documentation/programmer_guide/pdf $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide
cp -ar documentation/programmer_guide/icons $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide
cp -ar documentation/programmer_guide/screenshots $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide
cp -ar documentation/programmer_guide/slides $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/programmer_guide

# Doc: User Guide
install -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide
cp -ar documentation/user_guide/html $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide
cp -ar documentation/user_guide/pdf $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide
cp -ar documentation/user_guide/icons $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide
cp -ar documentation/user_guide/screenshots $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide
cp -ar documentation/user_guide/slides $RPM_BUILD_ROOT/%{CODENDI_DIR}/documentation/user_guide

# CLI package
install -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}/downloads
cp -ar downloads/* $RPM_BUILD_ROOT/%{CODENDI_DIR}/downloads

# Custom logo
install -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/www/themes/common/images
install -m 644 %{SOURCE3} $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/www/themes/common/images/organization_logo.png

%post
/usr/bin/chcon -R root:object_r:httpd_sys_content_t %{CODENDI_DIR}/documentation %{CODENDI_DIR}/downloads


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,codendiadm,codendiadm,-)
%{CODENDI_DIR}/documentation
%{CODENDI_DIR}/downloads
%{CODENDI_DIR}/src/www/themes/common/images/organization_logo.png

#%doc
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

