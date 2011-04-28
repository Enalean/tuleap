#%{!?__pecl: %{expand: %%global __pecl %{_bindir}/pecl}}
%define php_extdir %(php-config --extension-dir 2>/dev/null || echo %{_libdir}/php4)                     
%global php_zendabiver %((echo 0; php -i 2>/dev/null | sed -n 's/^PHP Extension => //p') | tail -1)
%global php_version %((echo 0; php-config --version 2>/dev/null) | tail -1)
%define pecl_name APC

Summary:       APC caches and optimizes PHP intermediate code
Name:          php-pecl-apc
Version:       3.0.19
Release:       2%{?dist}
License:       PHP
Group:         Development/Languages
URL:           http://pecl.php.net/package/APC
Source:        http://pecl.php.net/get/APC-%{version}.tgz
BuildRoot:     %{_tmppath}/%{name}-%{version}-%{release}-root
Conflicts:     php-mmcache php-eaccelerator
BuildRequires: php-devel httpd-devel php-pear
%if %{?php_zend_api}0
# Require clean ABI/API versions if available (Fedora)
Requires:      php(zend-abi) = %{php_zend_api}
Requires:      php(api) = %{php_core_api}
%else
%if "%{rhel}" == "5"
# RHEL5 where we have php-common providing the Zend ABI the "old way"
Requires:      php-zend-abi = %{php_zendabiver}
%else
# RHEL4 where we have no php-common and nothing providing the Zend ABI...
Requires:      php = %{php_version}
%endif
%endif
Provides:      php-pecl(%{pecl_name}) = %{version}

# This is obsoleted by the above Provide, but for backwards compatibility
# with previous versions of the package
Provides:      php-pecl(apc) = %{version}

#Requires(post): %{__pecl}
#Requires(postun): %{__pecl}

%description
APC is a free, open, and robust framework for caching and optimizing PHP
intermediate code.

%prep
%setup -q -n %{pecl_name}-%{version}

%build
%{_bindir}/phpize
%configure --enable-apc-mmap --with-apxs=%{_sbindir}/apxs --with-php-config=%{_bindir}/php-config
%{__make} %{?_smp_mflags}

%install
%{__rm} -rf %{buildroot}
%{__make} install INSTALL_ROOT=%{buildroot}

# Install the package XML file
#%{__mkdir_p} %{buildroot}%{pecl_xmldir}
#%{__install} -m 644 ../package.xml %{buildroot}%{pecl_xmldir}/%{pecl_name}.xml

# Drop in the bit of configuration
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php.d
%{__cat} > %{buildroot}%{_sysconfdir}/php.d/apc.ini << 'EOF'
; Enable apc extension module
extension = apc.so
; Options for the apc module
apc.enabled=1
apc.shm_segments=1
apc.optimization=0
apc.shm_size=32
apc.ttl=7200
apc.user_ttl=7200
apc.num_files_hint=1024
apc.mmap_file_mask=/tmp/apc.XXXXXX
apc.enable_cli=1
apc.cache_by_default=1
EOF

# Fix the charset of NOTICE
iconv -f iso-8859-1 -t utf8 NOTICE >NOTICE.utf8
mv NOTICE.utf8 NOTICE

#%post
#%{__pecl} install --nodeps --soft --force --register-only --nobuild %{pecl_xmldir}/%{pecl_name}.xml >/dev/null || :


#%postun
#if [ $1 -eq 0 ]  ; then
#%{__pecl} uninstall --nodeps --ignore-errors --register-only %{pecl_name} >/dev/null || :
#fi

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
%doc TECHNOTES.txt CHANGELOG LICENSE NOTICE TODO INSTALL apc.php
%config(noreplace) %{_sysconfdir}/php.d/apc.ini
%{php_extdir}/apc.so
#%{pecl_xmldir}/%{pecl_name}.xml

%changelog
* Fri Sep 19 2008 Manuel Vacelet <manuel.vacelet@st.com> - 3.0.19-2
- Disable pecl related stuff.

* Wed Jun 25 2008 Tim Jackson <rpm@timj.co.uk> - 3.0.19-1
- Update to 3.0.19
- Fix PHP Zend API/ABI dependencies to work on EL-4/5
- Fix "License" tag
- Fix encoding of "NOTICE" file
- Add registration via PECL

* Mon Feb 18 2008 Fedora Release Engineering <rel-eng@fedoraproject.org> - 3.0.14-3
- Autorebuild for GCC 4.3

* Tue Aug 28 2007 Fedora Release Engineering <rel-eng at fedoraproject dot org> - 3.0.14-2
- Rebuild for selinux ppc32 issue.

* Thu Jun 28 2007 Chris Chabot <chabotc@xs4all.nl> - 3.0.14-1
- Updated to 3.0.14
- Included new php api snipplets

* Fri Sep 15 2006 Chris Chabot <chabotc@xs4all.nl> - 3.0.12-5
- Updated to new upstream version

* Mon Sep 11 2006 Chris Chabot <chabotc@xs4all.nl> - 3.0.10-5
- FC6 rebuild 

* Sun Aug 13 2006 Chris Chabot <chabotc@xs4all.nl> - 3.0.10-4
- FC6T2 rebuild

* Mon Jun 19 2006 - Chris Chabot <chabotc@xs4all.nl> - 3.0.10-3
- Renamed to php-pecl-apc and added provides php-apc
- Removed php version string from the package version

* Mon Jun 19 2006 - Chris Chabot <chabotc@xs4all.nl> - 3.0.10-2
- Trimmed down BuildRequires
- Added Provices php-pecl(apc)

* Sun Jun 18 2006 - Chris Chabot <chabotc@xs4all.nl> - 3.0.10-1
- Initial package, templated on already existing php-json 
  and php-eaccelerator packages
