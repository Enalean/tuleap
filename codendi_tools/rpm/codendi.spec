# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME codendi
%define APP_USER codendiadm
%define APP_HOME_DIR /home/%{APP_USER}
%define APP_DIR %{_datadir}/%{APP_NAME}
%define APP_LIB_DIR /usr/lib/%{APP_NAME}
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
Requires: vixie-cron >= 4.1-9, tmpwatch
# Php and web related stuff
Requires: php, php-mysql, php-xml, php-mbstring, php-gd, php-soap, php-pear, gd
Requires: dejavu-lgc-fonts
Requires: jpgraph = 2.3.4-0.codendi
Requires: php-pecl-apc
Requires: htmlpurifier
Requires: curl
# Perl
Requires: perl, perl-DBI, perl-DBD-MySQL, perl-suidperl, perl-URI, perl-HTML-Tagset, perl-HTML-Parser, perl-libwww-perl, perl-DateManip
# Apache
Requires: httpd, mod_ssl, openssl
# Mysql Client
Requires: mysql
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql, mod_auth_mysql, nss, nscd
# Forgeupgrade
Requires: forgeupgrade >= 1.2

%description
Codendi is a web based application that address all the aspects of product development.

#
## Core component definitions
#

%package core-mailman
Summary: Mailman component for codendi
Group: Development/Tools
Version: @@CORE_MAILMAN_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Requires: mailman = 3:2.1.9-6.codendi
Provides: codendi-core-mailman = %{version}
%description core-mailman
Manage dependencies for Codendi mailman integration

%package core-subversion
Summary: Subversion component for codendi
Group: Development/Tools
Version: @@CORE_SUBVERSION_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Conflicts: cadaver
Requires: viewvc = 1.0.7-2.codendi
Requires: subversion, mod_dav_svn, subversion-perl, highlight
Provides: codendi-core-subversion = %{version}
%description core-subversion
Manage dependencies for Codendi Subversion integration

%package core-cvs
Summary: CVS component for codendi
Group: Development/Tools
Version: @@CORE_CVS_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Requires: viewvc = 1.0.7-2.codendi
Requires: xinetd, rcs, cvs = 1.11.22-5.codendi, cvsgraph, highlight
Provides: codendi-core-cvs = %{version}
%description core-cvs
Manage dependencies for Codendi CVS integration

#
## Plugins
#

%package plugin-forumml
Summary: ForumML plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_FORUMML_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, php, php-pear-Mail-mimeDecode php-pear-Mail-Mime php-pear-Mail-Mbox php-pear-Mail
Requires: codendi-core-mailman
Provides: codendi-plugin-forumml = %{version}
%description plugin-forumml
ForumML brings to Codendi a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-git
Summary: Git plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_GIT_VERSION@@
Release: 2%{?dist}
Requires: %{name} >= %{version}, git > 1.6, geshi, php-Smarty, gitolite
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

%package plugin-hudson
Summary: Hudson plugin for Codendi
Group: Development/Tools/Building
Version: @@PLUGIN_HUDSON_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-plugin-hudson = %{version}
%description plugin-hudson
Plugin to install the Codendi Hudson plugin for continuous integration

%package plugin-webdav
Summary: WebDAV plugin for Codendi
Group: Development/Tools
Version: @@PLUGIN_WEBDAV_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, Sabre_DAV = 1.0.14
Provides: codendi-plugin-webdav = %{version}
%description plugin-webdav
Plugin to access to file releases & docman though WebDAV

#
## Themes
#
%package theme-codex
Summary: Codex theme for Codendi
Group: Development/Tools
Version: @@THEME_CODEX_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-codex = %{version}
%description theme-codex
Original theme for Codendi

%package theme-codextab
Summary: CodexTab theme for Codendi
Group: Development/Tools
Version: @@THEME_CODEXTAB_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-codextab = %{version}
%description theme-codextab
CodexTab theme for Codendi

%package theme-dawn
Summary: Dawn theme for Codendi
Group: Development/Tools
Version: @@THEME_DAWN_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-dawn = %{version}
%description theme-dawn
Dawn theme for Codendi

%package theme-savannah
Summary: Savannah theme for Codendi
Group: Development/Tools
Version: @@THEME_SAVANNAH_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-savannah = %{version}
%description theme-savannah
Savannah theme for Codendi

%package theme-sttab
Summary: STTab theme for Codendi
Group: Development/Tools
Version: @@THEME_STTAB_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-sttab = %{version}
%description theme-sttab
STMicroelectronics theme for Codendi

%package theme-codexstn
Summary: CodexSTN theme for Codendi
Group: Development/Tools
Version: @@THEME_CODEXSTN_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-codexstn = %{version}
%description theme-codexstn
ST-Ericsson theme for Codendi

%package theme-steerforge
Summary: SteerForge theme for Codendi
Group: Development/Tools
Version: @@THEME_STEERFORGE_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
Provides: codendi-theme-steerforge = %{version}
%description theme-steerforge
SteerForge theme for Codendi

%package theme-tuleap
Summary: Tuleap theme
Group: Development/Tools
Version: @@THEME_TULEAP_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}
%description theme-tuleap
Tuleap theme

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
for i in codendi_tools cli plugins site-content src ChangeLog VERSION; do
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

# plugin webdav
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/plugins/webdav/locks

# plugin forumml
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/forumml

# plugin git
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitroot
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/repositories
touch $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/projects.list
%{__ln_s} var/lib/%{APP_NAME}/gitroot $RPM_BUILD_ROOT
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/templates_c
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/cache
%{__install} plugins/git/bin/gl-membership.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

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
if [ ! -d "%{APP_DATA_DIR}/gitolite/admin" ]; then
    # deploy gitolite.rc
    %{__install} -g gitolite -o gitolite -m 00644 %{APP_DIR}/plugins/git/etc/gitolite.rc.dist /usr/com/gitolite/.gitolite.rc

    # generate codendiadm ssh key for gitolite
    %{__install} -d "%{APP_HOME_DIR}/.ssh/" -g %{APP_USER} -o %{APP_USER} -m 00700
    ssh-keygen -q -t rsa -N "" -C "Tuleap / gitolite admin key" -f "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub"

    # deploy codendiadm ssh key for gitolite
    %{__cp} "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub" /tmp/
    su -c 'git config --global user.name "gitolite"' - gitolite
    su -c 'git config --global user.email gitolite@localhost' - gitolite
    %{__install} -d -g gitolite -o gitolite -m 00700 /usr/com/gitolite/.gitolite
    %{__install} -d -g gitolite -o gitolite -m 00700 /usr/com/gitolite/.gitolite/conf
    %{__install} -g gitolite -o gitolite -m 00644 %{APP_DIR}/plugins/git/etc/gitolite.conf.dist /usr/com/gitolite/.gitolite/conf/gitolite.conf
    %{__install} -g gitolite -o gitolite -m 00755 %{APP_DIR}/plugins/git/hooks/post-receive-gitolite /usr/share/gitolite/hooks/common/post-receive
    su -c 'gl-setup /tmp/id_rsa_gl-adm.pub' - gitolite

    # checkout
    %{__cat} "%{APP_DIR}/plugins/git/etc/ssh.config.dist" >> "%{APP_HOME_DIR}/.ssh/config"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/config"
    su -c 'git config --global user.name "%{APP_USER}"' - %{APP_USER}
    su -c 'git config --global user.email %{APP_USER}@localhost' - %{APP_USER}
    su -c 'cd %{APP_DATA_DIR}/gitolite; git clone gitolite@gl-adm:gitolite-admin admin' - %{APP_USER}
    %{__chmod} 750 %{APP_DATA_DIR}/gitolite/admin

    # uncomment gl-membership
    # Cannot be done before Tuleap setup. Otherwise previous clone command fails because gl-membership
    # doesn't have DB access .
    perl -pi -e 's/^# \$GL_GET_MEMBERSHIPS_PGM/\$GL_GET_MEMBERSHIPS_PGM/' /usr/com/gitolite/.gitolite.rc

    # add codendiadm to gitolite group
    if ! groups codendiadm | grep -q gitolite 2> /dev/null ; then
	usermod -a -G gitolite codendiadm
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
%{APP_DIR}/ChangeLog
%{APP_DIR}/VERSION
# Split src for src/www/themes
%dir %{APP_DIR}/src
%{APP_DIR}/src/AUTHORS
%{APP_DIR}/src/common
%{APP_DIR}/src/CONTRIBUTING
%{APP_DIR}/src/COPYING
%{APP_DIR}/src/db
%{APP_DIR}/src/etc
%{APP_DIR}/src/forgeupgrade
%{APP_DIR}/src/INSTALL
%{APP_DIR}/src/README
%{APP_DIR}/src/updates
%{APP_DIR}/src/utils
# Split src/www for src/www/themes
%dir %{APP_DIR}/src/www
%{APP_DIR}/src/www/.htaccess
%{APP_DIR}/src/www/*.php
%{APP_DIR}/src/www/account
%{APP_DIR}/src/www/admin
%{APP_DIR}/src/www/api
%{APP_DIR}/src/www/bugs
%{APP_DIR}/src/www/codendi.css
%{APP_DIR}/src/www/cvs
%{APP_DIR}/src/www/docman
%{APP_DIR}/src/www/docs
%{APP_DIR}/src/www/export
%{APP_DIR}/src/www/favicon.ico
%{APP_DIR}/src/www/file
%{APP_DIR}/src/www/forum
%{APP_DIR}/src/www/goto
%{APP_DIR}/src/www/help
%{APP_DIR}/src/www/include
%{APP_DIR}/src/www/mail
%{APP_DIR}/src/www/my
%{APP_DIR}/src/www/new
%{APP_DIR}/src/www/news
%{APP_DIR}/src/www/patch
%{APP_DIR}/src/www/people
%{APP_DIR}/src/www/pm
%{APP_DIR}/src/www/project
%{APP_DIR}/src/www/projects
%{APP_DIR}/src/www/reference
%{APP_DIR}/src/www/robots.txt
%{APP_DIR}/src/www/scripts
%{APP_DIR}/src/www/search
%{APP_DIR}/src/www/service
%{APP_DIR}/src/www/site
%{APP_DIR}/src/www/snippet
%{APP_DIR}/src/www/soap
%{APP_DIR}/src/www/softwaremap
%{APP_DIR}/src/www/stats
%{APP_DIR}/src/www/support
%{APP_DIR}/src/www/survey
%{APP_DIR}/src/www/svn
# Only "common" theme is embedded into the package
%dir %{APP_DIR}/src/www/themes
%{APP_DIR}/src/www/themes/common
%{APP_DIR}/src/www/top
%{APP_DIR}/src/www/tos
%{APP_DIR}/src/www/tracker
%{APP_DIR}/src/www/user
%{APP_DIR}/src/www/users
%{APP_DIR}/src/www/VERSION
%{APP_DIR}/src/www/widgets
%{APP_DIR}/src/www/wiki
# Plugins dir
%dir %{APP_DIR}/plugins
%{APP_DIR}/plugins/admindelegation
%{APP_DIR}/plugins/docman
%{APP_DIR}/plugins/graphontrackers
%{APP_DIR}/plugins/pluginsadministration
%{APP_DIR}/plugins/projectlinks
%{APP_DIR}/plugins/serverupdate
%{APP_DIR}/plugins/statistics
%{APP_DIR}/plugins/tracker_date_reminder
%{APP_DIR}/plugins/userlog
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


#
# Core
#
%files core-mailman
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/CORE_MAILMAN_VERSION

%files core-subversion
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/CORE_SUBVERSION_VERSION

%files core-cvs
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/CORE_CVS_VERSION

#
# Plugins
#
%files plugin-forumml
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/forumml
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/mail_2_DB.pl
%attr(00750,%{APP_USER},%{APP_USER}) %{_localstatedir}/run/forumml

%files plugin-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/git
%dir %{APP_DATA_DIR}/gitroot
%dir %{APP_DATA_DIR}/gitolite
%attr(00770,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/repositories
%attr(00660,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/projects.list
%attr(-,root,root) /gitroot
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gl-membership.pl

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

%files plugin-hudson
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson

%files plugin-webdav
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav

#
# Themes
#
%files theme-codex
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/CodeX

%files theme-codextab
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/CodeXTab

%files theme-dawn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/Dawn

%files theme-savannah
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/savannah

%files theme-sttab
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/STTab

%files theme-codexstn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/CodexSTN

%files theme-steerforge
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/SteerForge

%files theme-tuleap
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/Tuleap

#%doc
#%config

%changelog
* Mon Oct 11 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Package plugins that matters (solve dependencies install issues).

* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.

