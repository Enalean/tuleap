%global	php_apiver  %((echo 0; php -i 2>/dev/null | sed -n 's/^PHP API => //p') | tail -1)
%{!?__pecl:		%{expand:	%%global __pecl	%{_bindir}/pecl}}
%{!?php_extdir:	%{expand:	%%global php_extdir	%(php-config --extension-dir)}}

%define	peclName runkit

Summary:          Mangle with user defined functions and classes
Summary(ru):      Манипулирование пользовательскими функциями и классами
Summary(pl):      Obróbka zdefiniowanych przez użytkownika funkcji i klas
Name:             php-pecl-%{peclName}
Version:          0.9
Release:          11%{?dist}
License:          PHP
Group:            Development/Libraries
Source0:          http://pecl.php.net/get/%{peclName}-%{version}.tgz
# Source0-md5:    855786f79a3803972b04e44c32cece8d
URL:              http://pecl.php.net/package/runkit/
BuildRequires:    php-pear >= 1.4.7, php-devel >= 5.0.0
Requires(post):   %{__pecl}
Requires(postun): %{__pecl}
%if %{?php_zend_api}0
Requires:         php(zend-abi) = %{php_zend_api}
Requires:         php(api) = %{php_core_api}
%else
Requires:         php-api = %{php_apiver}
%endif
Provides:         php-pecl(%{peclName}) = %{version}
BuildRoot:        %{_tmppath}/%{name}-%{version}-root-%(id -u -n)

%description
Replace, rename, and remove user defined functions and classes. Define
customized superglobal variables for general purpose use. Execute code
in restricted environment (sandboxing).

%description -l ru
Замещение, переименование и удаление оперделенных пользователем функций
и классов. Определение собственных суперглобальных переменных. Выполнение
кода в ограниченной среде (песочнице)


%description -l pl
Zastępowanie, zmiana nazwy lub usuwanie zdefiniowanych przez
użytkownika funkcji i klas. Definiowanie zmiennych superglobalnych do
ogólnego użytku. Wykonywanie danego kodu w ograniczonym środowisku
(sandbox).

%prep
%setup -q -c
cd %{peclName}-%{version}

%build
cd %{peclName}-%{version}
phpize
#*Hu0 %configure
%configure --enable-%{peclName} --with-%{peclName}
%{__make}

%install
cd %{peclName}-%{version}

rm -rf %{buildroot}
install -d %{buildroot}{%{_sysconfdir}/php.d,%{php_extdir}}

install modules/%{peclName}.so %{buildroot}%{php_extdir}
#*Hu cat <<'EOF' > %{buildroot}%{_sysconfdir}/conf.d/%{peclName}.ini
cat <<'EOF' > %{buildroot}%{_sysconfdir}/php.d/%{peclName}.ini
; Enable %{peclName} extension module
extension=%{peclName}.so
EOF

# Install XML package description
install -m 0755 -d %{buildroot}%{pecl_xmldir}
cd ..
install -m 0664 package2.xml %{buildroot}%{pecl_xmldir}/%{name}.xml

%post
%if 0%{?pecl_install:1}
%{pecl_install} %{pecl_xmldir}/%{name}.xml >/dev/null || :
%endif

%postun
%if 0%{?pecl_uninstall:1}
if [ "$1" -eq "0" ]; then
    %{pecl_uninstall} %{peclName} >/dev/null || :
fi
%endif

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc %{peclName}-%{version}/README
%config(noreplace) %verify(not md5 mtime size) %{_sysconfdir}/php.d/%{peclName}.ini
%{pecl_xmldir}/%{name}.xml
%attr(755,root,root) %{php_extdir}/%{peclName}.so

%changelog
* Tue Jan 25 2011 Hatem Ounis <hatem.ounis@st.com> - 0.9-11
- To simplify use only the tarball from http://pecl.php.net/get/runkit-0.9.tgz

* Tue Mar 17 2009 Pavel Alexeev <Pahan@Hubbitus.info> - 0.9-10.CVS20090215
- Remi Collet notes in Fedora review:
- Rename back %%{peclName}.xml to %%{name}.xml :)
- Set %%defattr(-,root,root,-) (was %%defattr(644,root,root,755))
- Make the %%post/%%postun scriptlets silent

* Mon Mar 9 2009 Pavel Alexeev <Pahan@Hubbitus.info> - 0.9-9.CVS20090215
- In rename %%{name}.xml to %%{peclName}.xml
- Add BR php-pear >= 1.4.7

* Wed Feb 25 2009 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-8.CVS20090215
- All changes inspired by Fedora package review by Remi Collet.
- From summary deleted name.
- Readmy path fixed(replaced %%{peclName}-%%{version}/README to %%{peclName}/README)
- Group changed to Development/Libraries (was: Development/Languages/PHP)
- Removed Obsoletes: php-pear-%%{peclName} it was unnecessary.

* Mon Feb 23 2009 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-7.CVS20090215
- Again change version enumeration (https://bugzilla.redhat.com/show_bug.cgi?id=455226#c9).
- %%{pecl_xmldir}/%%{peclName}2.xml changed to %%{pecl_xmldir}/%%{name}.xml
- Recode pl summary and description text from iso8859-2 to UTF-8.

* Sun Feb 15 2009 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-0.6.CVS20090215
- Step to CVS build 20090215.
- Replace $RPM_BUILD_ROOT to %%{buildroot} to consistence usage.
- Strip some old comments.
- Add translated Summary(ru) and description.
- Remove legacy macros %%{?requires_php_extension}.
- All macroses %%peclName replaced to %{peclName} usages.
- Add file: %%{pecl_xmldir}/%%{peclName}2.xml
- All followed changes inspired by Fedora review by Remi Collet ( https://bugzilla.redhat.com/show_bug.cgi?id=455226 ).
- Change version enumeration, delete Hu-part.
- Modify Source0 for CVS build. Add comment about get it source.
- Spec-file renamed to php-pecl-runkit.spec.
- File BUG to upstream - http://pecl.php.net/bugs/bug.php?id=15969 .
- %%Post and %%postun scripts to restart apache removed.
- Register extension.
- Add PHP ABI provides/requires and Pre/post requires pecl.
- Defile some macroses from guidelines: php_apiver, __pecl, php_extdir.
- Replace %%extensionsdir by %%php_extdir.

* Mon May 12 2008 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-0.CVS20080512.Hu.5
- Add Patch3: php-pecl-runkit-0.9.Z_NEW_REFCOUNT.patch to reflect new zend API

* Mon May 12 2008 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-0.CVS20080512.Hu.3
- New CVS20080512 (cvs -d :pserver:cvsread@cvs.php.net/repository checkout pecl/runkit)
- Rename %%{_modname} to peclName to unify SPECs.
- Correct %%if 0%%{?CVS} to %%if 0%%{?CVS:1} - it is not integer!
- Rename pethces to:
	Patch0:		php-pecl-runkit-0.9-ZVAL_REFCOUNT.patch
	Patch1:		php-pecl-runkit-0.9-ZVAL_ADDREF.patch

* Mon Mar 10 2008 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] spb [ dOt.] su> - 0.9-0.CVS20080310.Hu.2
- CVS20080310 build. (cvs -d :pserver:cvsread@cvs.php.net:/repository checkout pecl/runkit)
- 0.9 stable are incompatible with php 5.3.0, build from CVS. Disable self patch0
- Enable patch0. Rewritten and rename to fix ZVAL_REFCOUNT.patch
	Hu.1
- Add patch1. Fix wrong call ZVAL_ADDREF.patch
	Hu.2

* Sun Mar 9 2008 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] info> - 0.9-0.Hu.3
- Add patch (self written) zval_ref.patch. It is allow build.
- Agjust built dir:
	BuildRoot:	%%{tmpdir}/%%{name}-%%{version}-root-%%(id -u -n)
	to
	BuildRoot:	%%{_tmppath}/%%{name}-%%{version}-root-%%(id -u -n)
- Fix Release:		0%%{?dist}.Hu.2 -> Release:		0%%{?dist}.Hu.2
	Hu.2
- Remove %%define _status beta and all apearance of %%{_status}
- Remove %%define _sysconfdir /etc/php (it's already defined in system wide)
- Remove Requires: %%{_sysconfdir}/conf.d
- Change path %%{_sysconfdir}/conf.d to %%{_sysconfdir}/php.d:
	Replace:
		install -d $RPM_BUILD_ROOT{%%{_sysconfdir}/conf.d,%%{extensionsdir}}
		to
		install -d $RPM_BUILD_ROOT{%%{_sysconfdir}/php.d,%%{extensionsdir}}

		%%config(noreplace) %%verify(not md5 mtime size) %%{_sysconfdir}/conf.d/%%{_modname}.ini
		to
		%%config(noreplace) %%verify(not md5 mtime size) %%{_sysconfdir}/php.d/%%{_modname}.ini

		cat <<'EOF' > $RPM_BUILD_ROOT%%{_sysconfdir}/conf.d/%%{_modname}.ini
		to
		cat <<'EOF' > $RPM_BUILD_ROOT%%{_sysconfdir}/php.d/%%{_modname}.ini
- Hu.3

* Wed Feb 27 2008 Pavel Alexeev <Pahan [ at ] Hubbitus [ DOT ] info> - 0.9-0.Hu.0
- Import from ftp://ftp.pld-linux.org/dists/2.0/PLD/SRPMS/SRPMS/php-pecl-runkit-0.4-5.src.rpm
- Step to version 0.9
	Release:		0{?dist}.Hu.0 (Was: Release:	0)
- Remove defining %%date and:
	* %%{date} PLD Team <feedback@pld-linux.org>
	All persons listed below can be reached at <cvs_login>@pld-linux.org
 due to error: ошибка: %%changelog не в нисходящем хронологическом порядке
- Small reformat of header spec
- Change BuildRequires:	php-devel >= 3:5.0.0 to php-devel >= 5.0.0
-Remove BuildRequires:	rpmbuild(macros) >= 1.254

# Old, Legacy changelog in incorrect format simple commented:
#$Log: php-pecl-runkit.spec,v $
#Revision 1.8  2005/12/22 12:12:04  glen
#- rel 5 (rebuild with new php)
#
#Revision 1.7  2005/10/30 13:29:27  glen
#- rel 4
#
#Revision 1.6  2005/10/29 00:05:14  glen
#- rebuild with zts and debug requires
#
#Revision 1.5  2005/09/14 22:33:47  glen
#- rel 2
#
#Revision 1.4  2005/09/14 13:37:00  glen
#- conf.d and php api macros
#
#Revision 1.3  2005/09/13 21:16:43  glen
#- superfluous BR libtool removed
#
#Revision 1.2  2005/07/27 22:08:17  qboosh
#- more standard pl desc
#
#Revision 1.1  2005/07/26 20:23:48  adamg
#- new
