# $Id: munin.spec 5323 2007-04-29 13:09:01Z dag $
# Authority: dag
# Upstream: Ingvar Hagelund <ingvar$linpro,no>

%define perl_vendorlib %(eval "`%{__perl} -V:installvendorlib`"; echo $installvendorlib)
%define logmsg logger -t %{name}/rpm

Summary: Network-wide graphing framework (grapher/gatherer)
Name: munin
Version: 1.2.5
Release: 1.codendi
License: GPL
Group: System Environment/Daemons
URL: http://munin.projects.linpro.no/

Packager: Dag Wieers <dag@wieers.com>
Vendor: Dag Apt Repository, http://dag.wieers.com/apt/

Source0: http://dl.sf.net/sourceforge/munin/munin_%{version}.tar.gz
Source1: munin-1.2.5-hddtemp_smartctl-config
Source2: munin-1.2.4-sendmail-config
Patch0: munin-1.2.4-cron.patch
Patch1: munin-1.2.4-conf.patch
Patch2: munin-1.2.5-makefile.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch
Requires: perl-Net-Server, perl-Net-SNMP
Requires: rrdtool, shadow-utils

%description
Munin is a highly flexible and powerful solution used to create graphs of
virtually everything imaginable throughout your network, while still
maintaining a rattling ease of installation and configuration.

This package contains the grapher/gatherer. You will only need one instance of
it in your network. It will periodically poll all the nodes in your network
it's aware of for data, which it in turn will use to create graphs and HTML
pages, suitable for viewing with your graphical web browser of choice.

%package node
Group: System Environment/Daemons
Summary: Network-wide graphing framework (node)

Requires: perl-Net-Server
Requires: procps >= 2.0.7
Requires: shadow-utils, sysstat
Requires: /sbin/chkconfig
Requires: /sbin/service

%description node
Munin is a highly flexible and powerful solution used to create graphs of
virtually everything imaginable throughout your network, while still
maintaining a rattling ease of installation and configuration.

This package contains node software. You should install it on all the nodes
in your network. It will know how to extract all sorts of data from the
node it runs on, and will wait for the gatherer to request this data for
further processing.

It includes a range of plugins capable of extracting common values such as
cpu usage, network usage, load average, and so on. Creating your own plugins
which are capable of extracting other system-specific values is very easy,
and is often done in a matter of minutes. You can also create plugins which
relay information from other devices in your network that can't run Munin,
such as a switch or a server running another operating system, by using
SNMP or similar technology.

%prep
%setup
%patch0 -p1
%patch1 -p1
%patch2 -p1

### htmldoc and html2text are not available for Red Hat. Quick hack with perl:
### Skip the PDFs.
%{__perl} -pi.orig -e 's|htmldoc munin|cat munin| or s|html(2text\|doc)|# $&|' Makefile
%{__perl} -pi.orig -e 's|\$\(INSTALL.+\.(pdf\|txt) \$\(DOCDIR|# $&|' Makefile

### Don't enable munin-node by default. 
%{__perl} -pi.orig -e 's|2345|-|' dists/redhat/munin-node.rc

%{__cat} <<EOF > munin.logrotate.conf
%{_localstatedir}/log/munin/munin-graph.log %{_localstatedir}/log/munin/munin-html.log %{_localstatedir}/log/munin/munin-limits.log %{_localstatedir}/log/munin/munin-update.log {
        daily
        missingok
        rotate 7
        compress
        notifempty
        create 640 munin adm
}
EOF

%{__cat} <<EOF >munin-node.logrotate.conf
%{_localstatedir}/log/munin/munin-node.log {
	daily
	missingok
	rotate 7
	compress
	copytruncate
	notifempty
	create 640 root adm
}
EOF

%{__cat} <<EOF >munin.httpd
#
# Apache configuration to support munin and munin-cgi-graph
# Valid user is required to avoid anonymous access.
# You can select a stricter policy if needed.

ScriptAlias /munin/cgi /var/www/munin/cgi

<Directory /var/www/munin/cgi>
    Options ExecCGI
    Allow from all
    Require valid-user
    AuthType Basic
    AuthName "Munin Authorization"
    AuthMYSQLEnable on
    AuthMySQLUser dbauthuser
    AuthMySQLPassword %sys_dbauth_passwd%
    AuthMySQLDB codendi
    AuthMySQLUserTable "user, user_group"
    AuthMySQLNameField user.user_name
    AuthMySQLPasswordField user.unix_pw
    AuthMySQLUserCondition "(user.status='A' AND user_group.user_id=user.user_id and user_group.group_id=1)"
</Directory>

Alias /munin "/var/www/munin"

<Directory "/var/www/munin">
    Allow from all
    Require valid-user
    AuthType Basic
    AuthName "Munin Authorization"
    AuthMYSQLEnable on
    AuthMySQLUser dbauthuser
    AuthMySQLPassword %sys_dbauth_passwd%
    AuthMySQLDB codendi
    AuthMySQLUserTable "user, user_group"
    AuthMySQLNameField user.user_name
    AuthMySQLPasswordField user.unix_pw
    AuthMySQLUserCondition "(user.status='A' AND user_group.user_id=user.user_id and user_group.group_id=1)"
</Directory>
EOF

%build
%{__make} build CONFIG="dists/redhat/Makefile.config"

%install
%{__rm} -rf %{buildroot}

### Install server
%{__make} install-main install-man \
	CONFIG="dists/redhat/Makefile.config" \
	DESTDIR="%{buildroot}" \
	MANDIR="%{buildroot}%{_mandir}"

%{__install} -Dp -m0644 dists/redhat/munin.cron.d %{buildroot}%{_sysconfdir}/cron.d/munin
%{__install} -Dp -m0644 munin.logrotate.conf %{buildroot}%{_sysconfdir}/logrotate.d/munin
%{__install} -Dp -m0644 munin.httpd %{buildroot}%{_sysconfdir}/httpd/conf.d/munin.conf
%{__install} -Dp -m0644 server/style.css %{buildroot}%{_localstatedir}/www/munin/style.css

### Move munin CGI to /var/www/munin
%{__install} -dp -m0755 %{buildroot}%{_localstatedir}/www/munin/cgi/
%{__mv} -v %{buildroot}%{_localstatedir}/www/html/munin/cgi/munin-cgi-graph \
			%{buildroot}%{_localstatedir}/www/munin/cgi/munin-cgi-graph

### Install node
%{__make} install-node install-node-plugins \
	CONFIG="dists/redhat/Makefile.config" \
	DESTDIR="%{buildroot}" \

%{__install} -Dp -m0755 dists/redhat/munin-node.rc %{buildroot}%{_initrddir}/munin-node
%{__install} -Dp -m0644 dists/tarball/plugins.conf %{buildroot}%{_sysconfdir}/munin/plugins.conf
%{__install} -Dp -m0644 dists/tarball/plugins.conf %{buildroot}%{_sysconfdir}/munin/plugin-conf.d/munin-node
%{__install} -Dp -m0644 munin-node.logrotate.conf %{buildroot}%{_sysconfdir}/logrotate.d/munin-node

### Remove the Sybase plugin for now, as they need perl modules 
### that are not in extras. We can readd them when/if those modules are added. 
%{__rm} -f %{buildroot}%{_datadir}/munin/plugins/sybase_space

### Install config for hddtemp_smartctl
%{__install} -Dp -m0644 %{SOURCE1} %{buildroot}/etc/munin/plugin-conf.d/hddtemp_smartctl

### Install config for sendmail under fedora
%{__install} -Dp -m0644 %{SOURCE2} %{buildroot}/etc/munin/plugin-conf.d/sendmail

### Create empty directories
%{__install} -dp -m0755 %{buildroot}%{_localstatedir}/lib/munin/
%{__install} -dp -m0755 %{buildroot}%{_localstatedir}/log/munin/
%{__install} -dp -m0755 %{buildroot}%{_sysconfdir}/munin/plugins/

%clean
%{__rm} -rf %{buildroot}

# Main package scripts
%pre
if ! /usr/bin/getent group munin &>/dev/null; then
        /usr/sbin/groupadd -r munin || \
                %logmsg "Unexpected error adding group \"munin\". Aborting installation."
fi
if ! /usr/bin/id munin &>/dev/null; then
        /usr/sbin/useradd -r -s /bin/nologin -d %{_localstatedir}/lib/munin -c "Munin user" -g munin munin || \
                %logmsg "Unexpected error adding user \"munin\". Aborting installation."
fi

### userdel removes group munin as well
%postun
if [ $1 -eq 0 ]; then
	/usr/sbin/userdel munin || %logmsg "User \"munin\" could not be deleted."
#	/usr/sbin/groupdel munin || %logmsg "Group \"munin\" could not be deleted."
fi
 
### Node package scripts
%pre node
if ! /usr/bin/getent group munin &>/dev/null; then
        /usr/sbin/groupadd -r munin || \
                %logmsg "Unexpected error adding group \"munin\". Aborting installation."
fi
if ! /usr/bin/id munin &>/dev/null; then
        /usr/sbin/useradd -r -s /bin/nologin -d %{_localstatedir}/lib/munin -c "Munin user" -g munin munin || \
                %logmsg "Unexpected error adding user \"munin\". Aborting installation."
fi

%post node
/sbin/chkconfig --add munin-node
/usr/sbin/munin-node-configure --shell | sh

%preun node
if [ $1 -eq 0 ]; then
	/sbin/service munin-node stop &>/dev/null || :
	/sbin/chkconfig --del munin-node
fi

### userdel removes group munin as well
%postun node
if [ $1 -eq 0 ]; then
	/usr/sbin/userdel munin || %logmsg "User \"munin\" could not be deleted."
#	/usr/sbin/groupdel munin || %logmsg "Group \"munin\" could not be deleted."
fi

%files
%defattr(-, root, root, 0755)
%doc ChangeLog COPYING README.api munin-*.html build/README-apache-cgi
%doc %{_mandir}/man5/munin.conf.5*
%doc %{_mandir}/man8/munin-cron.8*
%doc %{_mandir}/man8/munin-graph.8*
%doc %{_mandir}/man8/munin-html.8*
%doc %{_mandir}/man8/munin-limits.8*
%doc %{_mandir}/man8/munin-update.8*
%config(noreplace) %{_sysconfdir}/cron.d/munin
%dir %{_sysconfdir}/munin/
%config(noreplace) %{_sysconfdir}/munin/munin.conf
%config(noreplace) %{_sysconfdir}/munin/templates/
%config(noreplace) %{_sysconfdir}/logrotate.d/munin
%dir %{_sysconfdir}/httpd/
%dir %{_sysconfdir}/httpd/conf.d/
%config(noreplace) %{_sysconfdir}/httpd/conf.d/munin.conf
%{_bindir}/munin-cron
%{_datadir}/munin/munin-graph
%{_datadir}/munin/munin-html
%{_datadir}/munin/munin-limits
%{_datadir}/munin/munin-update
%{_datadir}/munin/VeraMono.ttf
%{_localstatedir}/www/munin/
%{perl_vendorlib}/Munin.pm

%defattr(-, munin, munin, 0755)
%dir %{_localstatedir}/lib/munin/
%dir %{_localstatedir}/log/munin/
%dir %{_localstatedir}/run/munin/
%dir %{_localstatedir}/www/munin/
%dir %{_localstatedir}/www/munin/cgi/
%doc %{_localstatedir}/www/munin/cgi/munin-cgi-graph
%doc %{_localstatedir}/www/munin/style.css

%files node
%defattr(-, root, root, 0755)
%doc COPYING node/TODO*
%doc %{_mandir}/man5/munin-node.conf.5*
%doc %{_mandir}/man8/munin-node.8*
%doc %{_mandir}/man8/munin-run.8*
%doc %{_mandir}/man8/munin-node-configure.8*
%doc %{_mandir}/man8/munin-node-configure-snmp.8*
%dir %{_sysconfdir}/munin/
%dir %{_sysconfdir}/munin/plugin-conf.d/
%dir %{_sysconfdir}/munin/plugins/
%config(noreplace) %{_sysconfdir}/logrotate.d/munin-node
%config(noreplace) %{_sysconfdir}/munin/munin-node.conf
%config(noreplace) %{_sysconfdir}/munin/plugin-conf.d/hddtemp_smartctl
%config(noreplace) %{_sysconfdir}/munin/plugin-conf.d/munin-node
%config(noreplace) %{_sysconfdir}/munin/plugin-conf.d/sendmail
%config(noreplace) %{_sysconfdir}/munin/plugins.conf
%config %{_initrddir}/munin-node
%{_sbindir}/munin-node
%{_sbindir}/munin-node-configure
%{_sbindir}/munin-node-configure-snmp
%{_sbindir}/munin-run
%dir %{_datadir}/munin/
%{_datadir}/munin/plugins/

%defattr(-, munin, munin, 0755)
%dir %{_localstatedir}/lib/munin/
%dir %{_localstatedir}/lib/munin/plugin-state/
%dir %{_localstatedir}/log/munin/

%changelog
* Mon May 5 2008 Nicolas Guerin <nicolas.guerin@xrce.xerox.com> - 1.2.5-1.codendi
- fix ownership on /var/www/munin
- add patch on redhat makefile
- require valid-user in apache configuration.

* Sun Apr 29 2007 Dag Wieers <dag@wieers.com> - 1.2.5-1 - 5323+/dag
- Cosmetic changes.

* Mon Oct 23 2006 Ingvar Hagelund <ingvar@linpro.no> - 1.2.5-1rhel4
- Simple repacking from fc5 to rhel. All kudos to Kevin for
  maintaining the Fedora Package
- For perl-Net-SNMP and dependencies not in rhel4, please consider 
  Dag Wieers repo, http://dag.wieers.com/packages/

* Tue Oct 17 2006 Kevin Fenzi <kevin@tummy.com> - 1.2.5-1
- Update to 1.2.5
- Fix HD stats (fixes #205042)
- Add in logrotate scripts that seem to have been dropped upstream

* Tue Jun 27 2006 Kevin Fenzi <kevin@tummy.com> - 1.2.4-9
- Re-enable snmp plugins now that perl-Net-SNMP is available (fixes 196588)
- Thanks to Herbert Straub <herbert@linuxhacker.at> for patch. 
- Fix sendmail plugins to look in the right place for the queue

* Sat Apr 22 2006 Kevin Fenzi <kevin@tummy.com> - 1.2.4-8
- add patch to remove unneeded munin-nagios in cron. 
- add patch to remove buildhostname in munin.conf (fixes #188928)
- clean up prep section of spec. 

* Fri Feb 24 2006 Kevin Fenzi <kevin@scrye.com> - 1.2.4-7
- Remove bogus Provides for perl RRDs (fixes #182702)

* Thu Feb 16 2006 Kevin Fenzi <kevin@tummy.com> - 1.2.4-6
- Readded old changelog entries per request
- Rebuilt for fc5

* Sat Dec 24 2005 Kevin Fenzi <kevin@tummy.com> - 1.2.4-5
- Fixed ownership for /var/log/munin in node subpackage (fixes 176529)

* Wed Dec 14 2005 Kevin Fenzi <kevin@tummy.com> - 1.2.4-4
- Fixed ownership for /var/lib/munin in node subpackage

* Wed Dec 14 2005 Kevin Fenzi <kevin@tummy.com> - 1.2.4-3
- Fixed libdir messup to allow builds on x86_64

* Mon Dec 12 2005 Kevin Fenzi <kevin@tummy.com> - 1.2.4-2
- Removed plugins that require Net-SNMP and Sybase 

* Tue Dec  6 2005 Kevin Fenzi <kevin@tummy.com> - 1.2.4-1
- Inital cleanup for fedora-extras

* Thu Apr 21 2005 Ingvar Hagelund <ingvar@linpro.no> - 1.2.3-4
- Fixed a bug in the iostat plugin

* Wed Apr 20 2005 Ingvar Hagelund <ingvar@linpro.no> - 1.2.3-3
- Added the missing /var/run/munin

* Tue Apr 19 2005 Ingvar Hagelund <ingvar@linpro.no> - 1.2.3-2
- Removed a lot of unecessary perl dependencies

* Mon Apr 18 2005 Ingvar Hagelund <ingvar@linpro.no> - 1.2.3-1
- Sync with svn

* Tue Mar 22 2005 Ingvar Hagelund <ingvar@linpro.no> - 1.2.2-5
- Sync with release of 1.2.2
- Add some nice text from the suse specfile
- Minimal changes in the header
- Some cosmetic changes
- Added logrotate scripts (stolen from debian package)

* Sun Feb 01 2004 Ingvar Hagelund <ingvar@linpro.no>
- Sync with CVS. Version 1.0.0pre2

* Sun Jan 18 2004 Ingvar Hagelund <ingvar@linpro.no>
- Sync with CVS. Change names to munin.

* Fri Oct 31 2003 Ingvar Hagelund <ingvar@linpro.no>
- Lot of small fixes. Now builds on more RPM distros

* Wed May 21 2003 Ingvar Hagelund <ingvar@linpro.no>
- Sync with CVS
- 0.9.5-1

* Tue Apr  1 2003 Ingvar Hagelund <ingvar@linpro.no>
- Sync with CVS
- Makefile-based install of core files
- Build doc (only pod2man)

* Thu Jan  9 2003 Ingvar Hagelund <ingvar@linpro.no>
- Sync with CVS, auto rpmbuild

* Thu Jan  2 2003 Ingvar Hagelund <ingvar@linpro.no>
- Fix spec file for RedHat 8.0 and new version of lrrd

* Wed Sep  4 2002 Ingvar Hagelund <ingvar@linpro.no>
- Small bugfixes in the rpm package

* Tue Jun 18 2002 Kjetil Torgrim Homme <kjetilho@linpro.no>
- new package
