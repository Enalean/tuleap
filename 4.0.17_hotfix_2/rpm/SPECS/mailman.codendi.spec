Summary: Mailing list manager with built in Web access.
Name: mailman
Version: 2.1.9
Release: 5%{?dist}.codendi
Epoch: 3
Group: Applications/Internet
#Source0: ftp://ftp.gnu.org/pub/gnu/mailman/mailman-%{version}.tgz
Source0: http://prdownloads.sourceforge.net/mailman/mailman-%{version}.tgz
Source1: mm_cfg.py
Source3: httpd-mailman.conf
Source4: mailman.logrotate
Source5: mailman.INSTALL.REDHAT.in
Source6: mailman-crontab-edit
Source7: mailman-migrate-fhs
Patch1: mailman-2.1-multimail.patch
Patch2: mailman-2.1-build.patch
Patch3: mailman-2.1-mailmanctl-status.patch
Patch4: mailman-cron.patch
Patch5: mailman-FHS.patch
Patch6: mailman-python-compile.patch
Patch7: mailman-init.patch
Patch8: mailman-archive-reply.patch
Patch9: mailman-2.1.9-init-directrun.patch
Patch10: mailman-init-retval.patch
Patch20: mailman-2.1.9-forumml.patch

License: GPL
URL: http://www.list.org/
BuildRoot: %{_tmppath}/%{name}-root
Prereq: shadow-utils, /sbin/chkconfig, /sbin/service
Requires: vixie-cron >= 4.1-9, httpd, python >= 2.2, mktemp
BuildRequires: python-devel >= 2.2, automake

%define contentdir /var/www

# Installation directories
%define mmdir /usr/lib/%{name}
%define varmmdir /var/lib/%{name}
%define docdir /usr/share/doc/%{name}-%{version}
%define configdir /etc/%{name}
%define datadir %{varmmdir}/data
%define lockdir /var/lock/%{name}
%define logdir /var/log/%{name}
%define piddir /var/run/%{name}
%define queuedir /var/spool/%{name}
%define httpdconfdir /etc/httpd/conf.d
%define restart_flag /var/run/%{name}-restart-after-rpm-install
%define mmbuilddir %{_builddir}/%{name}-%{version}

%define httpdconffile %{name}.conf

# Now, the user and group the CGIs will expect to be run under.  This should
# match the user and group the web server is configured to run as.  The scripts
# will error out if they are invoked by any other user.
%define cgiuser    codendiadm
%define cgigroup   codendiadm

# Now, the user and group the scripts will actually execute as.
%define mmuser       mailman
#%define mmuserid     41
%define mmuserid     106
%define mmgroup      mailman
#%define mmgroupid    41
%define mmgroupid    106

# Directory/File modes & permissions
%define dirmode 2775
%define exemode 2755

# Now, the groups your mail spoolers run as.  Sendmail uses 'mail'(12)
# and postfix used to use 'nobody', but now uses 'postfix'
%define mailgroup  "mail postfix mailman nobody daemon"

# The mail wrapper program
%define mail_wrapper mailman

%description 
Mailman is software to help manage email discussion lists, much like
Majordomo and Smartmail. Unlike most similar products, Mailman gives
each mailing list a webpage, and allows users to subscribe,
unsubscribe, etc. over the Web. Even the list manager can administer
his or her list entirely from the Web. Mailman also integrates most
things people want to do with mailing lists, including archiving, mail
<-> news gateways, and so on.

Documentation can be found in: %{docdir}

When the package has finished installing, you will need to perform some
additional installation steps, these are described in:
%{docdir}/INSTALL.REDHAT

%prep
%setup -q
%patch1 -p1 -b .multimail
%patch2 -p1 -b .permissions
%patch3 -p1 -b .status
%patch4 -p1 -b .cron
%patch5 -p1 -b .FHS
%patch6 -p1 -b .python-compile
%patch7 -p1 -b .inithelp
%patch8 -p1 -b .archive-in-reply-to
%patch9 -p1 -b .init-direct-run
%patch10 -p1 -b .init-retval

%patch20 -p1 -b .forumml
cp $RPM_SOURCE_DIR/mailman.INSTALL.REDHAT.in INSTALL.REDHAT.in

%build

CFLAGS="$RPM_OPT_FLAGS"; export CFLAGS
rm -f ./configure
aclocal
autoconf
./configure \
	--prefix=%{mmdir} \
	--with-var-prefix=%{varmmdir} \
	--with-config-dir=%{configdir} \
	--with-lock-dir=%{lockdir} \
	--with-log-dir=%{logdir} \
	--with-pid-dir=%{piddir} \
	--with-queue-dir=%{queuedir} \
	--with-python=%{__python} \
	--with-mail-gid=%{mailgroup} \
	--with-cgi-id=%{cgiuser} \
	--with-cgi-gid=%{cgigroup} \
	--with-mailhost=localhost.localdomain \
	--with-urlhost=localhost.localdomain \
	--without-permcheck

function SubstituteParameters()
{
sed -e 's|@VAR_PREFIX@|%{varmmdir}|g' \
    -e 's|@VARMMDIR@|%{varmmdir}|g' \
    -e 's|@prefix@|%{mmdir}|g' \
    -e 's|@MMDIR@|%{mmdir}|g' \
    -e 's|@CONFIG_DIR@|%{configdir}|g' \
    -e 's|@DATA_DIR@|%{datadir}|g' \
    -e 's|@LOCK_DIR@|%{lockdir}|g' \
    -e 's|@LOG_DIR@|%{logdir}|g' \
    -e 's|@PID_DIR@|%{piddir}|g' \
    -e 's|@QUEUE_DIR@|%{queuedir}|g' \
    -e 's|@DOC_DIR@|%{docdir}|g' \
    -e 's|@HTTPD_CONF_DIR@|%{httpdconfdir}|g' \
    -e 's|@HTTPD_CONF_FILE@|%{httpdconffile}|g' \
    $1 > $2
}

SubstituteParameters "INSTALL.REDHAT.in" "INSTALL.REDHAT"
SubstituteParameters "%{SOURCE1}" "Mailman/mm_cfg.py.dist"
SubstituteParameters "%{SOURCE3}" "httpd-mailman.conf"
SubstituteParameters "%{SOURCE4}" "mailman.logrotate"

make
	
%install
rm -fr $RPM_BUILD_ROOT
# Normal install.
make DESTDIR=$RPM_BUILD_ROOT install
#make install prefix=$RPM_BUILD_ROOT%{mmdir} var_prefix=$RPM_BUILD_ROOT%{varmmdir}

# Install the mailman init.d script
mkdir -p $RPM_BUILD_ROOT/etc/rc.d/init.d
install $RPM_BUILD_ROOT%{mmdir}/scripts/mailman $RPM_BUILD_ROOT/etc/rc.d/init.d/mailman

# Install the mailman cron.d script
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
cat > $RPM_BUILD_ROOT/etc/cron.d/%{name} <<EOF
# DO NOT EDIT THIS FILE!
#
# Contents of this file managed by /etc/init.d/%{name}
# Master copy is %{mmdir}/cron/crontab.in
# Consult that file for documentation
EOF

# Copy the icons into the web server's icons directory.
mkdir -p $RPM_BUILD_ROOT%{contentdir}/icons
cp $RPM_BUILD_ROOT/%{mmdir}/icons/* $RPM_BUILD_ROOT%{contentdir}/icons

# Create a link to the wrapper in /etc/smrsh to allow sendmail to run it.
mkdir -p $RPM_BUILD_ROOT/etc/smrsh
ln -s %{mmdir}/mail/%{mail_wrapper} $RPM_BUILD_ROOT/etc/smrsh

# Create a link so that the config file mm_cfg.py appears in config
# directory /etc/mailman. We don't put mm_cfg.py in the config directory
# because its executable code (python file) and the security policy wants
# to keep executable code out of /etc and inside of a lib directory instead,
# and because traditionally mm_cfg.py was in the Mailman subdirectory and
# experienced mailman admins will expect to find it there. But having it 
# "appear" in the config directory is good practice and heading in the 
# right direction for FHS compliance.
mkdir -p $RPM_BUILD_ROOT%{configdir}
ln -s %{mmdir}/Mailman/mm_cfg.py $RPM_BUILD_ROOT%{configdir}

# sitelist.cfg used to live in the DATA_DIR, now as part of the 
# FHS reoraganization it lives in the CONFIG_DIR. Most of the
# documentation refers to it in its DATA_DIR location and experienced
# admins will expect to find it there, so create a link in DATA_DIR to
# point to it in CONFIG_DIR so people aren't confused.
ln -s %{configdir}/sitelist.cfg $RPM_BUILD_ROOT%{datadir}

# Install a logrotate control file.
mkdir -p $RPM_BUILD_ROOT/etc/logrotate.d
install -m644 %{mmbuilddir}/mailman.logrotate $RPM_BUILD_ROOT/etc/logrotate.d/%{name}

# Install the httpd configuration file.
install -m755 -d $RPM_BUILD_ROOT%{httpdconfdir}
install -m644 %{mmbuilddir}/httpd-mailman.conf $RPM_BUILD_ROOT%{httpdconfdir}/%{httpdconffile}

# Install the documentation files
install -m755 -d $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/INSTALL.REDHAT   $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/ACKNOWLEDGMENTS  $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/FAQ              $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/NEWS             $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/README           $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/README.CONTRIB   $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/README-I18N.en   $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/README.NETSCAPE  $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/README.USERAGENT $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/STYLEGUIDE.txt   $RPM_BUILD_ROOT%{docdir}
install -m644 %{mmbuilddir}/UPGRADING        $RPM_BUILD_ROOT%{docdir}

cp -r %{mmbuilddir}/contrib $RPM_BUILD_ROOT%{docdir}
install -m644 $RPM_SOURCE_DIR/mailman-migrate-fhs $RPM_BUILD_ROOT%{docdir}/contrib/migrate-fhs
install -m755 -d $RPM_BUILD_ROOT%{docdir}/admin
cp -r %{mmbuilddir}/admin/www $RPM_BUILD_ROOT%{docdir}/admin

# remove dir/files from $RPM_BUILD_ROOT that we aren't shipping
rm -rf $RPM_BUILD_ROOT%{varmmdir}/icons

# The file fblast confuses /usr/lib/rpm/find-requires because its an executable
# script file that does not have the interpreter as the first line, its not
# executable by itself so turn off its execute permissions
chmod 0644 $RPM_BUILD_ROOT/%{mmdir}/tests/fblast.py

%clean
rm -rf $RPM_BUILD_ROOT $RPM_BUILD_DIR/files.%{name}

%pre

# Make sure the user "mailman" exists on this system and has the correct values
if grep -q "^mailman:" /etc/group 2> /dev/null ; then
  /usr/sbin/groupmod -g %{mmgroupid} -n %{mmgroup} %{mmgroup} 2> /dev/null || :
else
  /usr/sbin/groupadd -g %{mmgroupid} %{mmgroup} 2> /dev/null || :
fi
if grep -q "^mailman:" /etc/passwd 2> /dev/null ; then
  /usr/sbin/usermod -s /sbin/nologin -c "GNU Mailing List Manager" -d %{mmdir} -u %{mmuserid} -g %{mmgroupid}       %{mmuser} 2> /dev/null || :
else
  /usr/sbin/useradd -s /sbin/nologin -c "GNU Mailing List Manager" -d %{mmdir} -u %{mmuserid} -g %{mmgroupid} -M -r %{mmuser} 2> /dev/null || :
fi

# Mailman should never be running during an install, but a package upgrade
# shouldn't silently stop the service, so if mailman was running
# we'll leave a temp file in the lock directory as a flag so in
# the post install phase we can restart it.
if [ -d %{lockdir} ]; then
  rm -f %{restart_flag}
  /sbin/service %{name} status >/dev/null 2>&1
  if [ $? -eq 0 ]; then
      touch %{restart_flag}
      /sbin/service %{name} stop >/dev/null 2>&1
  fi
fi
# rpm should not abort if last command run had non-zero exit status, exit cleanly
exit 0

%post
# We no longer use crontab, but previous versions of the spec file did, so clean up
if [ -f /var/spool/cron/%{mmuser} ]; then
  crontab -u %{mmuser} -r
fi

# This adds the proper /etc/rc*.d links for the script that runs the mailman qrunner daemon
chkconfig --add mailman

# Restart mailman if it had been running before installation
if [ -e %{restart_flag} ]; then
  rm %{restart_flag}
  /sbin/service %{name} start >/dev/null 2>&1
fi
# rpm should not abort if last command run had non-zero exit status, exit cleanly
exit 0

%preun

# if [ $1 = 0 ]' checks that this is the actual deinstallation of
# the package, as opposed to just removing the old package on upgrade.

if [ $1 = 0 ]; then
  # These statements stop the service, and remove the /etc/rc*.d links.
  /sbin/service %{name} stop >/dev/null 2>&1
  /sbin/chkconfig --del %{name}
fi
# rpm should not abort if last command run had non-zero exit status, exit cleanly
exit 0

%postun
if [ $1 = 0 ]; then
  crontab -u %{mmuser} -r 2>/dev/null
fi
# rpm should not abort if last command run had non-zero exit status, exit cleanly
exit 0

%files
%defattr(-,root,%{mmgroup})
%dir %{mmdir}
%{mmdir}/Mailman
%{mmdir}/bin
%{mmdir}/cgi-bin
%{mmdir}/cron
%{mmdir}/icons
%{mmdir}/mail
%{mmdir}/messages
%{mmdir}/pythonlib
%{mmdir}/scripts
%config(noreplace) %{mmdir}/templates
%{mmdir}/tests
%{varmmdir}
%doc %{docdir}
%attr(0755,root,root) %{contentdir}/icons
%attr(0644, root, %{mmgroup}) %config(noreplace) %verify(not md5 size mtime) %{mmdir}/Mailman/mm_cfg.py
%config(noreplace) %{httpdconfdir}/%{httpdconffile}
/etc/logrotate.d/%{name}
/etc/smrsh/%{mail_wrapper}
%attr(2775,root,%{mmgroup}) %{configdir}
%attr(0644, root, %{mmgroup}) %config(noreplace) %verify(not md5 size mtime) %{configdir}/sitelist.cfg
%attr(2775,root,%{mmgroup}) %{lockdir}
%attr(2775,root,%{mmgroup}) %{logdir}
%attr(2775,root,%{mmgroup}) %{queuedir}
%attr(2775,root,%{mmgroup}) %{piddir}
%attr(0755,root,root) /etc/rc.d/init.d/%{name}
%attr(0644,root,root) %config(noreplace) %verify(not md5 size mtime) /etc/cron.d/mailman
%attr(0644,root,%{mmgroup}) %config(noreplace) %{mmdir}/cron/crontab.in

%changelog
* Mon Oct 27 2008 Sabri Labbene <sabri.labbene@st.com> - 3:2.1.9-5.codendi
- Added a patch to enable both pipermail-internal archiving and external archiving (patch from Mohamed Chaari <mohamed.chaari@st.com>) 

* Tue Jul 1 2008 Nicolas Guerin <nicolas.guerin@xrce.xerox.com> - 3:2.1.9-4.codendi
- add codendi-specific user ids.

* Wed Feb  27 2008 Nicolas Guerin <nicolas.guerin@xerox.com> - 3:2.1.9.1-34.rhel4.6.codendi
- merged with codendi 3.4 spec file

* Wed Jan 23 2008 Tomas Smetana <tsmetana@redhat.com> - 3:2.1.9-4
- fix #252185 init script prevents proper SELinux domain transitions
- fix #242672 wrong init script return value

* Thu Oct 05 2006 David Woodhouse <dwmw2@redhat.com> - 3:2.1.9-3
- fix broken In-Reply-To: header in mailto: URL in archives (#123768)

* Sun Oct 01 2006 Jesse Keating <jkeating@redhat.com> - 3:2.1.9-2
- rebuilt for unwind info generation, broken in gcc-4.1.1-21

* Mon Sep 25 2006 Harald Hoyer <harald@redhat.com> - 3:2.1.9-1
- updated to mailman-2.1.9 which fixes bug #206607

* Wed Jul 12 2006 Jesse Keating <jkeating@redhat.com> - 3:2.1.8-3.1
- rebuild

* Tue Jun 27 2006 Florian La Roche <laroche@redhat.com> - 3:2.1.8-3
- quieten postun of crontab removal

* Mon Jun 12 2006 Harald Hoyer <harald@redhat.com> - 3:2.1.8-2
- more build requirements

* Mon May 08 2006 Harald Hoyer <harald@redhat.com> - 3:2.1.8-1
- version 2.1.8

* Fri Feb 10 2006 Jesse Keating <jkeating@redhat.com> - 3:2.1.7-1.2
- bump again for double-long bug on ppc(64)

* Tue Feb 07 2006 Jesse Keating <jkeating@redhat.com> - 3:2.1.7-1.1
- rebuilt for new gcc4.1 snapshot and glibc changes

* Tue Jan 10 2006 Harald Hoyer <harald@redhat.com> - 3:2.1.7-1
- version 2.1.7

* Fri Dec 16 2005 Jesse Keating <jkeating@redhat.com>
- rebuilt for new gcj

* Wed Dec 14 2005 Harald Hoyer <harald@redhat.com> - 3:2.1.5-36.fc4.1
- fix for bug #173139 (CVE-2005-3573 Mailman Denial of Service)

* Fri Dec 09 2005 Jesse Keating <jkeating@redhat.com>
- rebuilt

* Thu Nov 10 2005 Harald Hoyer <harald@redhat.com> - 3:2.1.6-2
- added help to the initscript (bug #162724)

* Wed Jun  8 2005 John Dennis <jdennis@redhat.com> - 3:2.1.6-1.fc4
- initial port of 2.1.6
  remove mailman-2.1.5-moderator-request.patch, present in new release
  remove mailman-2.1-CAN-2005-0202.patch,       present in new release
  remove mailman-2.1-CAN-2004-1177.patch,       present in new release

* Thu Apr 28 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-36.fc4
- fix bug #156159 insecure location of restart flag file

* Mon Mar  7 2005 John Dennis <jdennis@redhat.com> 3:2.1.5-35.fc4
- bump rev for gcc4 build

* Wed Mar  2 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-34.fc4
- fix bug #150065, provide migration script for new FHS installation

* Fri Feb 25 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-33.fc4
- fix bug #147833, CAN-2004-1177

* Mon Feb 14 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-31.fc4
- fix bug #132750, add daemon to mail-gid so courier mail server will work.
- fix bug #143008, wrong location of mailmanctl in logrotate
- fix bug #142605, init script doesn't use /var/lock/subsys

* Tue Feb  8 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-30.fc4
- fix release tag

* Tue Feb  8 2005 John Dennis <jdennis@redhat.com> - 3:2.1.5-29
- fix security vulnerability CAN-2005-0202, errata RHSA-2005:137, bug #147344

* Tue Nov  9 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-28
- fix bug #137863, buildroot path in .pyc files

* Sat Oct 16 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-26
- fix typo in install documentation
- fix error in templates/Makefile.in, bad install args, fixes bug #136001,
  thank you to Kaj J. Niemi for spotting this.

* Thu Oct 14 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-24
- more FHS changes, matches with new SELinux security policy

* Wed Sep 29 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-21
- move list data dir to /var/lib/mailman to conform to FHS
  move lock dir to /var/lock/mailman to conform to FHS
  move config dir (VAR_PREFIX/data) to /etc/mailman to conform to FHS
  Thanks to Matt Domsch for pointing this out.

* Tue Sep 28 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-20
- fix bug #132732, security policy violations,
- bump release verison
  move non-data installation files from /var/mailman to /usr/lib/mailman,
  update documentation

* Fri Sep 10 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-19
- add il18n start/stop strings to init.d script

* Fri Sep 10 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-18
- fix bug #89250, add condrestart
  also fix status return values in mailmanctl and init.d script

* Tue Sep  7 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-17
- fix bug #120930, add contents of contrib to doc area

* Tue Sep  7 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-16
- fix bug #121220, httpd config file tweaks
  add doc to INSTALL.REDHAT for selecting MTA

* Fri Sep  3 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-15
- fix bug #117615, don't overwrite user modified templates on install
  made template directory "config noreplace"

* Thu Sep  2 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-14
- add comments into the crontab files so users know the /etc/cron.d
  file is volitile and will edit the right file.
  Also make the master crontab file "config noreplace" so edits are preserved.

* Wed Sep  1 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-13
- fix bug #124208, enable mailman cron jobs from init.d rather than during installation

* Tue Aug 31 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-12
- fix bug #129920, cron jobs execute under wrong SELinux policy

* Mon Aug 30 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-11
- remove all editing of aliases file in %pre and %post, fixes #bug 125651

* Mon Aug  9 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-10
- fix bug #129492 and bug #120912
  stop using crontab to setup mailman's cron jobs,
  instead install cron script in /etc/cron.d

* Mon Aug  9 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-9
- apply patch to elminate "-1 LISTNAME moderator request(s) waiting" messages
  problem desciption here:
  http://www.python.org/cgi-bin/faqw-mm.py?req=show&file=faq03.038.htp

* Tue Jun 15 2004 Elliot Lee <sopwith@redhat.com>
- rebuilt

* Wed Jun  9 2004 John Dennis <jdennis@redhat.com> - 3:2.1.5-7
- bump rev for rebuild

* Wed Jun  9 2004 John Dennis <jdennis@redhat.com> - 3:2.1.5-6
- fix bug in pre scriplet, last command had been "service mailman stop"
  which should have been harmless if mailman was not installed except
  that it left the exit status from the script as non-zero and rpm
  aborted the install.

* Wed Jun  9 2004 John Dennis <jdennis@redhat.com> - 3:2.1.5-5
- add status reporting to init.d control script
  stop mailman during an installation
  restart mailman if it had been running prior to installation

* Mon Jun  7 2004 John Dennis <jdennis@redhat.com> - 3:2.1.5-4
- back python prereq down to 2.2, should be sufficient

* Thu May 20 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-3
- make python prereq be at least 2.3

* Tue May 18 2004 Jeremy Katz <katzj@redhat.com> 3:2.1.5-2
- rebuild 

* Mon May 17 2004 John Dennis <jdennis@redhat.com> 3:2.1.5-1
- bring up to latest 2.1.5 upstream release
  From Barry Warsaw: Mailman 2.1.5, a bug fix release that also
  contains new support for the Turkish language, and a few minor new
  features. Mailman 2.1.5 is a significant upgrade which should
  improve disk i/o performance, administrative overhead for discarding
  held spams, and the behavior of bouncing member disables.  This
  version also contains a fix for an exploit that could allow 3rd
  parties to retrieve member passwords.  It is thus highly recommended
  that all existing sitesupgrade to the latest version

* Tue May 04 2004 Warren Togami <wtogami@redhat.com> 3:2.1.4-4
- #105638 fix bytecompile and rpm -V
- postun /etc/postfix/aliases fix
- clean uninstall (no more empty dirs)
- #115378 RedirectMatch syntax fix

* Fri Feb 13 2004 Elliot Lee <sopwith@redhat.com>
- rebuilt

* Fri Jan  9 2004 John Dennis <jdennis@finch.boston.redhat.com> 3:2.1.4-1
- upgrade to new upstream release 2.1.4
- fixes bugs 106349,112851,105367,91463

* Wed Jun 04 2003 Elliot Lee <sopwith@redhat.com>
- rebuilt

* Wed May  7 2003 John Dennis <jdennis@finch.boston.redhat.com>
- bring up to next upstream release 2.1.2

* Sun May 04 2003 Florian La Roche <Florian.LaRoche@redhat.de>
- fix typo in post script: mmusr -> mmuser

* Thu Apr 24 2003 John Dennis <jdennis@finch.boston.redhat.com>
- fix bug 72004, 74483, 74484, 87856 - improper log rotation
- fix bug 88083 - mailman user/group needed to exist during build
- fix bug 88144 - wrong %file attributes on mm_cfg.py
- fix bug 89221 - mailman user not created on install
- fix bug 89250 - wrong pid file name in initscript

* Wed Mar 05 2003 Florian La Roche <Florian.LaRoche@redhat.de>
- change to /etc/rc.d/init.d as in all other rpms

* Thu Feb 20 2003 John Dennis <jdennis@finch.boston.redhat.com>
- change mailman login shell from /bin/false to /sbin/nologin

* Fri Feb 14 2003 John Dennis <jdennis@finch.boston.redhat.com>
- bring package up to 2.1.1 release, add /usr/share/doc files

* Sat Feb 01 2003 Florian La Roche <Florian.LaRoche@redhat.de>
- make the icon dir owned by root:root as in other rpms

* Fri Jan 31 2003 John Dennis <jdennis@finch.boston.redhat.com>
- various small tweaks to the spec file to make installation cleaner
- use %{__python} when compiling, redirect compile output to /dev/null,
- don't run update in %post, let the user do it, remove the .pyc files in %postun,
- add setting of MAILHOST and URLHOST to localhost.localdomain, don't let
- configure set them to the build machine.

* Mon Jan 27 2003 John Dennis <jdennis@finch.boston.redhat.com>
- add the cross site scripting (xss) security patch to version 2.1

* Fri Jan 24 2003 John Dennis <jdennis@finch.boston.redhat.com>
- do not start mailman service in %post

* Wed Jan 22 2003 Tim Powers <timp@redhat.com>
- rebuilt

* Mon Jan 20 2003 John Dennis <jdennis@finch.boston.redhat.com>
- 1) remove config patch, mailmanctl was not the right file to install in init.d,
- it needed to be scripts/mailman
- 2) rename httpd-mailman.conf to mailman.conf, since the file now lives
- in httpd/conf.d directory the http prefix is redundant and inconsistent
- with the other file names in that directory.

* Tue Jan  7 2003 John Dennis <jdennis@finch.boston.redhat.com>
- Bring package up to date with current upstream source, 2.1
- Fix several install/packaging problems that were in upstream source
- Add multiple mail group functionality
- Fix syntax error in fblast.py
- Remove the forced setting of mail host and url host in mm_cfg.py

* Tue Nov 12 2002 Tim Powers <timp@redhat.com> 2.0.13-4
- remove files from $$RPM_BUILD_ROOT that we don't intent to ship

* Thu Aug 14 2002 Nalin Dahyabhai <nalin@redhat.com> 2.0.13-3
- set MAILHOST and WWWHOST in case the configure script can't figure out the
  local host name

* Fri Aug  2 2002 Nalin Dahyabhai <nalin@redhat.com> 2.0.13-2
- rebuild

* Fri Aug  2 2002 Nalin Dahyabhai <nalin@redhat.com> 2.0.13-1
- specify log files individually, per faq wizard
- update to 2.0.13

* Wed May 22 2002 Nalin Dahyabhai <nalin@redhat.com> 2.0.11-1
- update to 2.0.11

* Fri Apr  5 2002 Nalin Dahyabhai <nalin@redhat.com> 2.0.9-1
- include README.QMAIL in with the docs (#58887)
- include README.SENDMAIL and README.EXIM in with the docs
- use an included httpd.conf file instead of listing the configuration
  directives in the %%description, which due to specspo magic might look
  wrong sometimes (part of #51324)
- interpolate the DEFAULT_HOST_NAME value in mm.cfg into both the DEFAULT_URL
  and MAILMAN_OWNER (#57987)
- move logs to /var/log/mailman, qfiles to /var/spool/mailman, rotate
  logs in the log directory (#48724)
- raise exceptions when someone tries to set the admin address for a list
  to that of the admin alias (#61468)

* Thu Apr  4 2002 Nalin Dahyabhai <nalin@redhat.com>
- fix a default permissions problem in %{_var}/mailman/archives/private,
  reported by Johannes Erdfelt
- update to 2.0.9

* Tue Apr  2 2002 Nalin Dahyabhai <nalin@redhat.com>
- make the symlink in /etc/smrsh relative

* Tue Dec 11 2001 Nalin Dahyabhai <nalin@redhat.com> 2.0.8-1
- set FQDN and URL at build-time so that they won't be set to the host the
  RPM package is built on (#59177)

* Wed Nov 28 2001 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0.8

* Sat Nov 17 2001 Florian La Roche <Florian.LaRoche@redhat.de> 2.0.7-1
- update to 2.0.7

* Wed Jul 25 2001 Nalin Dahyabhai <nalin@redhat.com> 2.0.6-1
- update to 2.0.6

* Mon Jun 25 2001 Nalin Dahyabhai <nalin@redhat.com>
- code in default user/group names/IDs

* Wed May 30 2001 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0.5
- change the default hostname from localhost to localhost.localdomain in the
  default configuration
- chuck configuration file settings other than those dependent on the host name
  (the build system's host name is not a good default)  (#32337)

* Tue Mar 13 2001 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0.3

* Tue Mar  6 2001 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0.2

* Wed Feb 21 2001 Nalin Dahyabhai <nalin@redhat.com>
- patch from Barry Warsaw (via mailman-developers) to not die on
  broken Content-Type: headers

* Tue Jan  9 2001 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0.1

* Wed Dec  6 2000 Nalin Dahyabhai <nalin@redhat.com>
- update to 2.0 final release
- move the data to %{_var}

* Fri Oct 20 2000 Nalin Dahyabhai <nalin@redhat.com>
- update to beta 6

* Thu Aug  3 2000 Nalin Dahyabhai <nalin@redhat.com>
- add note about adding FollowSymlinks so that archives work

* Wed Aug  2 2000 Nalin Dahyabhai <nalin@redhat.com>
- make the default owner root again so that root owns the docs
- update to 2.0beta5, which fixes a possible security vulnerability
- add smrsh symlink

* Mon Jul 24 2000 Prospector <prospector@redhat.com>
- rebuilt

* Wed Jul 19 2000 Nalin Dahyabhai <nalin@redhat.com>
- update to beta4
- change uid/gid to apache.apache to match apache (#13593)
- properly recompile byte-compiled versions of the scripts (#13619)
- change mailman alias from root to postmaster

* Sat Jul  1 2000 Nalin Dahyabhai <nalin@redhat.com>
- update to beta3
- drop bugs and arch patches (integrated into beta3)

* Tue Jun 27 2000 Nalin Dahyabhai <nalin@redhat.com>
- move web files to reside under %{contentdir}
- move files from /usr/share to %{_datadir}
- integrate spot-fixes from mailman lists via gnome.org

* Mon Jun 19 2000 Nalin Dahyabhai <nalin@redhat.com>
- rebuild for Power Tools

* Wed May 23 2000 Nalin Dahyabhai <nalin@redhat.com>
- Update to 2.0beta2 to pick up security fixes.
- Change equires python to list >= 1.5.2

* Mon Nov  8 1999 Bernhard Rosenkränzer <bero@redhat.com>
- 1.1

* Tue Sep 14 1999 Preston Brown <pbrown@redhat.com>
- 1.0 final.

* Tue Jun 15 1999 Preston Brown <pbrown@redhat.com>
- security fix for cookies
- moved to /usr/share/mailman

* Fri May 28 1999 Preston Brown <pbrown@redhat.com>
- fix up default values.

* Fri May 07 1999 Preston Brown <pbrown@redhat.com>
- modifications to install scripts

* Thu May 06 1999 Preston Brown <pbrown@redhat.com>
- initial RPM for SWS 3.0
