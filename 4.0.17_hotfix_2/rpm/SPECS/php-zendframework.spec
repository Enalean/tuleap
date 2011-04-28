%define php_name ZendFramework

Summary:         Leading open-source PHP framework
Name:            php-zendframework
Version:         1.8.1
Release:         1%{?dist}

License:         BSD
Group:           Development/Libraries
Source0:         http://framework.zend.com/releases/%{php_name}-%{version}/%{php_name}-%{version}-minimal.tar.gz
BuildRoot:       %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
URL:             http://framework.zend.com/

BuildArch:       noarch

Requires: php >= 5.1.4
BuildRequires: symlinks

%description
Extending the art & spirit of PHP, Zend Framework is based on simplicity,
object-oriented best practices, corporate friendly licensing, and a rigorously
tested agile codebase. Zend Framework is focused on building more secure,
reliable, and modern Web 2.0 applications & web services, and consuming widely
available APIs from leading vendors like Google, Amazon, Yahoo!, Flickr, as
well as API providers and catalogers like StrikeIron and ProgrammableWeb.

%prep
%setup -qn %{php_name}-%{version}-minimal

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__mkdir_p} $RPM_BUILD_ROOT%{_datadir}/zend
%{__cp} -pr library/Zend $RPM_BUILD_ROOT%{_datadir}/zend

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/zend
%doc LICENSE.txt INSTALL.txt README.txt

%changelog
* Fri Oct 07 2008 Manuel VACELET <manuel.vacelet@st.com> - 1.8.1-1
- update to 1.8.1

* Fri Oct 07 2008 Manuel VACELET <manuel.vacelet@st.com> - 1.6.2-1
- update to 1.6.2
- full package.
- remove dependencies not appliable to RHEL5

* Sat Sep 13 2008 Alexander Kahl <akahl@iconmobile.com> - 1.6.0-1
- update to 1.6.0 stable (full version)
- create list of invalid executables in %%build for upstream
- new components Captcha, Dojo, Service-ReCaptcha, Wildfire, Zend_Tool
- BuildRequire symlinks to sanitize zf -> zf.sh symlink

* Sat Aug  2 2008 Alexander Kahl <akahl@iconmobile.com> - 1.6.0-0.2.rc1
- added license file to all packages to silence rpmline

* Tue Jul 29 2008 Alexander Kahl <akahl@iconmobile.com> - 1.6.0-0.1.rc1
- update to 1.6.0RC1
- added php-Fileinfo dependency

* Wed Jun 11 2008 Alexander Kahl <akahl@iconmobile.com> - 1.5.2-1
- update to 1.5.2
- new package split
- removed Cache-Backend-Sqlite, Db-Adapter-Db2, Db-Adapter-Firebird,
  Db-Adapter-Oracle
- removed optional php-bitset requirement from Search-Lucene, not available
- removed virtual requires and provides, not necessary anymore

* Mon Mar 17 2008 Alexander Kahl <akahl@iconmobile.com> - 1.5.0-1
- updated for 1.5.0 stable

* Mon Mar 17 2008 Alexander Kahl <akahl@iconmobile.com> - 1.5.0-1.rc3
- new upstream version rc3
- updated for 1.5.0 stable
- new subpackages Ldap and Service-Nirvanix

* Fri Mar  7 2008 Alexander Kahl <akahl@iconmobile.com> - 1.5.0-2.rc1
- added missing dependencies

* Thu Mar  6 2008 Alexander Kahl <akahl@iconmobile.com> - 1.5.0-1.rc1
- new release candidate version 1.5.0
- package all zend components in subpackages

* Wed Dec 12 2007 Alexander Kahl <akahl@iconmobile.com> - 1.0.3-1
- new stable version 1.0.3
- preserve timestamps upon copying
- split up documentation into subpackages
- description BE->AE

* Thu Oct 30 2007 Alexander Kahl <akahl@iconmobile.com> - 1.0.2-1
- initial release
