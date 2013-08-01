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
%define APP_PHP_INCLUDE_PATH /usr/share/pear:%{APP_DIR}/src/www/include:%{APP_DIR}/src:.

# Check values in Tuleap's mailman .spec file
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

Summary: The Tuleap forge
Name: %{PKG_NAME}
Provides: codendi = %{version}
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@st.com>

AutoReqProv: no 
#Prereq: /sbin/chkconfig, /sbin/service

# Package cutting is still a bit a mess so do not force dependency on custmization package yet
#Requires: %{PKG_NAME}-customization
Requires: vixie-cron >= 4.1-9, tmpwatch
# Php and web related stuff
Requires: %{php_base}, %{php_base}-mysql, %{php_base}-xml, %{php_base}-mbstring, %{php_base}-gd, %{php_base}-soap, %{php_base}-pear, gd
%if %{php_base} == php53
# contains posix* functions
Requires: %{php_base}-process
%endif
Requires: dejavu-lgc-fonts
%if %{PKG_NAME} == codendi_st
Requires: jpgraph
# = 2.3.4-0.codendi
Provides: codendi
%else
Provides: tuleap
%if %{php_base} == php
Requires: jpgraph-%{PKG_NAME}
Requires: htmlpurifier >= 4.5
Requires: %{php_base}-pecl-json
%else
Requires: %{php_base}-jpgraph-%{PKG_NAME}
Requires: %{php_base}-htmlpurifier >= 4.5
%endif
%endif
Requires: %{php_base}-pecl-apc
Requires: curl
Requires: %{php_base}-zendframework = 1.8.1
# Perl
Requires: perl, perl-DBI, perl-DBD-MySQL, perl-suidperl, perl-URI, perl-HTML-Tagset, perl-HTML-Parser, perl-libwww-perl, perl-DateManip
# Automatic perl dependencies
#perl(APR::Pool)  perl(APR::Table)  perl(Apache2::Access)  perl(Apache2::Const)  perl(Apache2::Module)  perl(Apache2::RequestRec)  perl(Apache2::RequestUtil)  perl(Apache2::ServerRec)  perl(Carp)  perl(Cwd)  perl(DBI)  perl(Digest::MD5)  perl(Encode)  perl(File::Basename)  perl(File::Copy)  perl(HTTP::Request::Common)  perl(LWP::UserAgent)  perl(Net::LDAP)  perl(POSIX)  perl(Time::Local)  perl(strict)  perl(subs)  perl(vars)  perl(warnings) 
# Apache
Requires: httpd, mod_ssl, openssl
# Mysql Client
Requires: mysql
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql, mod_auth_mysql, nss, nscd
# Forgeupgrade
Requires: forgeupgrade >= 1.2
# MIME libs
Requires: shared-mime-info

%description
Tuleap is a web based application that address all the aspects of product development.

#
## Core component definitions
#

%package install
Summary: Initial setup of the platform
Group: Development/Tools
Version: @@VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, redhat-lsb
%description install
This package contains the setup script for the %{PKG_NAME} platform.
It is meant to be install at the initial setup of the platform and
recommanded to uninstall it after.

%package core-mailman
Summary: Mailman component for Tuleap
Group: Development/Tools
Version: @@CORE_MAILMAN_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%if %{PKG_NAME} == codendi_st
Requires: mailman
# = 3:2.1.9-6.codendi
Provides: codendi-core-mailman
%else
Requires: mailman-%{PKG_NAME}
Provides: tuleap-core-mailman
%endif
%description core-mailman
Manage dependencies for Tuleap mailman integration

%package core-subversion
Summary: Subversion component for Tuleap
Group: Development/Tools
Version: @@CORE_SUBVERSION_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, subversion, mod_dav_svn, subversion-perl, highlight
%if %{PKG_NAME} == codendi_st
Requires: viewvc
# = 1.0.7-2.codendi
Provides: codendi-core-subversion
%else
Requires: viewvc-%{PKG_NAME}
Provides: tuleap-core-subversion
%endif
%description core-subversion
Manage dependencies for Tuleap Subversion integration

%package core-subversion-modperl
Summary: Subversion with mod_perl authentication
Group: Development/Tools
Version: 1.0
Release: 1%{?dist}
Requires: %{PKG_NAME}-core-subversion, mod_perl
%description core-subversion-modperl
Provides authentication for Subversion component based on mod_perl rather than
mod_mysql.
This module might help server with big subversion usage. mod_mysql + mod_svn
seems to have memory leak issues.

%package core-cvs
Summary: CVS component for Tuleap
Group: Development/Tools
Version: @@CORE_CVS_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, xinetd, rcs, cvsgraph, highlight
%if %{PKG_NAME} == codendi_st
Requires: viewvc
# = 1.0.7-2.codendi
Requires: cvs
# = 1.11.22-5.codendi
Provides: codendi-core-cvs
%else
Requires: viewvc-%{PKG_NAME}
Requires: cvs-%{PKG_NAME}
Provides: tuleap-core-cvs
%endif
%description core-cvs
Manage dependencies for Tuleap CVS integration

#
## Plugins
#

%package plugin-forumml
Summary: ForumML plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_FORUMML_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, %{php_base}-pear-Mail-mimeDecode %{php_base}-pear-Mail-Mime %{php_base}-pear-Mail-Mbox %{php_base}-pear-Mail
%if %{PKG_NAME} == codendi_st
Requires: codendi-core-mailman
Provides: codendi-plugin-forumml = %{version}
%else
Requires: tuleap-core-mailman
Provides: tuleap-plugin-forumml = %{version}
%endif
%description plugin-forumml
ForumML brings to Tuleap a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-git
Summary: Git plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_GIT_VERSION@@
Release: 1%{?dist}
Requires: %{name} >= %{version}, git > 1.6, %{php_base}-Smarty, gitolite
%if %{php_base} == php
Requires: geshi
%else
Requires: %{php_base}-geshi
%endif
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-git = %{version}
%else
Provides: tuleap-plugin-git = %{version}
%endif
%description plugin-git
Integration of git distributed software configuration management tool together
with Tuleap

%package plugin-docmanwatermark
Summary: Tuleap plugin for PDF watermarking
Group: Development/Tools
Version: @@PLUGIN_DOCMANWATERMARK_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-docmanwatermark = %{version}
%else
Provides: tuleap-plugin-docmanwatermark = %{version}
%endif
%description plugin-docmanwatermark
PDF Watermark plugin. Provide the possibility to add a customizable banner to
PDF file uploaded in Docman

%package plugin-ldap
Summary: Tuleap plugin to manage LDAP integration
Group: Development/Tools
Version: @@PLUGIN_LDAP_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, %{php_base}-ldap, perl-LDAP, python-ldap
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-ldap = %{version}
%else
Provides: tuleap-plugin-ldap = %{version}
%endif
%description plugin-ldap
LDAP Plugin for Tuleap. Provides LDAP information, LDAP
authentication, user and group management.

%package plugin-im
Summary: Instant Messaging Plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_IM_VERSION@@
Release: 1%{?dist}
AutoReqProv: no
Requires: %{PKG_NAME}, openfire, openfire-codendi-plugins
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-im = %{version}
%else
Provides: tuleap-plugin-im = %{version}
%endif
%description plugin-im
Provides instant messaging capabilities, based on a Jabber/XMPP server.

%package plugin-hudson
Summary: Hudson plugin for Tuleap
Group: Development/Tools/Building
Version: @@PLUGIN_HUDSON_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-hudson = %{version}
%else
Provides: tuleap-plugin-hudson = %{version}
%endif
%description plugin-hudson
Plugin to install the Tuleap Hudson plugin for continuous integration

%package plugin-webdav
Summary: WebDAV plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_WEBDAV_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
Requires: %{php_base}-sabredav = 1.4.4
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-webdav = %{version}
%else
Provides: tuleap-plugin-webdav = %{version}
%endif
%description plugin-webdav
Plugin to access to file releases & docman though WebDAV

%package plugin-requesthelp
Summary: Insert Remedy tickets using Codex
Group: Development/Tools
Version: @@PLUGIN_REQUESTHELP_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, %{php_base}-pecl-oci8 = 1.4.5
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-requesthelp = %{version}
%else
Provides: tuleap-plugin-requesthelp = %{version}
%endif
%description plugin-requesthelp
Plugin to insert Remedy tickets using Codex (used for ST only)

%package plugin-tracker
Summary: Tracker v5 for Tuleap
Group: Development/Tools
Version: @@PLUGIN_TRACKER_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}, libxslt
%description plugin-tracker
New tracker generation for Tuleap.

%package plugin-graphontrackers
Summary: Graphs for Tracker v5
Group: Development/Tools
Version: @@PLUGIN_GRAPHONTRACKERS_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-tracker >= 0.8.4
%description plugin-graphontrackers
Graphs for new tracker generation

%package plugin-cardwall
Summary: Graphs for Tracker v5
Group: Development/Tools
Version: @@PLUGIN_CARDWALL_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-tracker
%description plugin-cardwall
Fancy cardwall output on top of Tracker v5

%package plugin-agiledashboard
Summary: Agile dashboard
Group: Development/Tools
Version: @@PLUGIN_AGILEDASHBOARD_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-tracker, %{PKG_NAME}-plugin-cardwall
%description plugin-agiledashboard
Agile Dashboard aims to provide an nice integration of Scrum/Kanban
tool on top of Tracker.

%package plugin-fulltextsearch
Summary: Full-Text Search
Group: Development/Tools
Version: @@PLUGIN_FULLTEXTSEARCH_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
Requires: %{php_base}-elasticsearch
%description plugin-fulltextsearch
Allows documents of the docman to be searched in a full-text manner.

%package plugin-archivedeleteditems
Summary: Archiving plugin
Group: Development/Tools
Version: @@PLUGIN_ARCHIVEDELETEDITEMS_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%if %{PKG_NAME} == codendi_st
Provides: codendi-plugin-archivedeleteditems = %{version}
%else
Provides: tuleap-plugin-archivedeleteditems = %{version}
%endif
%description plugin-archivedeleteditems
Archive deleted items before purging them from filesystem

%package plugin-fusionforge_compat
Summary: FusionForge Compatibility
Group: Development/Tools
Version: @@PLUGIN_FUSIONFORGE_COMPAT_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description plugin-fusionforge_compat
Allows some fusionforge plugins to be installed in a Tuleap instance.

%package plugin-doaprdf
Summary: Doap
Group: Development/Tools
Version: @@PLUGIN_DOAPRDF_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-fusionforge_compat
%description plugin-doaprdf
This plugin provides DOAP RDF documents for projects on /projects URLs with
content-negociation (application/rdf+xml).

%package plugin-foafprofiles
Summary: Foaf Profiles
Group: Development/Tools
Version: @@PLUGIN_FOAFPROFILES_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-fusionforge_compat
%description plugin-foafprofiles
This plugin provides FOAFPROFILES for projects user (application/rdf+xml).

%package plugin-admssw
Summary: Adms.sw
Group: Development/Tools
Version: @@PLUGIN_ADMSSW_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-doaprdf
Requires: %{php_base}-pear-HTTP
%description plugin-admssw
This plugin provides ADMS.SW additions to the DOAP RDF documents for projects on
/projects URLs with content-negociation (application/rdf+xml).

%package plugin-boomerang
Summary: Boomerang plugin
Group: Development/Tools
Version: @@PLUGIN_BOOMERANG_VERSION@@
Release: 1%{?dist}
%description plugin-boomerang
Allow performances evaluation in Tuleap.

%if %{php_base} == php53
%package plugin-mediawiki
Summary: Mediawiki plugin
Group: Development/Tools
Version: @@PLUGIN_MEDIAWIKI_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}-plugin-fusionforge_compat
Requires: php53-mediawiki-tuleap
%description plugin-mediawiki
This plugin provides Mediawiki integration in Tuleap.
%endif

#
## Themes
#
%package theme-codex
Summary: Codex theme for Tuleap
Group: Development/Tools
Version: @@THEME_CODEX_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-codex
Original theme for Tuleap

%package theme-codextab
Summary: CodexTab theme for Tuleap
Group: Development/Tools
Version: @@THEME_CODEXTAB_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-codextab
CodexTab theme for Tuleap

%package theme-dawn
Summary: Dawn theme for Tuleap
Group: Development/Tools
Version: @@THEME_DAWN_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-dawn
Dawn theme for Tuleap

%package theme-savannah
Summary: Savannah theme for Tuleap
Group: Development/Tools
Version: @@THEME_SAVANNAH_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-savannah
Savannah theme for Tuleap

%package theme-sttab
Summary: STTab theme for Tuleap
Group: Development/Tools
Version: @@THEME_STTAB_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-sttab
STMicroelectronics theme for Tuleap

%package theme-codexstn
Summary: CodexSTN theme for Tuleap
Group: Development/Tools
Version: @@THEME_CODEXSTN_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-codexstn
ST-Ericsson theme for Tuleap

%package theme-steerforge
Summary: SteerForge theme for Tuleap
Group: Development/Tools
Version: @@THEME_STEERFORGE_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-steerforge
SteerForge theme for Tuleap

%package theme-tuleap
Summary: Tuleap theme
Group: Development/Tools
Version: @@THEME_TULEAP_VERSION@@
Release: 1%{?dist}
Requires: %{PKG_NAME}
%description theme-tuleap
Tuleap theme

%package theme-experimental
Summary: Experimental theme for Tuleap
Group: Development/Tools
Version: @@THEME_EXPERIMENTAL_VERSION@@
Release: 1%{?dist}
Requires: tuleap
%description theme-experimental
Experimental theme for Tuleap

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
# Install tuleap application
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}
for i in tools cli plugins site-content src ChangeLog VERSION; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{APP_DIR}
done
# Remove old scripts: not used and add unneeded perl depedencies to the package
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanUploader.pl
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanLegacyDownloader.pl
# Hard-coded perl include that breaks packging
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/plugins/forumml/bin/ml_arch_2_DB.pl
# No need of template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tests

# Data dir
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}
%{__install} -m 700 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/user

# Install script
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap-install
%{__install} -m 755 tools/setup.sh $RPM_BUILD_ROOT/%{_datadir}/tuleap-install/setup.sh
#
# Install Tuleap executables
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
%{__install} src/utils/svn/pre-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/post-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/fileforge.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/fileforge
%{__install} plugins/forumml/bin/mail_2_DB.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

# Special custom include script
%{__install} src/etc/env.inc.php.dist $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php
%{__perl} -pi -e "s~%include_path%~%{APP_PHP_INCLUDE_PATH}~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php

# Install init.d script
%{__install} -d $RPM_BUILD_ROOT/etc/rc.d/init.d
%{__install} src/utils/init.d/%{APP_NAME} $RPM_BUILD_ROOT/etc/rc.d/init.d/

# Install cron.d script
%{__install} -d $RPM_BUILD_ROOT/etc/cron.d
%{__install} src/utils/cron.d/codendi-stop $RPM_BUILD_ROOT/etc/cron.d/%{APP_NAME}

# Cache dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}

# Core subversion mod_perl
%{__install} -d $RPM_BUILD_ROOT/%{perl_vendorlib}/Apache
%{__install} src/utils/svn/Tuleap.pm $RPM_BUILD_ROOT/%{perl_vendorlib}/Apache

# Apache conf dir
%{__install} -d $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/

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
%{__install} plugins/git/bin/git-post-receive.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/bin/gitolite-suexec-wrapper.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

# Plugin archivedeleteditems
%{__install} plugins/archivedeleteditems/bin/archive-deleted-items.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

# Plugin tracker
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/tracker

# Plugin mediawiki
%if %{php_base} == php53
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/master
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/projects
%{__install} plugins/mediawiki/etc/mediawiki.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/mediawiki.conf
%else
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/mediawiki
%endif

#Plugin boomerang
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/boomerang

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
        /usr/sbin/usermod -c 'Owner of Tuleap directories'    -d '/home/codendiadm'    -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group},%{mailman_group} %{app_user}
    else
        /usr/sbin/useradd -c 'Owner of Tuleap directories' -M -d '/home/codendiadm' -r -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group},%{mailman_group} %{app_user}
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
        /usr/sbin/usermod -c 'Dummy Tuleap User'    -d '/var/lib/codendi/dumps'    -g %{dummy_group} %{dummy_user}
    else
        /usr/sbin/useradd -c 'Dummy Tuleap User' -M -d '/var/lib/codendi/dumps' -r -g %{dummy_group} %{dummy_user}
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

    # Remove existing combined js
    %{__rm} -f %{APP_DIR}/src/www/scripts/combined/codendi-*.js
    %{__chown} %{APP_USER}:%{APP_USER} %{APP_CACHE_DIR}/lang/*.php

    # Remove soap cache
    rm -f /tmp/wsdl-*
fi

# In any cases fix the context
/usr/bin/chcon -R root:object_r:httpd_sys_content_t $RPM_BUILD_ROOT/%{APP_DIR} || true

# This adds the proper /etc/rc*.d links for the script that runs the Tuleap backend
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
    if [ -d '/var/lib/gitolite' ]; then
	GITOLITE_BASE_DIR=/var/lib/gitolite
    else
	GITOLITE_BASE_DIR=/usr/com/gitolite
    fi

    # deploy gitolite.rc
    %{__install} -g gitolite -o gitolite -m 00644 %{APP_DIR}/plugins/git/etc/gitolite.rc.dist $GITOLITE_BASE_DIR/.gitolite.rc

    # generate codendiadm ssh key for gitolite
    %{__install} -d "%{APP_HOME_DIR}/.ssh/" -g %{APP_USER} -o %{APP_USER} -m 00700
    ssh-keygen -q -t rsa -N "" -C "Tuleap / gitolite admin key" -f "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub"

    # deploy codendiadm ssh key for gitolite
    %{__cp} "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub" /tmp/
    su -c 'git config --global user.name "gitolite"' - gitolite
    su -c 'git config --global user.email gitolite@localhost' - gitolite
    %{__install} -d -g gitolite -o gitolite -m 00700 $GITOLITE_BASE_DIR/.gitolite
    %{__install} -d -g gitolite -o gitolite -m 00700 $GITOLITE_BASE_DIR/.gitolite/conf
    %{__install} -g gitolite -o gitolite -m 00644 %{APP_DIR}/plugins/git/etc/gitolite.conf.dist $GITOLITE_BASE_DIR/.gitolite/conf/gitolite.conf
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
    perl -pi -e 's/^# \$GL_GET_MEMBERSHIPS_PGM/\$GL_GET_MEMBERSHIPS_PGM/' $GITOLITE_BASE_DIR/.gitolite.rc

    # add codendiadm to gitolite group
    if ! groups codendiadm | grep -q gitolite 2> /dev/null ; then
	usermod -a -G gitolite codendiadm
    fi
fi

#
# Post install of tracker plugin (clean combined js)
%post plugin-tracker
%{__rm} -f %{APP_DIR}/src/www/scripts/combined/*.js

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
%{APP_DIR}/tools
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
%{APP_DIR}/src/templates
%{APP_DIR}/src/updates
%{APP_DIR}/src/utils
# Split src/www for src/www/themes
%dir %{APP_DIR}/src/www
%{APP_DIR}/src/www/.htaccess
%{APP_DIR}/src/www/*.php
%{APP_DIR}/src/www/account
%{APP_DIR}/src/www/admin
%{APP_DIR}/src/www/api
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
%{APP_DIR}/src/www/people
%{APP_DIR}/src/www/project
%{APP_DIR}/src/www/projects
%{APP_DIR}/src/www/reference
%{APP_DIR}/src/www/scripts
%{APP_DIR}/src/www/search
%{APP_DIR}/src/www/service
%{APP_DIR}/src/www/site
%{APP_DIR}/src/www/snippet
%{APP_DIR}/src/www/soap
%{APP_DIR}/src/www/softwaremap
%{APP_DIR}/src/www/stats
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
%{APP_DIR}/plugins/statistics
%{APP_DIR}/plugins/tracker_date_reminder
%{APP_DIR}/plugins/userlog

# Data dir
%dir %{APP_DATA_DIR}
%dir %{APP_DATA_DIR}/user
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
%attr(00755,root,root) %{APP_LIBBIN_DIR}/env.inc.php
%attr(00755,root,root) %{APP_LIBBIN_DIR}/pre-revprop-change.php
%attr(00755,root,root) %{APP_LIBBIN_DIR}/post-revprop-change.php
%attr(04755,root,root) %{APP_LIBBIN_DIR}/fileforge
%attr(00755,root,root) /etc/rc.d/init.d/%{APP_NAME}
%attr(00644,root,root) /etc/cron.d/%{APP_NAME}
%dir %{APP_CACHE_DIR}
%dir /etc/httpd/conf.d/tuleap-plugins

#
# Install
#
%files install
%defattr(-,%{APP_USER},%{APP_USER},-)
%{_datadir}/tuleap-install

#
# Core
#
%files core-mailman
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/CORE_MAILMAN_VERSION

%files core-subversion
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/CORE_SUBVERSION_VERSION

%files core-subversion-modperl
%defattr(-,root,root,-)
%{perl_vendorlib}/Apache/Tuleap.pm

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
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/git-post-receive.pl
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gitolite-suexec-wrapper.sh

%files plugin-docmanwatermark
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/docmanwatermark

%files plugin-ldap
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/ldap

%files plugin-im
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/IM

%files plugin-hudson
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson

%files plugin-webdav
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav

%files plugin-requesthelp
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/requesthelp

%files plugin-tracker
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/tracker
%dir %{APP_DATA_DIR}/tracker

%files plugin-graphontrackers
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/graphontrackersv5

%files plugin-cardwall
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/cardwall

%files plugin-agiledashboard
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/agiledashboard

%files plugin-fulltextsearch
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/fulltextsearch

%files plugin-archivedeleteditems
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/archivedeleteditems
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/archive-deleted-items.pl

%files plugin-fusionforge_compat
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/fusionforge_compat

%files plugin-admssw
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/admssw

%files plugin-doaprdf
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/doaprdf

%files plugin-foafprofiles
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/foafprofiles

%files plugin-boomerang
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/boomerang
%dir %{APP_DATA_DIR}/boomerang

%if %{php_base} == php53
%files plugin-mediawiki
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/mediawiki
%dir %{APP_DATA_DIR}/mediawiki
%dir %{APP_DATA_DIR}/mediawiki/master
%dir %{APP_DATA_DIR}/mediawiki/projects
%attr(644,%{APP_USER},%{APP_USER}) /etc/httpd/conf.d/tuleap-plugins/mediawiki.conf
%endif

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

%files theme-experimental
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/Experimental

#%doc
#%config

%changelog
* Mon Oct 11 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Package plugins that matters (solve dependencies install issues).

* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.

