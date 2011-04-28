# $Id: viewvc.spec 5977 2007-11-10 12:22:14Z dag $
# Authority: dag
%define name	viewvc
%define confdir %{_sysconfdir}/codendi/conf
%define codendi_user codendiadm
%define codendi_group codendiadm

Summary: Web-interface for CVS and Subversion version control repositories
Name: viewvc
Version: 1.0.7
Release: 1.codendi
License: BSD
Group: Development/Tools
URL: http://www.viewvc.org/

Packager:     Nicolas Guerin <nicolas.guerin@xerox.com>, Dag Wieers <dag@wieers.com>
Vendor:       Codendi http://codendi.com

Source0: http://www.viewvc.org/viewvc-%{version}.tar.gz
Patch0: viewvc-tools.patch
Patch1: viewvc-1.0.5-conf.codendi.patch
Patch2: viewvc-1.0.5-templates.codendi.patch
Patch3: viewvc-1.0.5-scripts.codendi.patch

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch
BuildRequires: python >= 1.5.2
Requires: httpd, python >= 1.5.2
Obsoletes: viewcvs
Provides: viewcvs = %{version}-%{release}

%description
ViewVC is a browser interface for CVS and Subversion version control 
repositories. It generates templatized HTML to present navigable 
directory, revision, and change log listings. It can display specific 
versions of files as well as diffs between those versions. Basically, 
ViewVC provides the bulk of the report-like functionality you expect out 
of your version control tool, but much more prettily than the average 
textual command-line program output.

%prep
%setup
%patch0 -p1
# Codendi patches are applied during install, to avoid compilation issues
find . -type d -name .svn | xargs %{__rm} -rf

%{__cat} <<EOF >/tmp/viewvc.httpd
### viewvc sample configuration

#ScriptAlias /viewvc %{_localstatedir}/www/cgi-bin/viewvc.cgi
#ScriptAlias /query %{_localstatedir}/www/cgi-bin/query.cgi
Alias /viewvc-static %{_localstatedir}/www/viewvc

<Directory %{_localstatedir}/www/viewvc>
	Allow from all
</Directory>
EOF

%build

%install
%{__rm} -rf %{buildroot}
%{__python} viewvc-install --destdir="%{buildroot}" --prefix="%{_datadir}/viewvc"

### Remove mod_python files
%{__rm} -rf %{buildroot}%{_datadir}/viewvc/bin/mod_python

### Fix python files perms and shellbang
%{__perl} -pi \
    -e 's|/usr/local/bin/python|%{_bindir}/python|g;' \
    -e 's|\s*/usr/bin/env python|%{_bindir}/python|g;' \
    -e 's|CONF_PATHNAME =.*|CONF_PATHNAME = r"%{confdir}/%{name}.conf"|g;' \
    $(find %{buildroot}%{_datadir}/viewvc/ -type f)

#perl -p -i -e 's|<VIEWVC_INSTALL_DIRECTORY>|%{_datadir}/%{name}|g;' viewvc.conf.dist
pwd
cd %{buildroot}%{_datadir}/viewvc
pwd
patch -p1 viewvc.conf $RPM_SOURCE_DIR/viewvc-1.0.5-conf.codendi.patch
patch -p1 < $RPM_SOURCE_DIR/viewvc-1.0.5-templates.codendi.patch
patch -p1 < $RPM_SOURCE_DIR/viewvc-1.0.5-scripts.codendi.patch

### Install CGI's to www directory
%{__mkdir_p} %{buildroot}%{_localstatedir}/www/cgi-bin
%{__install} -p -m0755 %{buildroot}%{_datadir}/viewvc/bin/cgi/viewvc.cgi %{buildroot}%{_localstatedir}/www/cgi-bin/
%{__rm} -rf %{buildroot}%{_datadir}/viewvc/bin/cgi

### Fix paths in configuration
%{__perl} -pi \
    -e 's|templates/|%{_datadir}/viewvc/templates/|g;' \
    -e 's|^#docroot = .*|docroot = /viewvc-static|;' \
    -e 's|^cvsgraph_conf = .*|cvsgraph_conf = %{confdir}/cvsgraph.conf|;' \
    %{buildroot}%{_datadir}/viewvc/viewvc.conf

### Install config to sysconf directory
%{__install} -Dp -m0644 %{buildroot}%{_datadir}/viewvc/viewvc.conf %{buildroot}%{confdir}/viewvc.conf
%{__rm} -f %{buildroot}%{_datadir}/viewvc/viewvc.conf
%{__install} -Dp -m0644 %{buildroot}%{_datadir}/viewvc/cvsgraph.conf %{buildroot}%{confdir}/cvsgraph.conf
%{__rm} -f %{buildroot}%{_datadir}/viewvc/cvsgraph.conf

### Move static files under %{_localstatedir}/www
%{__mv} %{buildroot}%{_datadir}/viewvc/templates/docroot %{buildroot}%{_localstatedir}/www/viewvc

### Remove standard styles that conflict with Codendi styles
#note: 'undef $/' is needed to read the whole file at once, for multiline matching
perl -i -e 'undef $/; while(<>) {s/Standard.*Navigation/Navigation/s;print;}' %{buildroot}%{_localstatedir}//www/viewvc/styles.css

### Compile the python files
find %{buildroot}%{_datadir}/viewvc/lib -type f -name "*.pyc" | xargs %{__rm} -f
%{__python} -O %{_libdir}/python*/compileall.py %{buildroot}%{_datadir}/viewvc/lib

### Install viewcv Apache configuration
%{__install} -Dp -m0644 /tmp/viewvc.httpd %{buildroot}/etc/httpd/conf.d/viewvc.conf

### Set mode 755 on executable scripts
%{__grep} -rl '^#!' %{buildroot}%{_datadir}/viewvc | xargs %{__chmod} 0755

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
%doc CHANGES README INSTALL TODO
# %config(noreplace) %{_sysconfdir}/viewvc/
%config(noreplace) %{_sysconfdir}/httpd/conf.d/viewvc.conf
%{_datadir}/viewvc/
%attr(775, %{codendi_user}, %{codendi_group}) %{_localstatedir}/www/cgi-bin/viewvc.cgi
#%attr(775, %{codendi_user}, %{codendi_group}) %{_localstatedir}/www/cgi-bin/query.cgi
%attr(775, %{codendi_user}, %{codendi_group}) %{_localstatedir}/www/viewvc/
%attr(755, %{codendi_user}, %{codendi_group}) %dir %{confdir}
%attr(644, %{codendi_user}, %{codendi_group}) %config(noreplace) %{confdir}/%{name}.conf
%attr(644, %{codendi_user}, %{codendi_group}) %config(noreplace) %{confdir}/cvsgraph.conf

%changelog
* Mon Apr 27 2009 Nicolas Guerin <nicolas.guerin@xerox.com> - 1.0.7-1.codendi
- upgrade to release 1.0.7.

* Fri May 16 2008 Nicolas Guerin <nicolas.guerin@xerox.com> - 1.0.5-1.codendi
- apply Codendi specific patches: permissions, php highlighting, configuration, etc.

* Thu Feb 28 2008 Dag Wieers <dag@wieers.com> - 1.0.5-1 - 5977+/dag
- Updated to release 1.0.5.

* Sun Apr 15 2007 Dag Wieers <dag@wieers.com> - 1.0.4-1
- Updated to release 1.0.4.

* Sat Oct 14 2006 Dag Wieers <dag@wieers.com> - 1.0.3-1
- Updated to release 1.0.3.

* Tue Oct 10 2006 Dag Wieers <dag@wieers.com> - 1.0.2-2
- Fixed group name.

* Sat Sep 30 2006 Dag Wieers <dag@wieers.com> - 1.0.2-1
- Updated to release 1.0.2.

* Tue Aug 01 2006 Dag Wieers <dag@wieers.com> - 1.0.1-2
- Provide a better default httpd setup using Alias and ScriptALias /viewvc-static.

* Tue Aug 01 2006 Dag Wieers <dag@wieers.com> - 1.0.1-1
- Initial package based on Mandrake package.
