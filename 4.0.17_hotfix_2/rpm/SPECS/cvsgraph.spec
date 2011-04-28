Name:           cvsgraph
Version:        1.6.1
Release:        3%{?dist}
Summary:        CVS/RCS repository grapher

Group:          Development/Tools
License:        GPL
URL:            http://www.akhphd.au.dk/~bertho/cvsgraph/
Source0:        http://www.akhphd.au.dk/~bertho/cvsgraph/release/%{name}-%{version}.tar.gz
Source1:        %{name}-httpd.conf
Patch0:         %{name}-1.6.0-config.patch
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildRequires:  gd-devel
BuildRequires:  byacc
BuildRequires:  flex
BuildRequires:  freetype-devel
BuildRequires:  libpng-devel
BuildRequires:  libjpeg-devel
BuildRequires:  %{__perl}

%description
CvsGraph is a utility to make a graphical representation of all
revisions and branches of a file in a CVS/RCS repository. It has been
inspired by the 'graph' option in WinCVS.


%prep
%setup -q
%patch0
rename .php3 .php contrib/*.php3
%{__perl} -pi -e \
  's|/home/bertho/public_html/cvsgraph/cvsgraph|%{_bindir}/cvsgraph|g ;
   s|/home/bertho/public_html/cvsgraph/conf/cvsgraph\.conf|%{_sysconfdir}/cvsgraph.conf|g' \
   contrib/*.php
%{__perl} -pi -e 's|/usr/local/etc|%{_sysconfdir}|g' cvsgraph.1
install -pm 644 %{SOURCE1} contrib/cvsgraph-httpd.conf


%build
%configure
make %{?_smp_mflags}


%install
rm -rf $RPM_BUILD_ROOT
install -Dpm 755 cvsgraph $RPM_BUILD_ROOT%{_bindir}/cvsgraph
install -Dpm 644 cvsgraph.conf $RPM_BUILD_ROOT%{_sysconfdir}/cvsgraph.conf
install -Dpm 644 cvsgraph.1 $RPM_BUILD_ROOT%{_mandir}/man1/cvsgraph.1
install -Dpm 644 cvsgraph.conf.5 $RPM_BUILD_ROOT%{_mandir}/man5/cvsgraph.conf.5


%clean
rm -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%doc ChangeLog LICENSE README contrib/
%config(noreplace) %{_sysconfdir}/cvsgraph.conf
%{_bindir}/cvsgraph
%{_mandir}/man[15]/cvsgraph*.[15]*


%changelog
* Tue Aug 29 2006 Ville Skyttä <ville.skytta at iki.fi> - 1.6.1-3
- Drop no longer needed Obsoletes.

* Mon Jul 31 2006 Ville Skyttä <ville.skytta at iki.fi> - 1.6.1-2
- Ensure proper doc file permissions (#200770).

* Sun Jul  9 2006 Ville Skyttä <ville.skytta at iki.fi> - 1.6.1-1
- 1.6.1.

* Thu Feb 16 2006 Ville Skyttä <ville.skytta at iki.fi> - 1.6.0-2
- Rebuild.

* Sun Dec 18 2005 Ville Skyttä <ville.skytta at iki.fi> - 1.6.0-1
- 1.6.0.

* Tue Aug 30 2005 Ville Skyttä <ville.skytta at iki.fi> - 1.5.2-1
- 1.5.2.

* Fri Apr  7 2005 Michael Schwendt <mschwendt[AT]users.sf.net> - 1.5.1-2
- rebuilt

* Wed Jan 26 2005 Ville Skyttä <ville.skytta at iki.fi> - 0:1.5.1-1
- Update to 1.5.1; wrapper, spelling and part of config patch applied upstream.
- Drop -web subpackage, include *.php as docs.

* Fri Sep  3 2004 Ville Skyttä <ville.skytta at iki.fi> - 0:1.5.0-0.fdr.1
- Update to 1.5.0.
- Improve default configuration, manual page spelling fixes.
- Rename httpd.conf snippet to zzz-cvsgraph.conf.
- Spec cleanups.

* Sun Jun  1 2003 Ville Skyttä <ville.skytta at iki.fi> - 0:1.4.0-0.fdr.3
- Address -web package comments in #56.
- Spec cleanups and tweaks according to current Fedora spec template.

* Fri Apr 25 2003 Ville Skyttä <ville.skytta at iki.fi> - 0:1.4.0-0.fdr.2
- Fix missing Epoch in -web "main" package dependency.
- Save .spec in UTF-8.

* Sat Mar 22 2003 Ville Skyttä <ville.skytta at iki.fi> - 0:1.4.0-0.fdr.1
- Update to 1.4.0 and current Fedora guidelines.

* Fri Feb  7 2003 Ville Skyttä <ville.skytta at iki.fi> - 1.3.0-1.fedora.1
- First Fedora release.
