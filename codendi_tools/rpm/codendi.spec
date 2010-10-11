# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME codendi
%define APP_USER codendiadm
%define APP_DIR %{_datadir}/%{APP_NAME}
%define APP_LIB_DIR %{_libdir}/%{APP_NAME}
%define APP_LIBBIN_DIR %{APP_LIB_DIR}/bin
%define APP_DATA_DIR %{_localstatedir}/lib/%{APP_NAME}
%define APP_CACHE_DIR %{_localstatedir}/tmp/%{APP_NAME}_cache

# Check values in Codendi's mailman .spec file
%define mailman_groupid  106
%define mailman_group    mailman
%define mailman_userid   106
%define mailman_user     mailman
%define app_group        codendiadm
%define app_user         codendiadm
%define dummy_group      dummy
%define dummy_user       dummy
%define ftpadmin_group   ftpadmin
%define ftpadmin_user    ftpadmin
%define ftp_group        ftp
%define ftp_user         ftp

Summary: Codendi forge
Name: %{PKG_NAME}
Provides: codendi = %{version}
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@st.com>

#Prereq: /sbin/chkconfig, /sbin/service

# Package cutting is still a bit a mess so do not force dependency on custmization package yet
#Requires: %{PKG_NAME}-customization
Requires: vixie-cron >= 4.1-9

Requires: forgeupgrade >= 1.2

%description
Codendi is a web based application that address all the aspects of product development.

%package plugin-forumml
Summary: ForumML plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_FORUMML_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, php, php-pear-Mail-mimeDecode php-pear-Mail-Mime php-pear-Mail-Mbox php-pear-Mail
#Requires: mailman-2.1.9-5.codendi
Provides: codendi-plugin-forumml = %{version}
%description plugin-forumml
ForumML brings to Codendi a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-git
Summary: Git plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_GIT_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, git, geshi, php-Smarty
Provides: codendi-plugin-git = %{version}
%description plugin-git
Integration of git distributed software configuration management tool together
with Codendi

%package plugin-svntodimensions
Summary: Codendi plugin for svntodimensions
Group: Development/Tools
Version: @@PLUGIN_SVNTODIMENSIONS_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-plugin-svntodimensions = %{version}
%description plugin-svntodimensions
Codendi plugin for svntodimensions

%package plugin-cvstodimensions
Summary: Codendi plugin for cvstodimensions
Group: Development/Tools
Version: @@PLUGIN_CVSTODIMENSIONS_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-plugin-cvstodimensions = %{version}
%description plugin-cvstodimensions
Codendi plugin for cvstodimensions

%package plugin-docmanwatermark
Summary: Codendi plugin for PDF watermarking
Group: Development/Tools
Version: @@PLUGIN_DOCMANWATERMARK_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, php-zendframework = 1.8.1
# Requires: codendi-plugin-docman
Provides: codendi-plugin-docmanwatermark = %{version}
%description plugin-docmanwatermark
PDF Watermark plugin. Provide the possibility to add a customizable banner to
PDF file uploaded in Docman

%package plugin-ldap
Summary: Codendi plugin to manage LDAP integration
Group: Development/Tools
Version: @@PLUGIN_LDAP_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, php-ldap, perl-LDAP, python-ldap
Provides: codendi-plugin-ldap = %{version}
%description plugin-ldap
LDAP Plugin for Codendi. Provides LDAP information, LDAP
authentication, user and group management.

%package plugin-im
Summary: Instant Messaging Plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_IM_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, openfire, openfire-codendi-plugins
Provides: codendi-plugin-im = %{version}
%description plugin-im
Provides instant messaging capabilities, based on a Jabber/XMPP server.

%package plugin-jri
Summary: Codendi Java Remote Interface plugin
Group: Development/Tools
Version: @@PLUGIN_JRI_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, codendi-jri
Provides: codendi-plugin-jri = %{version}
%description plugin-jri
Codendi Java Remote Interface: the java API for Codendi

%package plugin-eclipse
Summary: Eclipse plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_ECLIPSE_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, codendi-eclipse
Provides: codendi-plugin-eclipse = %{version}
%description plugin-eclipse
Plugin to install the Codendi Eclipse plugin and access the documentation


#
# Package setup
%prep
%setup -q

#
# Build
%build
# Nothing to do

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

#
# Install codendi application
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}
for i in codendi_tools cli plugins site-content src ST-ChangeLog ST-VERSION; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{APP_DIR}
done
# Remove old scripts: not used and add unneeded perl depedencies to the package
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanUploader.pl
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanLegacyDownloader.pl
# Hard-coded perl include that breaks packging
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/plugins/forumml/bin/ml_arch_2_DB.pl
# Remove salome plugin because not used and breaks SELinux postinstall fix (wrong symlink)
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/salome
# No need of template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/template
# Remove organization_logo (provided by codendi_customization package)
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/images/organization_logo.png

#
# Install Codendi executables
%{__install} -d $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/gotohell $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/backup_job $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/backup_subversion.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/log_accum $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/commit_prep $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/cvssh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/cvssh-restricted $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/commit-email.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/codendi_svn_pre_commit.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/fileforge.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/fileforge
%{__install} plugins/forumml/bin/mail_2_DB.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

# Install init.d script
%{__install} -d $RPM_BUILD_ROOT/etc/rc.d/init.d
%{__install} src/utils/init.d/%{APP_NAME} $RPM_BUILD_ROOT/etc/rc.d/init.d/

# Install cron.d script
%{__install} -d $RPM_BUILD_ROOT/etc/cron.d
%{__install} src/utils/cron.d/codendi-stop $RPM_BUILD_ROOT/etc/cron.d/%{APP_NAME}

# Cache dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}

# plugin forumml
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/forumml

# plugin git
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitroot
%{__ln_s} var/lib/%{APP_NAME}/gitroot $RPM_BUILD_ROOT
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/templates_c
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/cache


##
## On package install
##

#
#
#
%pre
if [ "$1" -eq "1" ]; then
    # Install

    #
    # Make sure mandatory unix groups exist
    #

    # mailman
    if grep -q "^%{mailman_group}:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupmod -g %{mailman_groupid} -n %{mailman_group} %{mailman_group} 2> /dev/null || :
    else
        /usr/sbin/groupadd -g %{mailman_groupid} %{mailman_group} 2> /dev/null || :
    fi
    # codendiadm
    if ! grep -q "^%{app_group}:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupadd -r %{app_group} 2> /dev/null || :
    fi
    # dummy
    if ! grep -q "^%{dummy_group}:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupadd -r %{dummy_group} 2> /dev/null || :
    fi
    # ftpadmin
    if ! grep -q "^%{ftpadmin_group}:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupadd -r %{ftpadmin_group} 2> /dev/null || :
    fi
    # ftp
    if ! grep -q "^%{ftp_group}:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupadd -r %{ftp_group} 2> /dev/null || :
    fi

    # Make suser mandatory unix users exist

    # codendiadm
    # mailman group needed to write in /var/log/mailman/ directory
    if id %{app_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Owner of Codendi directories'    -d '/home/codendiadm'    -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group},%{mailman_group} %{app_user}
    else
        /usr/sbin/useradd -c 'Owner of Codendi directories' -M -d '/home/codendiadm' -r -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group},%{mailman_group} %{app_user}
    fi
    # mailman
    if id %{mailman_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Owner of Mailman directories'    -d '/usr/lib/mailman' -u %{mailman_userid} -g %{mailman_groupid} -s '/sbin/nologin' %{mailman_user}
    else
        /usr/sbin/useradd -c 'Owner of Mailman directories' -M -d '/usr/lib/mailman' -u %{mailman_userid} -g %{mailman_groupid} -s '/sbin/nologin' %{mailman_user}
    fi
    # ftpadmin
    if id %{ftpadmin_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'FTP Administrator'    -d '/var/lib/codendi/ftp'    -g %{ftpadmin_group} %{ftpadmin_user}
    else
        /usr/sbin/useradd -c 'FTP Administrator' -M -d '/var/lib/codendi/ftp' -r -g %{ftpadmin_group} %{ftpadmin_user}
    fi
    # ftp
    if id %{ftp_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'FTP User'    -d '/var/lib/codendi/ftp'    -g %{ftp_group} %{ftp_user}
    else
        /usr/sbin/useradd -c 'FTP User' -M -d '/var/lib/codendi/ftp' -r -g %{ftp_group} %{ftp_user}
    fi
    # dummy
    if id %{dummy_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Dummy Codendi User'    -d '/var/lib/codendi/dumps'    -g %{dummy_group} %{dummy_user}
    else
        /usr/sbin/useradd -c 'Dummy Codendi User' -M -d '/var/lib/codendi/dumps' -r -g %{dummy_group} %{dummy_user}
    fi
else
    # Stop the services
    #/etc/init.d/codendi stop
    #/sbin/service httpd stop

    true
fi

#
#
#
%post
if [ "$1" -eq "1" ]; then
    # Install
    true

else
    # Upgrade
    # Launch forgeupgrade
    true

    # Re-generate language files
    %{APP_DIR}/src/utils/php-launcher.sh %{APP_DIR}/src/utils/generate_language_files.php
fi

# In any cases fix the context
/usr/bin/chcon -R root:object_r:httpd_sys_content_t $RPM_BUILD_ROOT/%{APP_DIR}

# This adds the proper /etc/rc*.d links for the script that runs the codendi backend
#/sbin/chkconfig --add %{APP_NAME}

# Restart the services
#/sbin/service httpd start
#/etc/init.d/codendi start

#
# Post install of git plugin
%post plugin-git
if [ "$1" -eq "1" ]; then
    # During install
    if ! %{__grep} /usr/bin/git-shell /etc/shells &> /dev/null; then
        echo /usr/bin/git-shell >> /etc/shells
    fi
fi

##
## On package un-install
##

#
#
#
#%preun

# if [ $1 = 0 ]' checks that this is the actual deinstallation of
# the package, as opposed to just removing the old package on upgrade.

#if [ $1 = 0 ]; then
    # These statements stop the service, and remove the /etc/rc*.d links.
    #/sbin/service %{APP_NAME} stop >/dev/null 2>&1
    #/sbin/chkconfig --del %{APP_NAME}
#    true
#fi
# rpm should not abort if last command run had non-zero exit status, exit cleanly
#exit 0


#
#
#
%clean
%{__rm} -rf $RPM_BUILD_ROOT


#
#
#
%files
%defattr(-,%{APP_USER},%{APP_USER},-)
%dir %{APP_DIR}
%{APP_DIR}/codendi_tools
%{APP_DIR}/cli
%{APP_DIR}/site-content
%{APP_DIR}/src
%{APP_DIR}/ST-ChangeLog
%{APP_DIR}/ST-VERSION
%dir %{APP_DIR}/plugins
%{APP_DIR}/plugins/admindelegation
%{APP_DIR}/plugins/docman
%{APP_DIR}/plugins/graphontrackers
%{APP_DIR}/plugins/hudson
%{APP_DIR}/plugins/pluginsadministration
%{APP_DIR}/plugins/projectlinks
%{APP_DIR}/plugins/serverupdate
%{APP_DIR}/plugins/statistics
%{APP_DIR}/plugins/tracker_date_reminder
%{APP_DIR}/plugins/userlog
%{APP_DIR}/plugins/webdav
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIB_DIR}
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIBBIN_DIR}
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gotohell
%attr(00740,root,root) %{APP_LIBBIN_DIR}/backup_job
%attr(00740,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/backup_subversion.sh
%attr(04755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/log_accum
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/commit_prep
%attr(00755,root,root) %{APP_LIBBIN_DIR}/cvssh
%attr(00755,root,root) %{APP_LIBBIN_DIR}/cvssh-restricted
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/commit-email.pl
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/codendi_svn_pre_commit.php
%attr(04755,root,root) %{APP_LIBBIN_DIR}/fileforge
%attr(00755,root,root) /etc/rc.d/init.d/%{APP_NAME}
%attr(00644,root,root) /etc/cron.d/%{APP_NAME}
%dir %{APP_CACHE_DIR}

%files plugin-forumml
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/forumml
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/mail_2_DB.pl
%attr(00750,%{APP_USER},%{APP_USER}) %{_localstatedir}/run/forumml

%files plugin-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/git
%dir %{APP_DATA_DIR}/gitroot
%attr(-,root,root) /gitroot
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty

%files plugin-svntodimensions
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/svntodimensions

%files plugin-cvstodimensions
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/cvstodimensions

%files plugin-docmanwatermark
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/docmanwatermark

%files plugin-ldap
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/ldap

%files plugin-im
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/IM

%files plugin-jri
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/codendijri

%files plugin-eclipse
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/eclipse

#%doc
#%config

%changelog
* Mon Oct 11 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Package plugins that matters (solve dependencies install issues).

* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.

