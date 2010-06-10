# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME codendi
%define APP_LIB_DIR %{_libdir}/%{APP_NAME}
%define APP_LIBBIN_DIR %{APP_LIB_DIR}/bin


Summary: Codendi fileforge
Name: %{PKG_NAME}-fileforge
Version: @@VERSION@@
Release: 1%{?dist}
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: %{PKG_NAME}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@st.com>

%description
Codendi is a web based application that address all the aspects of product development.
Fileforge is a tool to manage file deliveries

%prep
%setup -q -n %{PKG_NAME}-%{version}


%build
%{__cc} src/utils/fileforge.c -o src/utils/fileforge

%install
%{__rm} -rf $RPM_BUILD_ROOT


# Install Codendi executables
%{__install} -m 00755 -d $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 04755 src/utils/fileforge $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

%post

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,%{APP_USER},%{APP_USER},-)
%attr(-,root,root) %{APP_LIBBIN_DIR}/fileforge

#%doc 
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.
