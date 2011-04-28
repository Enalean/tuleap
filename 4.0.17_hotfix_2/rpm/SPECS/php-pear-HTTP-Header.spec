# default values when new /etc/rpm/macros.pear not present
%{!?__pear:       %define __pear       %{_bindir}/pear}
%{!?pear_phpdir:  %define pear_phpdir  %(%{__pear} config-get php_dir  2> /dev/null || echo undefined)}
%{!?pear_docdir:  %define pear_docdir  %(%{__pear} config-get doc_dir  2> /dev/null || echo undefined)}
%{!?pear_testdir: %define pear_testdir %(%{__pear} config-get test_dir 2> /dev/null || echo undefined)}
%{!?pear_datadir: %define pear_datadir %(%{__pear} config-get data_dir 2> /dev/null || echo undefined)}
%{!?pear_xmldir:  %define pear_xmldir  %{pear_phpdir}/.pkgxml}

Summary:     PEAR: OO interface to modify and handle HTTP headers and status codes.
Name:        php-pear-HTTP-Header
Version:     1.2.1
Release:     1%{?dist}
License:     BSD, revised
Group:       Development/Libraries
Source:      http://pear.php.net/get/HTTP_Header-%{version}.tgz
BuildRoot:   %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
URL:         http://pear.php.net/package/HTTP_Header

BuildArch:        noarch
BuildRequires:    php-pear(PEAR) >= 1.4.0
Requires(post):   %{__pear}
Requires(postun): %{__pear}
Requires:         php-pear(PEAR) >= 1.4.0, php >= 4.2.0, php-pear-HTTP >= 1.3.1
Provides:         php-pear(HTTP) = %{version}

%description
This class provides methods to set/modify HTTP headers
and status codes including an HTTP caching facility.
It also provides methods for checking Status types.
 
%prep
%setup -c -q
mv package.xml HTTP_Header-%{version}/HTTP_Header.xml

%build
# Empty build section

%install
rm -rf %{buildroot}

cd HTTP_Header-%{version}
%{__pear} install --nodeps --packagingroot %{buildroot} HTTP_Header.xml

# Clean up unnecessary files
rm -rf %{buildroot}%{pear_phpdir}/.??*

# Install XML package description
%{__mkdir_p} %{buildroot}%{pear_xmldir}
%{__install} -pm 644 HTTP_Header.xml %{buildroot}%{pear_xmldir}

%check
# Sanity check
lst=$(find %{buildroot}%{pear_phpdir} -exec grep -q %{buildroot} {} \; -print)
[ ! -z "$lst" ] && echo "Reference to BUILDROOT in $lst" && exit 1;

%clean
rm -rf %{buildroot}

%post
%{__pear} install --nodeps --soft --force --register-only %{pear_xmldir}/HTTP_Header.xml  >/dev/null || :

%postun
# if refcount = 0 then package has been removed (not upgraded)
if [ "$1" -eq "0" ]; then
    %{__pear} uninstall --nodeps --ignore-errors --register-only HTTP_Header  >/dev/null || :
fi

%files
%defattr(-,root,root,-)
%{pear_phpdir}/HTTP/Header.php
%{pear_phpdir}/HTTP/Header
%{pear_xmldir}/HTTP_Header.xml
%{pear_phpdir}/test/HTTP_Header
%doc %{pear_docdir}/HTTP_Header

%changelog
* Mon Jan 10 2011 Manuel Vacelet <manuel.vacelet@st.com> 1.2.1-1
- initial RPM
