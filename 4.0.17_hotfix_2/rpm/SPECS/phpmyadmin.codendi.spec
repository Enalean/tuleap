# $Id: phpmyadmin.spec 6090 2008-01-12 14:29:54Z ae $
# Authority: jim

%define real_name phpMyAdmin

Summary: Web application to manage MySQL
Name: phpmyadmin
Version: 2.11.9.5
Release: 0.codendi
License: GPL
Group: Applications/Internet
URL: http://www.phpmyadmin.net/

Packager: Dag Wieers <dag@wieers.com>
Vendor: Dag Apt Repository, http://dag.wieers.com/apt/

Source: http://dl.sf.net/phpmyadmin/phpMyAdmin-%{version}-all-languages-utf-8-only.tar.bz2
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

BuildArch: noarch
Requires: php-mysql >= 4.1.0
Requires: webserver
Obsoletes: phpMyAdmin <= %{version}-%{release}
Provides: phpMyAdmin = %{version}-%{release}

%description
phpMyAdmin can manage a whole MySQL server (needs a super-user) as well as a
single database. To accomplish the latter you'll need a properly set up MySQL
user who can read/write only the desired database. It's up to you to look up
the appropriate part in the MySQL manual.

%prep
%setup -n %{real_name}-%{version}-all-languages-utf-8-only

%{__cat} <<EOF >phpmyadmin.conf
#
#  %{summary}
#

<Directory "%{_datadir}/phpmyadmin">
  Order Deny,Allow
  Deny from all
  Allow from all
</Directory>

Alias /phpmyadmin %{_datadir}/phpmyadmin
Alias /phpMyAdmin %{_datadir}/phpmyadmin
Alias /mysqladmin %{_datadir}/phpmyadmin
EOF

%{__cat} <<EOF >config-codendi.inc.php
<?php
/*
 * Codendi phpMyAdmin configuration file
 */

/* Servers configuration */
\$i = 0;

\$i++;
\$cfg['Servers'][\$i]['host'] = 'localhost';
\$cfg['Servers'][\$i]['connect_type'] = 'tcp';
\$cfg['Servers'][\$i]['compress'] = false;
\$cfg['Servers'][\$i]['auth_type'] = 'http';
\$cfg['Servers'][\$i]['verbose'] = 'Codendi';
\$cfg['Servers'][\$i]['only_db'] = array('codendi', '*');
\$cfg['Servers'][\$i]['extension'] = 'mysqli';

/* End of servers configuration */

?>
EOF

%build

%install
%{__rm} -rf %{buildroot}

%{__install} -d -m0755 %{buildroot}%{_datadir}/phpmyadmin/
%{__cp} -av *.{php,html,css,ico} %{buildroot}%{_datadir}/phpmyadmin/
%{__cp} -av contrib/ js/ lang/ libraries/ pmd/ scripts/ test/ themes/ %{buildroot}%{_datadir}/phpmyadmin/

%{__install} -Dp -m0644 config-codendi.inc.php %{buildroot}%{_datadir}/phpmyadmin/config.inc.php
%{__install} -Dp -m0644 phpmyadmin.conf %{buildroot}%{_sysconfdir}/httpd/conf.d/phpmyadmin.conf

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
%doc ChangeLog CREDITS Documentation.* INSTALL LICENSE README RELEASE-DATE* TODO
%config(noreplace) %{_sysconfdir}/httpd/conf.d/phpmyadmin.conf
%{_datadir}/phpmyadmin/

%defattr(0640, root, apache, 0755)
%config(noreplace) %{_datadir}/phpmyadmin/config.inc.php

%changelog
* Mon Apr 27 2009 Nicolas Guerin <nicolas.guerin@xrce.xerox.com> - 2.11.9.5-0.codendi
- Updated to release 2.11.9.5

* Thu Mar 06 2008 Nicolas Guerin <nicolas.guerin@xrce.xerox.com> - 2.11.5-1.codendi
- Adapted to Codendi

* Sun Mar 02 2008 Dag Wieers <dag@wieers.com> - 2.11.5-1 - 6090+/ae
- Updated to release 2.11.5.

* Sat Jan 12 2008 Jim <quien-sabe@metaorg.com> - 2.11.4-1
- Updated to release 2.11.4.

* Tue Dec 11 2007 Dag Wieers <dag@wieers.com> - 2.11.3-1
- Updated to release 2.11.3.

* Tue Nov 27 2007 Dag Wieers <dag@wieers.com> - 2.11.2.2-1
- Updated to release 2.11.2.2.

* Wed Oct 17 2007 Dag Wieers <dag@wieers.com> - 2.11.1.2-1
- Updated to release 2.11.1.2.

* Sat Sep 22 2007 Dag Wieers <dag@wieers.com> - 2.11.1-1
- Updated to release 2.11.1.

* Thu Aug 23 2007 Jim <quien-sabe@metaorg.com> - 2.11.0-1
- Updated to release 2.11.0.

* Thu Jul 26 2007 Dag Wieers <dag@wieers.com> - 2.10.3-2
- Cosmetic cleanup.

* Fri Jul 20 2007 Jim <quien-sabe@metaorg.com> - 2.10.3-1
- Updated to latest upstream version

* Sun Jun 17 2007 Jim <quien-sabe@metaorg.com> - 2.10.2-1
- Updated to latest upstream version

* Tue Mar 6 2007 Jim <quien-sabe@metaorg.com> - 2.10.0.2-1
- Updated to latest upstream version

* Tue Jan 16 2007 Jim <quien-sabe@metaorg.com> - 2.9.2-1
- Updated to latest upstream version

* Tue Jan 16 2007 Jim <quien-sabe@metaorg.com> - 2.9.2-1
- Updated to latest upstream version

* Mon Nov 20 2006 Jim <quien-sabe@metaorg.com> - 2.9.1.1-1
- Updated to latest upstream version

* Fri Nov 10 2006 Jim <quien-sabe@metaorg.com> - 2.9.1-1
- Updated to latest upstream version

* Sun Nov 5 2006 Jim <quien-sabe@metaorg.com> - 2.9.0.3-1
- Updated to latest upstream version

* Wed Oct 4 2006 Jim <quien-sabe@metaorg.com> - 2.9.0.2-1
- Updated to latest upstream version

* Mon Oct 2 2006 Jim <quien-sabe@metaorg.com> - 2.9.0.1-1
- Updated to latest upstream version

* Wed Sep 20 2006 Jim <quien-sabe@metaorg.com> - 2.9.0-1
- Updated to latest upstream version

* Tue Aug 22 2006 Jim <quien-sabe@metaorg.com> - 2.8.2.4-1
- Updated to latest upstream version

* Wed Aug 2 2006 Jim <quien-sabe@metaorg.com> - 2.8.2.1-1
- Updated to latest upstream version

* Sun May 21 2006 Jim <quien-sabe@metaorg.com> - 2.8.1-2
- Fixed an issue with the apache conf file

* Sat May 20 2006 Jim <quien-sabe@metaorg.com> - 2.8.1-1
- Updated to lastest upstream version

* Fri Apr 7 2006 Jim Richardson <devlop@aidant.net> - 2.8.0.3-1
- Initial package.
