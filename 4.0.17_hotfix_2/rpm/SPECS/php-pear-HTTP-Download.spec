# default values when new /etc/rpm/macros.pear not present
%{!?__pear:       %define __pear       %{_bindir}/pear}
%{!?pear_phpdir:  %define pear_phpdir  %(%{__pear} config-get php_dir  2> /dev/null || echo undefined)}
%{!?pear_docdir:  %define pear_docdir  %(%{__pear} config-get doc_dir  2> /dev/null || echo undefined)}
%{!?pear_testdir: %define pear_testdir %(%{__pear} config-get test_dir 2> /dev/null || echo undefined)}
%{!?pear_datadir: %define pear_datadir %(%{__pear} config-get data_dir 2> /dev/null || echo undefined)}
%{!?pear_xmldir:  %define pear_xmldir  %{pear_phpdir}/.pkgxml}

Summary:     PEAR: Send HTTP Downloads
Name:        php-pear-HTTP-Download
Version:     1.1.4
Release:     1%{?dist}
License:     BSD, revised
Group:       Development/Libraries
Source:      http://pear.php.net/get/HTTP_Download-%{version}.tgz
BuildRoot:   %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
URL:         http://pear.php.net/package/HTTP_Download

BuildArch:        noarch
BuildRequires:    php-pear(PEAR) >= 1.6.0
Requires(post):   %{__pear}
Requires(postun): %{__pear}
Requires:         php-pear(PEAR) >= 1.6.0, php >= 4.2.0, php-pear-HTTP-Header
Provides:         php-pear(HTTP) = %{version}

%description
Provides an interface to easily send hidden files or any arbitrary data to
HTTP clients. HTTP_Download can gain its data from variables, files or
stream resources.

It features:
- Basic caching capabilities
- Basic throttling mechanism
- On-the-fly gzip-compression
- Ranges (partial downloads and resuming)
- Delivery of on-the-fly generated archives through Archive_Tar and Archive_Zip
- Sending of PgSQL LOBs without the need to read all data in prior to sending
 
%prep
%setup -c -q
mv package.xml HTTP_Download-%{version}/HTTP_Download.xml

%build
# Empty build section

%install
rm -rf %{buildroot}

cd HTTP_Download-%{version}
%{__pear} install --nodeps --packagingroot %{buildroot} HTTP_Download.xml

# Clean up unnecessary files
rm -rf %{buildroot}%{pear_phpdir}/.??*

# Install XML package description
%{__mkdir_p} %{buildroot}%{pear_xmldir}
%{__install} -pm 644 HTTP_Download.xml %{buildroot}%{pear_xmldir}

%check
# Sanity check
lst=$(find %{buildroot}%{pear_phpdir} -exec grep -q %{buildroot} {} \; -print)
[ ! -z "$lst" ] && echo "Reference to BUILDROOT in $lst" && exit 1;

%clean
rm -rf %{buildroot}

%post
%{__pear} install --nodeps --soft --force --register-only %{pear_xmldir}/HTTP_Download.xml  >/dev/null || :

%postun
# if refcount = 0 then package has been removed (not upgraded)
if [ "$1" -eq "0" ]; then
    %{__pear} uninstall --nodeps --ignore-errors --register-only HTTP_Download  >/dev/null || :
fi

%files
%defattr(-,root,root,-)
%{pear_phpdir}/HTTP/Download.php
%{pear_phpdir}/HTTP/Download
%{pear_xmldir}/HTTP_Download.xml
%{pear_phpdir}/test/HTTP_Download
%doc %{pear_docdir}/HTTP_Download

%changelog
* Mon Jan 10 2011 Manuel Vacelet <manuel.vacelet@st.com> 1.1.4-1
- initial RPM
