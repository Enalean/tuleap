# default values when new /etc/rpm/macros.pear not present
%{!?__pear:       %define __pear       %{_bindir}/pear}
%{!?pear_phpdir:  %define pear_phpdir  %(%{__pear} config-get php_dir  2> /dev/null || echo undefined)}
%{!?pear_docdir:  %define pear_docdir  %(%{__pear} config-get doc_dir  2> /dev/null || echo undefined)}
%{!?pear_testdir: %define pear_testdir %(%{__pear} config-get test_dir 2> /dev/null || echo undefined)}
%{!?pear_datadir: %define pear_datadir %(%{__pear} config-get data_dir 2> /dev/null || echo undefined)}
%{!?pear_xmldir:  %define pear_xmldir  %{pear_phpdir}/.pkgxml}

Summary:     PEAR: Miscellaneous HTTP utilities
Summary(fr): PEAR: Divers utilitaires HTTP
Name:        php-pear-HTTP
Version:     1.4.1
Release:     1%{?dist}
License:     PHP License
Group:       Development/Libraries
Source:      http://pear.php.net/get/HTTP-%{version}.tgz
Source1:     PHP-LICENSE-3.01
BuildRoot:   %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
URL:         http://pear.php.net/package/HTTP

BuildArch:        noarch
BuildRequires:    php-pear(PEAR) >= 1.7.1
Requires(post):   %{__pear}
Requires(postun): %{__pear}
Requires:         php-pear(PEAR) >= 1.7.1, php >= 4.0.6
Provides:         php-pear(HTTP) = %{version}

%description
The HTTP class is a class with static methods for doing 
miscellaneous HTTP related stuff like date formatting,
language negotiation or HTTP redirection.
 
%description -l fr
La classe HTTP contient des méthodes statiques pour
réaliser diverses tâches concernant HTTP, comme la mise
en forme des dates, la négociation de la langue ou les
redirections HTTP.

%prep
%setup -c -q
mv package.xml HTTP-%{version}/HTTP.xml

%{__install} -m 644 -c %{SOURCE1} LICENSE

%build
# Empty build section

%install
rm -rf %{buildroot}

cd HTTP-%{version}
%{__pear} install --nodeps --packagingroot %{buildroot} HTTP.xml

# Clean up unnecessary files
rm -rf %{buildroot}%{pear_phpdir}/.??*

# Install XML package description
%{__mkdir_p} %{buildroot}%{pear_xmldir}
%{__install} -pm 644 HTTP.xml %{buildroot}%{pear_xmldir}

%check
# Sanity check
lst=$(find %{buildroot}%{pear_phpdir} -exec grep -q %{buildroot} {} \; -print)
[ ! -z "$lst" ] && echo "Reference to BUILDROOT in $lst" && exit 1;

%clean
rm -rf %{buildroot}

%post
%{__pear} install --nodeps --soft --force --register-only %{pear_xmldir}/HTTP.xml  >/dev/null || :

%postun
# if refcount = 0 then package has been removed (not upgraded)
if [ "$1" -eq "0" ]; then
    %{__pear} uninstall --nodeps --ignore-errors --register-only HTTP  >/dev/null || :
fi

%files
%defattr(-,root,root,-)
%{pear_phpdir}/HTTP.php
%{pear_xmldir}/HTTP.xml
%{pear_phpdir}/test/HTTP
%doc LICENSE

%changelog
* Mon Jan 10 2011 Manuel Vacelet <manuel.vacelet@st.com> 1.4.1-1
- Update upstream

* Thu Sep 07 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-7
- last template.spec

* Mon Sep 04 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-6
- new and simpler %%prep and %%install

* Mon Aug 28 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-5
- FE6 rebuild

* Sat May 20 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-4
- install Licence in prep
- use new macros from /etc/rpm/macros.pear

* Sat May 20 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-3
- Require pear >= 1.4.9
- bundle the v3.01 PHP LICENSE file
- use --packagingroot (instead of -R)
- check from install to check (as in php-pear)

* Sat May 06 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-2
- use %%{_datadir}/pear/.pkgxml for XML (Bug #190252)

* Fri Apr 28 2006 Remi Collet <Fedora@FamilleCollet.com> 1.4.0-1
- spec for extras
- add french summary & description

* Thu Apr 06 2006 Remi Collet <rpms@FamilleCollet.com> 1.4.0-2.fc{3,4,5}.remi
- change /var/lib/pear to %%{_libdir}/php/pear for XML (as in extras for FC5)
- spec cleanning

* Sat Jan  7 2006 Remi Collet <remi.collet@univ-reims.fr> 1.4.0-1.fc{3,4}.remi
- initial RPM
