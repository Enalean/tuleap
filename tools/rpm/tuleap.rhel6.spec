# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME tuleap
%define APP_USER codendiadm
%define APP_HOME_DIR /home/%{APP_USER}
%define APP_DIR %{_datadir}/%{APP_NAME}
%define APP_LIB_DIR /usr/lib/%{APP_NAME}
%define APP_LIBBIN_DIR %{APP_LIB_DIR}/bin
%define APP_DATA_DIR %{_localstatedir}/lib/%{APP_NAME}
%define APP_CACHE_DIR %{_localstatedir}/tmp/%{APP_NAME}_cache
%define APP_LOG_DIR /var/log/%{APP_NAME}
%define APP_PHP_INCLUDE_PATH /usr/share/pear:%{APP_DIR}/src/www/include:%{APP_DIR}/src:.

# Compatibility
%define OLD_APP_NAME codendi
%define OLD_APP_DIR %{_datadir}/%{OLD_APP_NAME}
%define OLD_APP_LIB_DIR /usr/lib/%{OLD_APP_NAME}
%define OLD_APP_DATA_DIR %{_localstatedir}/lib/%{OLD_APP_NAME}
%define OLD_APP_CACHE_DIR %{_localstatedir}/tmp/%{OLD_APP_NAME}_cache
%define OLD_APP_LOG_DIR /var/log/%{OLD_APP_NAME}

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
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@enalean.com>

AutoReqProv: no

Requires: vixie-cron >= 4.1-9, tmpwatch
# Php and web related stuff
Requires: rh-php56-php-mysqlnd, rh-php56-php-pdo, rh-php56-php-xml, rh-php56-php-mbstring, rh-php56-php-gd,
Requires: rh-php56-php-soap, rh-php56-php-pear, rh-php56-php-intl, rh-php56-php-process, rh-php56-php-cli
Requires: rh-php56-php-bcmath, rh-php56-php-fpm, rh-php56-php-opcache, rh-php56-php-pecl-jsonc
Requires: dejavu-lgc-sans-fonts, dejavu-lgc-sans-mono-fonts, dejavu-lgc-serif-fonts

Requires: jpgraph-%{PKG_NAME}
Requires: ckeditor >= 4.3.2

Requires: curl
Requires: php-ZendFramework2-Mail
Requires: tuleap-core-subversion

# PHPWiki
Requires: highlight
# Python
Requires: python, MySQL-python
# Perl
Requires: perl, perl-DBI, perl-DBD-MySQL, perl-suidperl, perl-URI, perl-HTML-Tagset, perl-HTML-Parser, perl-libwww-perl, perl-DateManip, perl-Text-Iconv, perl-LDAP
# Automatic perl dependencies
#perl(APR::Pool)  perl(APR::Table)  perl(Apache2::Access)  perl(Apache2::Const)  perl(Apache2::Module)  perl(Apache2::RequestRec)  perl(Apache2::RequestUtil)  perl(Apache2::ServerRec)  perl(Carp)  perl(Cwd)  perl(DBI)  perl(Digest::MD5)  perl(Encode)  perl(File::Basename)  perl(File::Copy)  perl(HTTP::Request::Common)  perl(LWP::UserAgent)  perl(Net::LDAP)  perl(POSIX)  perl(Time::Local)  perl(strict)  perl(subs)  perl(vars)  perl(warnings)
# Apache
Requires: httpd, mod_ssl, openssl, nginx
# Mysql Client
Requires: mysql
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql, nss, nscd
# Forgeupgrade
Requires: forgeupgrade >= 1.2
# MIME libs
Requires: shared-mime-info
# Documentation
Requires: tuleap-documentation
# SELinux policy tools
Requires(post): policycoreutils-python
# Bind utils
Requires: bind-utils

Obsoletes: php-restler, php-amqplib-amqplib, php-markdown

%description
Tuleap is a web based application that address all the aspects of product development.

#
## Core component definitions
#

%package install
Summary: Initial setup of the platform
Group: Development/Tools
Version: @@VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description install
This package contains the setup script for the tuleap platform.
It is meant to be install at the initial setup of the platform and
recommanded to uninstall it after.

%package core-mailman
Summary: Mailman component for Tuleap
Group: Development/Tools
Version: @@CORE_MAILMAN_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: mailman-tuleap
Provides: tuleap-core-mailman
%description core-mailman
Manage dependencies for Tuleap mailman integration

%package core-subversion
Summary: Subversion component for Tuleap
Group: Development/Tools
Version: 1.2
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, subversion, mod_dav_svn, subversion-perl, tuleap-core-subversion-modperl, perl-CGI
Requires: viewvc, viewvc-theme-tuleap >= 1.0.6
Requires: sha1collisiondetector
%description core-subversion
Manage dependencies for Tuleap Subversion integration

%package core-subversion-modperl
Summary: Subversion with mod_perl authentication
Group: Development/Tools
Version: 1.3
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: mod_perl, perl-Digest-SHA, perl(Crypt::Eksblowfish::Bcrypt)
%description core-subversion-modperl
Provides authentication for Subversion component based on mod_perl rather than
mod_mysql.
This module might help server with big subversion usage. mod_mysql + mod_svn
seems to have memory leak issues.

%package core-cvs
Summary: CVS component for Tuleap
Group: Development/Tools
Version: @@CORE_CVS_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, xinetd, rcs, cvsgraph, perl-CGI
Requires: viewvc, viewvc-theme-tuleap >= 1.0.6
Requires: cvs-tuleap
%description core-cvs
Manage dependencies for Tuleap CVS integration

#
## Plugins
#

%package plugin-forumml
Summary: ForumML plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_FORUMML_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, php-pear-Mail-mimeDecode php-pear-Mail-Mime php-pear-Mail-Mbox php-pear-Mail
Requires: tuleap-core-mailman
Provides: tuleap-plugin-forumml = %{version}
%description plugin-forumml
ForumML brings to Tuleap a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-svn
Summary: Subversion plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_SVN_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, subversion, mod_dav_svn, subversion-perl, tuleap-core-subversion-modperl
Requires: viewvc, viewvc-theme-tuleap >= 1.0.6
%description plugin-svn
Integration of Subversion software configuration management tool with Tuleap.

%package plugin-git
Summary: Git plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_GIT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, git > 1.7.4, php-Smarty, gitolite = 2.3.1, gitphp-tuleap >= 0.2.5-15
Requires: geshi, php-guzzle-Guzzle, sudo
Provides: tuleap-plugin-git = %{version}
Conflicts: tuleap-plugin-git-gitolite3
%description plugin-git
Integration of git distributed software configuration management tool together
with Tuleap
This package is integrated with gitolite v2 (legacy)

%package plugin-git-gitolite3
Summary: Git plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_GIT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, rh-git29-git, php-Smarty, gitolite3, gitphp-tuleap >= 0.2.5-15
Requires: geshi, php-guzzle-Guzzle, sudo
Provides: tuleap-plugin-git = %{version}
Conflicts: tuleap-plugin-git
%description plugin-git-gitolite3
Integration of git distributed software configuration management tool together
with Tuleap.
This package is integrated with gitolite v3 (new version)

%package plugin-pullrequest
Summary: Pullrequest management for Tuleap
Version: @@PLUGIN_PULLREQUEST_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, %{name}-plugin-git
Group: Development/Tools
%description plugin-pullrequest
%{summary}.

%package plugin-ldap
Summary: Tuleap plugin to manage LDAP integration
Group: Development/Tools
Version: @@PLUGIN_LDAP_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, perl-LDAP, rh-php56-php-ldap
Provides: tuleap-plugin-ldap = %{version}
%description plugin-ldap
LDAP Plugin for Tuleap. Provides LDAP information, LDAP
authentication, user and group management.

%package plugin-im
Summary: Instant Messaging Plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_IM_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, openfire, glibc(x86-32), openfire-tuleap-plugins
#, zlib.i686
Provides: tuleap-plugin-im = %{version}
%description plugin-im
Provides instant messaging capabilities, based on a Jabber/XMPP server.

%package plugin-hudson
Summary: Hudson plugin for Tuleap
Group: Development/Tools/Building
Version: @@PLUGIN_HUDSON_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-hudson
Plugin to install the Tuleap Hudson plugin for continuous integration

%package plugin-hudson-svn
Summary: Hudson/Jenkins plugin for Tuleap SVN multiple repositories
Group: Development/Tools
Version: @@PLUGIN_HUDSON_SVN_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-hudson, tuleap-plugin-svn
%description plugin-hudson-svn
Hudson/Jenkins plugin for Tuleap SVN multiple repositories

%package plugin-hudson-git
Summary: Hudson/Jenkins plugin for Tuleap Git repositories
Group: Development/Tools
Version: @@PLUGIN_HUDSON_GIT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-hudson, tuleap-plugin-git
%description plugin-hudson-git
Hudson/Jenkins plugin for Tuleap Git repositories

%package plugin-webdav
Summary: WebDAV plugin for Tuleap
Group: Development/Tools
Version: @@PLUGIN_WEBDAV_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-sabredav = 1.4.4
%description plugin-webdav
Plugin to access to file releases & docman though WebDAV

%package plugin-tracker
AutoReqProv: no
Summary: Tracker v5 for Tuleap
Group: Development/Tools
Version: @@PLUGIN_TRACKER_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, libxslt, rh-php56-php-imap
%description plugin-tracker
New tracker generation for Tuleap.

%package plugin-graphontrackers
Summary: Graphs for Tracker v5
Group: Development/Tools
Version: @@PLUGIN_GRAPHONTRACKERS_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker >= 0.8.4
%description plugin-graphontrackers
Graphs for new tracker generation

%package plugin-tracker-encryption
Summary: Encryption for tracker
Group: Development/Tools
Version: @@PLUGIN_TRACKER_ENCRYPTION_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-phpseclib-crypt-rsa
%description plugin-tracker-encryption
Adding a new type of tracker fields that are encrypted.
This plugin is still in beta.

%package plugin-cardwall
Summary: Graphs for Tracker v5
Group: Development/Tools
Version: @@PLUGIN_CARDWALL_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: tuleap-plugin-tracker
%description plugin-cardwall
Fancy cardwall output on top of Tracker v5

%package plugin-agiledashboard
Summary: Agile dashboard
Group: Development/Tools
Version: @@PLUGIN_AGILEDASHBOARD_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker, tuleap-plugin-cardwall
%description plugin-agiledashboard
Agile Dashboard aims to provide an nice integration of Scrum/Kanban
tool on top of Tracker.

%package plugin-fulltextsearch
Summary: Full-Text Search
Group: Development/Tools
Version: @@PLUGIN_FULLTEXTSEARCH_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-elasticsearch
%description plugin-fulltextsearch
Allows documents of the docman to be searched in a full-text manner.

%package plugin-archivedeleteditems
Summary: Archiving plugin
Group: Development/Tools
Version: @@PLUGIN_ARCHIVEDELETEDITEMS_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-archivedeleteditems
Archive deleted items before purging them from filesystem

%package plugin-fusionforge_compat
Summary: FusionForge Compatibility
Group: Development/Tools
Version: 0.9
Release: @@VERSION@@_@@RELEASE@@%{?dist}
%description plugin-fusionforge_compat
This is an empty package. If this package is still installed on your system it can be removed safely.
Please check Tuleap deployment guide for more information.

%package plugin-mediawiki
Summary: Mediawiki plugin
Group: Development/Tools
Version: @@PLUGIN_MEDIAWIKI_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-mediawiki-tuleap-123
%description plugin-mediawiki
This plugin provides Mediawiki integration in Tuleap.

%package plugin-openidconnectclient
Summary: OpenId consumer plugin
Group: Development/Tools
Version: @@PLUGIN_OPENIDCONNECTCLIENT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-openid-connect-client
%description plugin-openidconnectclient
Connect to Tuleap using an OpenID Connect provider

%package plugin-proftpd
Summary: Proftpd plugin
Group: Development/Tools
Version: @@PLUGIN_PROFTPD_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-pear-HTTP-Download >= 1.1.4-3
%description plugin-proftpd
Control and interfact with Proftpd as FTP server

%package plugin-frs
AutoReqProv: no
Summary: File release system plugin
Group: Development/Tools
Version: @@PLUGIN_FRS_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-frs
Add features to the file release system provided by Tuleap

%package plugin-referencealias-core
Summary: Reference aliases plugin
Group: Development/Tools
Version: @@PLUGIN_REFERENCEALIAS_CORE_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-referencealias-core
This plugin allows to have references defined with "pkgXXX" syntax as an alias for Tuleap FRS refrences.

%package plugin-referencealias-git
Summary: Reference aliases for git plugin
Group: Development/Tools
Version: @@PLUGIN_REFERENCEALIAS_GIT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-git
%description plugin-referencealias-git
This plugin allows to use cmmtXXX as aliases for git references

%package plugin-referencealias-svn
Summary: Reference aliases for svn plugin
Group: Development/Tools
Version: @@PLUGIN_REFERENCEALIAS_SVN_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-svn
%description plugin-referencealias-svn
This plugin allows to use cmmtXXX references as aliases for svn plugin commit references

%package plugin-referencealias-mediawiki
Summary: Reference aliases for mediawiki plugin
Group: Development/Tools
Version: @@PLUGIN_REFERENCEALIAS_MEDIAWIKI_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-mediawiki
%description plugin-referencealias-mediawiki
This plugin allows to use wikiXXXX references to point to mediawiki pages

%package plugin-referencealias-tracker
Summary: Reference aliases for tracker plugin
Group: Development/Tools
Version: @@PLUGIN_REFERENCEALIAS_TRACKER_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-referencealias-tracker
This plugin allows to import references like "artfXXX" or "trackerYYYY" for the tracker plugin.

%package plugin-artifactsfolders
Summary: Artifacts Folders
Group: Development/Tools
Version: @@PLUGIN_ARTIFACTSFOLDERS_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-artifactsfolders
Add a "Folder" tab in an artifact

%package plugin-captcha
Summary: Add a captcha protection to sensitive operations
Group: Development/Tools
Version: @@PLUGIN_CAPTCHA_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-captcha
%{summary}.

%package plugin-bugzilla-reference
Summary: References between Bugzilla and Tuleap
Group: Development/Tools
Version: @@PLUGIN_BUGZILLA_REFERENCE_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-bugzilla-reference
%{summary}.

%package plugin-create-test-env
Summary: Create test environment on a Tuleap server
Group: Development/Tools
Version: @@PLUGIN_CREATE_TEST_ENV_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-create-test-env
%{summary}.

%package api-explorer
Summary: Web API Explorer
Group: Development/Tools
Version: 1.0
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Obsoletes: restler-api-explorer
%description api-explorer
Web API Explorer for Restler. Based on Swagger UI, it dynamically generates beautiful documentation.

#
## Themes
#

%package theme-flamingparrot
Summary: FlamingParrot, default theme starting Tuleap 7
Group: Development/Tools
Version: @@THEME_FLAMINGPARROT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Provides: tuleap-theme-experimental %{PKG_NAME}-theme-steerforge  %{PKG_NAME}-theme-codexstn  %{PKG_NAME}-theme-sttab  %{PKG_NAME}-theme-savannah  %{PKG_NAME}-theme-dawn
Obsoletes: tuleap-theme-experimental %{PKG_NAME}-theme-steerforge  %{PKG_NAME}-theme-codexstn  %{PKG_NAME}-theme-sttab  %{PKG_NAME}-theme-savannah  %{PKG_NAME}-theme-dawn
%description theme-flamingparrot
FlamingParrot, default theme starting Tuleap 7

%package theme-burningparrot
Summary: BurningParrot, default theme starting Tuleap 10
Group: Development/Tools
Version: @@THEME_BURNINGPARROT_VERSION@@
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description theme-burningparrot
BurningParrot, default theme starting Tuleap 10

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
for i in tools plugins site-content src VERSION AUTHORS; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{APP_DIR}
done
# No need of template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tests
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/label
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/crosstracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/timetracking
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/dynamic_credentials
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/velocity
# Remove development tools and utility files
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/composer.lock
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/autoload
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/gerrit_setup
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/githooks
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/version_numbers
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/autoload.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-mo.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-po.php
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/sass.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/tuleap-gulp-build.js
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/tlp/webpack.config.js

# Link to local config for logo and themes images
# Needed for nginx try_files
%{__ln_s} /etc/%{APP_NAME}/themes/common/images $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/local

# Data dir
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}
%{__install} -m 700 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/user
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/images

# Install script
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap-install
%{__install} -m 755 tools/setup.sh $RPM_BUILD_ROOT/%{_datadir}/tuleap-install/setup.sh

# Install Tuleap executables
%{__install} -d $RPM_BUILD_ROOT/%{_bindir}
%{__install} src/utils/tuleap $RPM_BUILD_ROOT/%{_bindir}/tuleap

%{__install} -d $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/gotohell $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/backup_job $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/log_accum $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/commit_prep $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/cvssh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/cvs1/cvssh-restricted $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/commit-email.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/codendi_svn_pre_commit.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/pre-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/post-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/fileforge.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/fileforge

# Special custom include script
%{__install} src/etc/env.inc.php.dist $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php
%{__perl} -pi -e "s~%include_path%~%{APP_PHP_INCLUDE_PATH}~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php

# Install init.d script
%{__install} -d $RPM_BUILD_ROOT/etc/rc.d/init.d
%{__install} src/utils/init.d/codendi $RPM_BUILD_ROOT/etc/rc.d/init.d/%{APP_NAME}

# Install cron.d script
%{__install} -d $RPM_BUILD_ROOT/etc/cron.d
%{__install} src/utils/cron.d/codendi-stop $RPM_BUILD_ROOT/etc/cron.d/%{APP_NAME}

# Install logrotate.d script
%{__install} -d $RPM_BUILD_ROOT/etc/logrotate.d
%{__install} src/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog

# Cache dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}

# Log dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_LOG_DIR}
%{__install} -d $RPM_BUILD_ROOT/%{APP_LOG_DIR}/cvslog

# Run dir
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/tuleap

# Core subversion mod_perl
%{__install} -d $RPM_BUILD_ROOT/%{perl_vendorlib}/Apache
%{__install} src/utils/svn/Tuleap.pm $RPM_BUILD_ROOT/%{perl_vendorlib}/Apache

# Apache conf dir
%{__install} -d $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/
%{__install} src/etc/ckeditor.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/ckeditor.conf
%{__install} src/etc/tuleap-uploaded-images.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/tuleap-uploaded-images.conf
%{__install} -d $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/
%{__install} src/etc/00-common.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/00-common.conf
%{__install} src/etc/02-themes.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/02-themes.conf
%{__install} src/etc/03-plugins.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/03-plugins.conf
%{__install} src/etc/04-cgi.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/04-cgi.conf

# Sudoers directory
%{__install} -d $RPM_BUILD_ROOT/etc/sudoers.d

# plugin webdav
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/plugins/webdav/locks

# plugin forumml
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/forumml
%{__install} plugins/forumml/etc/sudoers.d/tuleap_plugin_forumml $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/tuleap_plugin_forumml

# plugin-git
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitroot
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/repositories
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/grokmirror
touch $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/projects.list
%{__ln_s} var/lib/%{APP_NAME}/gitroot $RPM_BUILD_ROOT
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/templates_c
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/cache
%{__install} plugins/git/bin/gl-membership.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/bin/gitolite-suexec-wrapper.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/bin/restore-tar-repository.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__install} plugins/git/etc/sudoers.d/gitolite-http $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite2_http
%{__install} plugins/git/etc/sudoers.d/tuleap-git-postreceive $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_postreceive

# plugin-git-gitolite3
%{__install} plugins/git/bin/gitolite3-suexec-wrapper.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/etc/sudoers.d/gitolite3-http $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__perl} -pi -e "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__install} plugins/git/etc/sudoers.d/gitolite3-replace-authorized-keys $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_replace_authorized_keys

#codendiadm > gitolite sudo
%{__install} plugins/git/etc/sudoers.d/gitolite $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite
%{__perl} -pi -e "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite

# Plugin PullRequest
%{__install} -D plugins/pullrequest/etc/sudoers.d/gitolite-access-command $RPM_BUILD_ROOT/etc/sudoers.d/gitolite-access-command
%{__install} plugins/pullrequest/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest

# Plugin svn
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/svn_plugin

# Plugin tracker
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/tracker
%{__install} plugins/tracker/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__install} plugins/tracker/etc/sudoers.d/tuleap-plugin-tracker $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_tracker

# Plugin agiledashboard
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/agiledashboard
%{__install} plugins/agiledashboard/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard

# Plugin mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/master
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/projects
%{__install} plugins/mediawiki/etc/mediawiki.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/mediawiki.conf

# Plugin proftpd
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/secure_ftp

# Plugin fulltextsearch
%{__install} plugins/fulltextsearch/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_fulltextsearch
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_fulltextsearch
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_fulltextsearch

# Plugin bugzilla
%{__install} plugins/bugzilla_reference/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference

#Plugin archivedeleteditems
%{__install} plugins/archivedeleteditems/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_archivedeleteditems

# Plugin hudson_git
%{__install} plugins/hudson_git/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_hudson_git

# Plugin hudson_svn
%{__install} plugins/hudson_svn/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_hudson_svn

# Plugin im
%{__install} plugins/IM/etc/05-im.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/05-im.conf

# Plugin create_test_env
%{__install} plugins/create_test_env/etc/sudoers.d/tuleap_plugin_create_test_env $RPM_BUILD_ROOT/%{_sysconfdir}/sudoers.d

# Symlink for compatibility with older version
%{__ln_s} %{APP_DIR} $RPM_BUILD_ROOT/%{OLD_APP_DIR}
%{__ln_s} %{APP_LIB_DIR} $RPM_BUILD_ROOT/%{OLD_APP_LIB_DIR}
%{__ln_s} %{APP_DATA_DIR} $RPM_BUILD_ROOT/%{OLD_APP_DATA_DIR}
%{__ln_s} %{APP_CACHE_DIR} $RPM_BUILD_ROOT/%{OLD_APP_CACHE_DIR}
%{__ln_s} %{APP_LOG_DIR} $RPM_BUILD_ROOT/%{OLD_APP_LOG_DIR}
%{__ln_s} /etc/rc.d/init.d/%{APP_NAME} $RPM_BUILD_ROOT/etc/rc.d/init.d/codendi
%{__ln_s} /etc/%{APP_NAME} $RPM_BUILD_ROOT/etc/%{OLD_APP_NAME}

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
    if id %{app_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Owner of Tuleap directories'    -d '/home/codendiadm'    -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group} %{app_user}
    else
        /usr/sbin/useradd -c 'Owner of Tuleap directories' -m -d '/home/codendiadm' -r -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group} %{app_user}
    fi
    # ftpadmin
    if id %{ftpadmin_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'FTP Administrator'    -d '/var/lib/tuleap/ftp'    -g %{ftpadmin_group} %{ftpadmin_user}
    else
        /usr/sbin/useradd -c 'FTP Administrator' -M -d '/var/lib/tuleap/ftp' -r -g %{ftpadmin_group} %{ftpadmin_user}
    fi
    # ftp
    if id %{ftp_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'FTP User'    -d '/var/lib/tuleap/ftp'    -g %{ftp_group} %{ftp_user}
    else
        /usr/sbin/useradd -c 'FTP User' -M -d '/var/lib/tuleap/ftp' -r -g %{ftp_group} %{ftp_user}
    fi
    # dummy
    if id %{dummy_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Dummy Tuleap User'    -d '/var/lib/tuleap/dumps'    -g %{dummy_group} %{dummy_user}
    else
        /usr/sbin/useradd -c 'Dummy Tuleap User' -M -d '/var/lib/tuleap/dumps' -r -g %{dummy_group} %{dummy_user}
    fi
else
    # Stop the services
    #/etc/init.d/codendi stop
    #/sbin/service httpd stop

    true
fi

%pre plugin-git-gitolite3
if [ "$1" -eq "1" ]; then
    # Install
    if ! grep -q "^gitolite:" /etc/group 2> /dev/null ; then
        /usr/sbin/groupadd -r gitolite 2> /dev/null || :
    fi

    if id gitolite >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Git'    -d '/var/lib/gitolite' -g gitolite gitolite
    else
        /usr/sbin/useradd -c 'Git' -m -d '/var/lib/gitolite' -g gitolite gitolite
    fi

else
    true
fi

echo 'source /opt/rh/rh-git29/enable' > /var/lib/gitolite/.profile
chown gitolite:gitolite /var/lib/gitolite/.profile

chmod 750 /var/lib/gitolite

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

    %{_bindir}/tuleap --clear-caches &> /dev/null
fi

# In any cases fix the context
/usr/sbin/semanage fcontext -a -t httpd_sys_content_t '%{APP_DIR}(/.*)?' || true
/sbin/restorecon -R %{APP_DIR} || true

# This adds the proper /etc/rc*.d links for the script that runs the Tuleap backend
#/sbin/chkconfig --add %{APP_NAME}

# Restart the services
#/sbin/service httpd start
#/etc/init.d/codendi start

#
# Post install of git plugin
%post plugin-git
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
%{__install} -g gitolite -o gitolite -m 00755 %{APP_DIR}/plugins/git/hooks/post-receive-gitolite /usr/share/gitolite/hooks/common/post-receive

%post plugin-git-gitolite3
if [ ! -d "%{APP_DATA_DIR}/gitolite/admin" ]; then
    # Setup repositories in Tuleap area
    %{__ln_s} %{APP_DATA_DIR}/gitolite/repositories /var/lib/gitolite/repositories

    # deploy gitolite.rc
    %{__install} -g gitolite -o gitolite -m 00644 %{APP_DIR}/plugins/git/etc/gitolite3.rc.dist /var/lib/gitolite/.gitolite.rc

    # generate codendiadm ssh key for gitolite
    %{__install} -d "%{APP_HOME_DIR}/.ssh/" -g %{APP_USER} -o %{APP_USER} -m 00700
    ssh-keygen -q -t rsa -N "" -C "Tuleap / gitolite admin key" -f "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub"

    # deploy codendiadm ssh key for gitolite
    %{__cp} "%{APP_HOME_DIR}/.ssh/id_rsa_gl-adm.pub" /tmp/
    su -c 'git config --global user.name "gitolite"' - gitolite
    su -c 'git config --global user.email gitolite@localhost' - gitolite
    su -c 'gitolite setup -pk /tmp/id_rsa_gl-adm.pub' - gitolite

    # checkout
    %{__cat} "%{APP_DIR}/plugins/git/etc/ssh.config.dist" >> "%{APP_HOME_DIR}/.ssh/config"
    %{__chown}  %{APP_USER}:%{APP_USER}  "%{APP_HOME_DIR}/.ssh/config"
    su -c 'git config --global user.name "%{APP_USER}"' - %{APP_USER}
    su -c 'git config --global user.email %{APP_USER}@localhost' - %{APP_USER}
    su -c 'cd %{APP_DATA_DIR}/gitolite; git clone gitolite@gl-adm:gitolite-admin admin' - %{APP_USER}
    %{__chmod} 750 %{APP_DATA_DIR}/gitolite/admin

    # remove testing
    %{__install} -g codendiadm -o codendiadm -m 00644 %{APP_DIR}/plugins/git/etc/gitolite.conf.dist  %{APP_DATA_DIR}/gitolite/admin/conf/gitolite.conf
    su -c 'cd %{APP_DATA_DIR}/gitolite/admin && git add conf/gitolite.conf && git commit -m "Remove testing" && git push origin master' - %{APP_USER}
    %{__rm} -rf %{APP_DATA_DIR}/gitolite/repositories/testing.git

    # uncomment gl-membership
    # Cannot be done before Tuleap setup. Otherwise previous clone command fails because gl-membership
    # doesn't have DB access .
    perl -pi -e 's/# GROUPLIST_PGM/GROUPLIST_PGM/' /var/lib/gitolite/.gitolite.rc

    # SSH keys are managed by Tuleap
    sed -i "s/'ssh-authkeys',/#'ssh-authkeys',/" /var/lib/gitolite/.gitolite.rc

    # add codendiadm to gitolite group
    if ! groups codendiadm | grep -q gitolite 2> /dev/null ; then
	usermod -a -G gitolite codendiadm
    fi
fi
%{__install} -g gitolite -o gitolite -m 00755 %{APP_DIR}/plugins/git/hooks/post-receive-gitolite /var/lib/gitolite/.gitolite/hooks/common/post-receive
if [ -f /usr/share/gitolite/hooks/common/post-receive ]; then
	%{__install} -g gitolite -o gitolite -m 00755 %{APP_DIR}/plugins/git/hooks/post-receive-gitolite /usr/share/gitolite/hooks/common/post-receive
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
%{APP_DIR}/site-content
%{APP_DIR}/VERSION
%{APP_DIR}/AUTHORS
# Split src for src/www/themes
%dir %{APP_DIR}/src
%{APP_DIR}/src/glyphs
%{APP_DIR}/src/common
%{APP_DIR}/src/vendor
%{APP_DIR}/src/COPYING
%{APP_DIR}/src/db
%{APP_DIR}/src/etc
%{APP_DIR}/src/forgeupgrade
%{APP_DIR}/src/templates
%{APP_DIR}/src/utils
# Split src/www for src/www/themes
%dir %{APP_DIR}/src/www
%{APP_DIR}/src/www/*.php
%{APP_DIR}/src/www/.htaccess
%{APP_DIR}/src/www/account
%{APP_DIR}/src/www/admin
# API Explorer is not packaged with the core
%dir %{APP_DIR}/src/www/api
%{APP_DIR}/src/www/api/index.php
%{APP_DIR}/src/www/api/VERSION
%{APP_DIR}/src/www/api/.htaccess
%{APP_DIR}/src/www/api/reference
%{APP_DIR}/src/www/assets
%{APP_DIR}/src/www/codendi.css
%{APP_DIR}/src/www/cvs
%{APP_DIR}/src/www/export
%{APP_DIR}/src/www/favicon.ico
%{APP_DIR}/src/www/file
%{APP_DIR}/src/www/forum
%{APP_DIR}/src/www/help
%{APP_DIR}/src/www/include
%{APP_DIR}/src/www/mail
%{APP_DIR}/src/www/my
%{APP_DIR}/src/www/new
%{APP_DIR}/src/www/news
%{APP_DIR}/src/www/project
%{APP_DIR}/src/www/reference
%{APP_DIR}/src/www/scripts
%{APP_DIR}/src/www/search
%{APP_DIR}/src/www/service
%{APP_DIR}/src/www/soap
%{APP_DIR}/src/www/softwaremap
%{APP_DIR}/src/www/svn
# Only "common" theme is embedded into the package
%dir %{APP_DIR}/src/www/themes
%dir %{APP_DIR}/src/www/themes/common
%{APP_DIR}/src/www/themes/common/css
%{APP_DIR}/src/www/themes/common/font
%{APP_DIR}/src/www/themes/common/images
%dir %{APP_DIR}/src/www/themes/common/tlp
%{APP_DIR}/src/www/themes/common/tlp/dist
%{APP_DIR}/src/www/themes/local
%{APP_DIR}/src/www/tos
%{APP_DIR}/src/www/tour
%{APP_DIR}/src/www/tracker
%{APP_DIR}/src/www/user
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
%dir %{APP_DATA_DIR}/images

# Executables (/usr/bin)
%attr(00755,%{APP_USER},%{APP_USER}) %{_bindir}/tuleap

# Executables (/usr/lib/tuleap/bin)
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIB_DIR}
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIBBIN_DIR}
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gotohell
%attr(00740,root,root) %{APP_LIBBIN_DIR}/backup_job
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
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_syslog
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}
%dir /etc/httpd/conf.d/tuleap-plugins
%attr(04755,root,root) /etc/httpd/conf.d/tuleap-plugins/ckeditor.conf
%attr(04755,root,root) /etc/httpd/conf.d/tuleap-plugins/tuleap-uploaded-images.conf
%dir /etc/httpd/conf.d/tuleap-aliases
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/00-common.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/02-themes.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/03-plugins.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/04-cgi.conf

# Log dir
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LOG_DIR}
%attr(775,%{APP_USER},%{APP_USER}) %dir %{APP_LOG_DIR}/cvslog

# Run dir
%attr(00755,%{APP_USER},%{APP_USER}) %dir %{_localstatedir}/run/tuleap

# Compatibility with older version
%attr(-,root,root) %{OLD_APP_DIR}
%attr(-,root,root) %{OLD_APP_DATA_DIR}
%attr(-,root,root) %{OLD_APP_CACHE_DIR}
%attr(-,root,root) %{OLD_APP_LIB_DIR}
%attr(-,root,root) %{OLD_APP_LOG_DIR}
%attr(-,root,root) /etc/rc.d/init.d/codendi
%attr(-,root,root) /etc/%{OLD_APP_NAME}

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
%attr(00750,%{APP_USER},%{APP_USER}) %{_localstatedir}/run/forumml
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_forumml

%files plugin-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/git
%dir %{APP_DATA_DIR}/gitroot
%dir %{APP_DATA_DIR}/gitolite
%attr(00770,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/repositories
%attr(00775,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/grokmirror
%attr(00660,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/projects.list
%attr(-,root,root) /gitroot
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gl-membership.pl
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gitolite-suexec-wrapper.sh
%attr(00755,root,root) %{APP_LIBBIN_DIR}/restore-tar-repository.php
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite2_http
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_postreceive

%files plugin-git-gitolite3
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/git
%dir %{APP_DATA_DIR}/gitroot
%dir %{APP_DATA_DIR}/gitolite
%attr(00770,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/repositories
%attr(00775,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/grokmirror
%attr(00660,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/projects.list
%attr(-,root,root) /gitroot
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gl-membership.pl
%attr(00755,root,root) %{APP_LIBBIN_DIR}/gitolite3-suexec-wrapper.sh
%attr(00755,root,root) %{APP_LIBBIN_DIR}/restore-tar-repository.php
%attr(00644,root,root) %{_sysconfdir}/logrotate.d/%{APP_NAME}_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_http
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_postreceive
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_replace_authorized_keys

%files plugin-pullrequest
%defattr(-,root,root,-)
%{APP_DIR}/plugins/pullrequest
%config(noreplace) /etc/logrotate.d/tuleap_pullrequest
%attr(00440,root,root) /etc/sudoers.d/gitolite-access-command

%files plugin-ldap
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/ldap

%files plugin-im
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/IM
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/05-im.conf

%files plugin-hudson
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson

%files plugin-hudson-svn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson_svn
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_svn
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_svn

%files plugin-hudson-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson_git
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_git
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_git

%files plugin-webdav
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav

%files plugin-svn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/svn
%dir %{APP_DATA_DIR}/svn_plugin

%files plugin-tracker
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/tracker
%dir %{APP_DATA_DIR}/tracker
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_tracker
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_tracker

%files plugin-graphontrackers
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/graphontrackersv5

%files plugin-tracker-encryption
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/tracker_encryption

%files plugin-cardwall
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/cardwall

%files plugin-agiledashboard
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/agiledashboard
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_agiledashboard
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_agiledashboard

%files plugin-fulltextsearch
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/fulltextsearch
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_fulltextsearch

%files plugin-archivedeleteditems
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/archivedeleteditems
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems

%files plugin-fusionforge_compat

%files plugin-mediawiki
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/mediawiki
%dir %{APP_DATA_DIR}/mediawiki
%dir %{APP_DATA_DIR}/mediawiki/master
%dir %{APP_DATA_DIR}/mediawiki/projects
%attr(644,%{APP_USER},%{APP_USER}) /etc/httpd/conf.d/tuleap-plugins/mediawiki.conf

%files plugin-openidconnectclient
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/openidconnectclient

%files plugin-proftpd
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/proftpd
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/secure_ftp

%files plugin-frs
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/frs

%files plugin-referencealias-core
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/referencealias_core

%files plugin-referencealias-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/referencealias_git

%files plugin-referencealias-svn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/referencealias_svn

%files plugin-referencealias-mediawiki
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/referencealias_mediawiki

%files plugin-referencealias-tracker
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/referencealias_tracker

%files plugin-artifactsfolders
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/artifactsfolders

%files plugin-captcha
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/captcha

%files plugin-bugzilla-reference
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/bugzilla_reference
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_bugzilla_reference

%files plugin-create-test-env
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/create_test_env
%attr(00400,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_create_test_env

%files api-explorer
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/api/explorer

#
# Themes
#

%files theme-flamingparrot
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/FlamingParrot

%files theme-burningparrot
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/www/themes/BurningParrot

#%doc
#%config

%changelog
* Fri Dec 04 2015 Adrien HAMRAOUI <adrien.hamraoui@sogilis.com> -
- Add gitolite sudoer file installation.

* Mon Oct 11 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Package plugins that matters (solve dependencies install issues).

* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.
