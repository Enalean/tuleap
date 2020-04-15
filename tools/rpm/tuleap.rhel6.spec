# Define variables
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
%define APP_PHP_INCLUDE_PATH %{APP_DIR}/src/www/include:%{APP_DIR}/src:.

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

%bcond_with enterprise
%bcond_with experimental

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

Requires: vixie-cron >= 4.1-9
# Php and web related stuff
Requires: php73-php-common >= 7.3.15
Requires: php73-php-mysqlnd, php73-php-pdo, php73-php-xml, php73-php-mbstring, php73-php-gd
Requires: php73-php-soap, php73-php-intl, php73-php-process, php73-php-cli
Requires: php73-php-bcmath, php73-php-fpm, php73-php-opcache, php73-php-json, php73-php-pecl-zip, php73-php-pecl-redis, php73-php-sodium

Requires: curl
Requires: tuleap-core-subversion

# PHPWiki
Requires: highlight
# Python
Requires: python, MySQL-python
# Perl
Requires: perl, perl-DBI, perl-DBD-MySQL, sudo, perl-URI, perl-HTML-Tagset, perl-HTML-Parser, perl-libwww-perl, perl-DateManip, perl-Text-Iconv, perl-LDAP
# Automatic perl dependencies
#perl(APR::Pool)  perl(APR::Table)  perl(Apache2::Access)  perl(Apache2::Const)  perl(Apache2::Module)  perl(Apache2::RequestRec)  perl(Apache2::RequestUtil)  perl(Apache2::ServerRec)  perl(Carp)  perl(Cwd)  perl(DBI)  perl(Digest::MD5)  perl(Encode)  perl(File::Basename)  perl(File::Copy)  perl(HTTP::Request::Common)  perl(LWP::UserAgent)  perl(Net::LDAP)  perl(POSIX)  perl(Time::Local)  perl(strict)  perl(subs)  perl(vars)  perl(warnings)
# Apache
Requires: httpd, mod_ssl, openssl, nginx
# Mysql Client
Requires: mysql
# libnss-mysql (system authentication based on MySQL)
Requires: libnss-mysql, nss, nscd
# Forgeupgrade
Requires: forgeupgrade >= 1.6
# MIME libs
Requires: shared-mime-info
# Documentation
Requires: tuleap-documentation
# SELinux policy tools
Requires(post): policycoreutils-python
# Bind utils
Requires: bind-utils

Obsoletes: php-restler, php-markdown
Obsoletes: %{name}-plugin-im
Obsoletes: %{name}-plugin-fulltextsearch

# It's embedded in Tuleap thanks to npm.
Obsoletes: ckeditor

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
Requires: viewvc, viewvc-theme-tuleap >= 1.0.7
Requires: sha1collisiondetector
%description core-subversion
Manage dependencies for Tuleap Subversion integration

%package core-subversion-modperl
Summary: Subversion with mod_perl authentication
Group: Development/Tools
Version: 1.3
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: mod_perl, perl-Digest-SHA, perl(Crypt::Eksblowfish::Bcrypt), perl(Redis)
%description core-subversion-modperl
Provides authentication for Subversion component based on mod_perl rather than
mod_mysql.
This module might help server with big subversion usage. mod_mysql + mod_svn
seems to have memory leak issues.

%package core-cvs
Summary: CVS component for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, xinetd, rcs, cvsgraph, perl-CGI
Requires: viewvc, viewvc-theme-tuleap >= 1.0.7
Requires: cvs-tuleap
%description core-cvs
Manage dependencies for Tuleap CVS integration

#
## Plugins
#

%package plugin-forumml
Summary: ForumML plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, php73-php-pecl-mailparse
Requires: tuleap-core-mailman
Provides: tuleap-plugin-forumml = %{version}
%description plugin-forumml
ForumML brings to Tuleap a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-svn
Summary: Subversion plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, subversion, mod_dav_svn, subversion-perl, tuleap-core-subversion-modperl
Requires: viewvc, viewvc-theme-tuleap >= 1.0.7
%description plugin-svn
Integration of Subversion software configuration management tool with Tuleap.

%package plugin-git
Summary: Git plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, git > 1.7.4, gitolite = 2.3.1
Requires: sudo
Provides: tuleap-plugin-git = %{version}
Conflicts: tuleap-plugin-git-gitolite3
%description plugin-git
Integration of git distributed software configuration management tool together
with Tuleap
This package is integrated with gitolite v2 (legacy)

%package plugin-git-gitolite3
Summary: Git plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires(pre): shadow-utils
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, sclo-git212-git, gitolite3
Requires: sudo
Provides: tuleap-plugin-git = %{version}
Conflicts: tuleap-plugin-git
%description plugin-git-gitolite3
Integration of git distributed software configuration management tool together
with Tuleap.
This package is integrated with gitolite v3 (new version)

%package plugin-gitlfs
Summary: Support of large file upload and download in Git
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, %{name}-plugin-git-gitolite3, sudo
Group: Development/Tools
%description plugin-gitlfs
%{summary}.

%package plugin-pullrequest
Summary: Pullrequest management for Tuleap
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, %{name}-plugin-git, sclo-git212-git
Group: Development/Tools
%description plugin-pullrequest
%{summary}.

%package plugin-ldap
Summary: Tuleap plugin to manage LDAP integration
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, perl-LDAP, php73-php-ldap
Provides: tuleap-plugin-ldap = %{version}
%description plugin-ldap
LDAP Plugin for Tuleap. Provides LDAP information, LDAP
authentication, user and group management.

%package plugin-hudson
Summary: Hudson plugin for Tuleap
Group: Development/Tools/Building
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-hudson
Plugin to install the Tuleap Hudson plugin for continuous integration

%package plugin-hudson-svn
Summary: Hudson/Jenkins plugin for Tuleap SVN multiple repositories
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-hudson, tuleap-plugin-svn
%description plugin-hudson-svn
Hudson/Jenkins plugin for Tuleap SVN multiple repositories

%package plugin-hudson-git
Summary: Hudson/Jenkins plugin for Tuleap Git repositories
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-hudson, tuleap-plugin-git
%description plugin-hudson-git
Hudson/Jenkins plugin for Tuleap Git repositories

%package plugin-webdav
Summary: WebDAV plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Obsoletes: php-sabredav
%description plugin-webdav
Plugin to access to file releases & docman though WebDAV

%package plugin-tracker
AutoReqProv: no
Summary: Tracker v5 for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, libxslt, php73-php-pecl-mailparse
%description plugin-tracker
New tracker generation for Tuleap.

%package plugin-graphontrackers
Summary: Graphs for Tracker v5
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker >= 0.8.4
%description plugin-graphontrackers
Graphs for new tracker generation

%package plugin-tracker-encryption
Summary: Encryption for tracker
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-tracker-encryption
Adding a new type of tracker fields that are encrypted.
This plugin is still in beta.

%package plugin-cardwall
Summary: Graphs for Tracker v5
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: tuleap-plugin-tracker
%description plugin-cardwall
Fancy cardwall output on top of Tracker v5

%package plugin-agiledashboard
Summary: Agile dashboard
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker, tuleap-plugin-cardwall
%description plugin-agiledashboard
Agile Dashboard aims to provide an nice integration of Scrum/Kanban
tool on top of Tracker.

%package plugin-archivedeleteditems
Summary: Archiving plugin
Group: Development/Tools
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
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: php-mediawiki-tuleap-123 >= 1.23.9-7
%description plugin-mediawiki
This plugin provides Mediawiki integration in Tuleap.

%package plugin-openidconnectclient
Summary: OpenId consumer plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-openidconnectclient
Connect to Tuleap using an OpenID Connect provider

%package plugin-proftpd
Summary: Proftpd plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-proftpd
Control and interfact with Proftpd as FTP server

%package plugin-frs
AutoReqProv: no
Summary: File release system plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-frs
Add features to the file release system provided by Tuleap

%package plugin-referencealias-core
Summary: Reference aliases plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-referencealias-core
This plugin allows to have references defined with "pkgXXX" syntax as an alias for Tuleap FRS refrences.

%package plugin-referencealias-git
Summary: Reference aliases for git plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-git
%description plugin-referencealias-git
This plugin allows to use cmmtXXX as aliases for git references

%package plugin-referencealias-svn
Summary: Reference aliases for svn plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-svn
%description plugin-referencealias-svn
This plugin allows to use cmmtXXX references as aliases for svn plugin commit references

%package plugin-referencealias-mediawiki
Summary: Reference aliases for mediawiki plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-mediawiki
%description plugin-referencealias-mediawiki
This plugin allows to use wikiXXXX references to point to mediawiki pages

%package plugin-referencealias-tracker
Summary: Reference aliases for tracker plugin
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-referencealias-tracker
This plugin allows to import references like "artfXXX" or "trackerYYYY" for the tracker plugin.

%package plugin-artifactsfolders
Summary: Artifacts Folders
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker
%description plugin-artifactsfolders
Add a "Folder" tab in an artifact

%package plugin-captcha
Summary: Add a captcha protection to sensitive operations
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-captcha
%{summary}.

%package plugin-bugzilla-reference
Summary: References between Bugzilla and Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-bugzilla-reference
%{summary}.

%package plugin-create-test-env
Summary: Create test environment on a Tuleap server
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker, tuleap-plugin-botmattermost
%description plugin-create-test-env
%{summary}.

%package plugin-api-explorer
Summary: Web API Explorer
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Obsoletes: tuleap-api-explorer, restler-api-explorer
Provides: tuleap-api-explorer, restler-api-explorer
%description plugin-api-explorer
%{summary}.

%if %{with enterprise}

%package plugin-crosstracker
Summary: Cross tracker search widget
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist},  tuleap-plugin-tracker
%description plugin-crosstracker
%{summary}.

%package plugin-document
Summary: Document UI
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-document
%{summary}.

%package plugin-dynamic-credentials
Summary: Dynamic credentials generation
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-dynamic-credentials
%{summary}.

%package plugin-label
Summary: Label widget
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-label
%{summary}.

%package plugin-project-ownership
Summary: Project ownership
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Obsoletes: tuleap-plugin-project-certification
%description plugin-project-ownership
%{summary}.

%package plugin-projectmilestones
Summary: A widget for milestones monitoring
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-agiledashboard
%description plugin-projectmilestones
%{summary}.

%package plugin-prometheus-metrics
Summary: Prometheus metrics end point
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-prometheus-metrics
%{summary}.

%package plugin-taskboard
Summary: Taskboard
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-agiledashboard
%description plugin-taskboard
%{summary}.

%package plugin-testmanagement
Summary: Test Management plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-tracker, tuleap-plugin-agiledashboard
%description plugin-testmanagement
%{summary}.

%package plugin-textualreport
Summary: Textual Report
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-textualreport
%{summary}.

%package plugin-timetracking
Summary: Timetracking plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-timetracking
%{summary}.

%package plugin-velocity
Summary: Velocity chart
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-agiledashboard
%description plugin-velocity
%{summary}.

%endif

%if %{with experimental}

%package plugin-oauth2-server
Summary: OAuth2 Server
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-oauth2-server
%{summary}.

%endif

#
## Themes
#

%package theme-flamingparrot
Summary: FlamingParrot, default theme starting Tuleap 7
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Provides: tuleap-theme-experimental %{PKG_NAME}-theme-steerforge  %{PKG_NAME}-theme-codexstn  %{PKG_NAME}-theme-sttab  %{PKG_NAME}-theme-savannah  %{PKG_NAME}-theme-dawn
Obsoletes: tuleap-theme-experimental %{PKG_NAME}-theme-steerforge  %{PKG_NAME}-theme-codexstn  %{PKG_NAME}-theme-sttab  %{PKG_NAME}-theme-savannah  %{PKG_NAME}-theme-dawn
%description theme-flamingparrot
FlamingParrot, default theme starting Tuleap 7

%package theme-burningparrot
Summary: BurningParrot, default theme starting Tuleap 10
Group: Development/Tools
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
%if %{with enterprise}
echo '@@VERSION@@-@@RELEASE@@' > VERSION
%endif

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

#
# Install tuleap application
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}
for i in tools plugins site-content src VERSION LICENSE; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{APP_DIR}
done
%if %{with enterprise}
%{__cp} -a ENTERPRISE_BUILD $RPM_BUILD_ROOT/%{APP_DIR}
%endif
# No need of template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tee_container
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/template
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/mfa
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tuleap_synchro
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/tuleap_synchro
/usr/bin/find "$RPM_BUILD_ROOT/%{APP_DIR}/" -depth -mindepth 3 -maxdepth 3 -path "$RPM_BUILD_ROOT/%{APP_DIR}/plugins/*/scripts" -type d -execdir %{__rm} -rf "{}" \;
/usr/bin/find "$RPM_BUILD_ROOT/%{APP_DIR}/" -depth -mindepth 3 -maxdepth 3 -path "$RPM_BUILD_ROOT/%{APP_DIR}/plugins/*/themes" -type d -execdir %{__rm} -rf "{}" \;
%if %{with enterprise}
%else
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/projectmilestones
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/label
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/crosstracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/document
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/textualreport
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/timetracking
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/dynamic_credentials
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/velocity
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/prometheus_metrics
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/project_ownership
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/taskboard
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/testmanagement
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/projectmilestones
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/label
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/crosstracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/document
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/project_ownership
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/taskboard
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/testmanagement
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/timetracking
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/velocity
%endif
%if %{with experimental}
%else
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/oauth2_server
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/oauth2_server
%endif
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/*.js
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/*.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/composer.lock
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/scripts/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/css
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/images
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/node_modules
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/package-lock.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/package.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/css
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/images
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/common
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/tlp
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/tlp-doc
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/gerrit_setup
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/githooks
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/version_numbers
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/autoload.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-mo.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-po.php
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/run_dev/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/scripts/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/php73/run.sh

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
%{__ln_s} %{APP_DIR}/src/tuleap-cfg/tuleap-cfg.php $RPM_BUILD_ROOT/%{_bindir}/tuleap-cfg

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
%{__install} src/etc/tuleap-uploaded-images.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/tuleap-uploaded-images.conf
%{__install} -d $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/
%{__install} src/etc/00-common.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/00-common.conf
%{__install} src/etc/02-themes.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/02-themes.conf
%{__install} src/etc/03-plugins.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/03-plugins.conf
%{__install} src/etc/04-cgi.conf.dist $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-aliases/04-cgi.conf

# Sudoers directory
%{__install} -d $RPM_BUILD_ROOT/etc/sudoers.d
%{__install} src/utils/sudoers.d/tuleap_fileforge $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/tuleap_fileforge

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
%{__install} -D plugins/git/etc/sudoers.d/gitolite-access-command $RPM_BUILD_ROOT/etc/sudoers.d/gitolite-access-command
%{__install} -D plugins/git/bin/TULEAP_MAX_NEWBIN_SIZE $RPM_BUILD_ROOT/usr/share/gitolite3/VREF/TULEAP_MAX_NEWBIN_SIZE

#codendiadm > gitolite sudo
%{__install} plugins/git/etc/sudoers.d/gitolite $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite
%{__perl} -pi -e "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite

# Plugin gitlfs
%{__install} plugins/gitlfs/etc/sudoers.d/tuleap_gitlfs_authenticate $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitlfs_authenticate
%{__install} -m 755 -d $RPM_BUILD_ROOT/usr/share/gitolite3/commands/
%{__ln_s} %{APP_DIR}/plugins/gitlfs/bin/git-lfs-authenticate $RPM_BUILD_ROOT/usr/share/gitolite3/commands/git-lfs-authenticate

# Plugin PullRequest
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

# Plugin proftpd
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/secure_ftp

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

# Plugin create_test_env
%{__install} plugins/create_test_env/etc/sudoers.d/tuleap_plugin_create_test_env $RPM_BUILD_ROOT/%{_sysconfdir}/sudoers.d

# Plugin LDAP
%{__install} plugins/ldap/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap

# Plugin OpenID Connect Client
%{__install} plugins/openidconnectclient/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client

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
    getent group gitolite >/dev/null || groupadd -r gitolite

    if getent passwd gitolite >/dev/null; then
        /usr/sbin/usermod -c 'Git'    -d '/var/lib/gitolite' -g gitolite gitolite
    else
        /usr/sbin/useradd -r -c 'Git' -m -d '/var/lib/gitolite' -g gitolite gitolite
    fi

else
    true
fi

echo 'source /opt/rh/sclo-git212/enable' > /var/lib/gitolite/.profile
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

# Clean old tuleap cache file
%{__rm} -f %{APP_CACHE_DIR}/tuleap_hooks_cache

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

%{__rm} -f /var/lib/gitolite/.gitolite/hooks/common/post-receive 2> /dev/null || :
%{__ln_s} %{APP_DIR}/plugins/git/hooks/post-receive-gitolite /var/lib/gitolite/.gitolite/hooks/common/post-receive
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
%{APP_DIR}/LICENSE
%if %{with enterprise}
%{APP_DIR}/ENTERPRISE_BUILD
%endif
# Split src for src/www/themes
%dir %{APP_DIR}/src
%{APP_DIR}/src/glyphs
%{APP_DIR}/src/common
%{APP_DIR}/src/core
%{APP_DIR}/src/vendor
%{APP_DIR}/src/embedded_vendor
%{APP_DIR}/src/db
%{APP_DIR}/src/etc
%{APP_DIR}/src/forgeupgrade
%{APP_DIR}/src/templates
%{APP_DIR}/src/tuleap-cfg
%{APP_DIR}/src/utils
# Split src/www for src/www/themes
%dir %{APP_DIR}/src/www
%{APP_DIR}/src/www/index.php
%{APP_DIR}/src/www/account
%{APP_DIR}/src/www/admin
# API Explorer is not packaged with the core
%dir %{APP_DIR}/src/www/api
%{APP_DIR}/src/www/api/index.php
%{APP_DIR}/src/www/api/reference
%dir %{APP_DIR}/src/www/assets
%{APP_DIR}/src/www/assets/core
%{APP_DIR}/src/www/assets/admindelegation
%{APP_DIR}/src/www/assets/docman
%{APP_DIR}/src/www/assets/pluginsadministration
%{APP_DIR}/src/www/assets/projectlinks
%{APP_DIR}/src/www/assets/statistics
%{APP_DIR}/src/www/assets/userlog
%{APP_DIR}/src/www/cvs
%{APP_DIR}/src/www/favicon.ico
%{APP_DIR}/src/www/file
%{APP_DIR}/src/www/forum
%{APP_DIR}/src/www/help
%{APP_DIR}/src/www/include
%{APP_DIR}/src/www/mail
%{APP_DIR}/src/www/my
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
%{APP_DIR}/src/www/themes/common
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
%attr(00755,root,root) %{_bindir}/tuleap-cfg

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
%attr(00755,root,root) /etc/rc.d/init.d/%{APP_NAME}
%attr(00644,root,root) /etc/cron.d/%{APP_NAME}
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_syslog
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}
%dir /etc/httpd/conf.d/tuleap-plugins
%attr(04755,root,root) /etc/httpd/conf.d/tuleap-plugins/tuleap-uploaded-images.conf
%dir /etc/httpd/conf.d/tuleap-aliases
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/00-common.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/02-themes.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/03-plugins.conf
%attr(00644,root,root) /etc/httpd/conf.d/tuleap-aliases/04-cgi.conf

# Sudoers
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_fileforge

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

%files core-subversion
%defattr(-,%{APP_USER},%{APP_USER},-)

%files core-subversion-modperl
%defattr(-,root,root,-)
%{perl_vendorlib}/Apache/Tuleap.pm

%files core-cvs
%defattr(-,%{APP_USER},%{APP_USER},-)

#
# Plugins
#
%files plugin-forumml
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/forumml
%{APP_DIR}/src/www/assets/forumml
%attr(00750,%{APP_USER},%{APP_USER}) %{_localstatedir}/run/forumml
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_forumml

%files plugin-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/git
%{APP_DIR}/src/www/assets/git
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
%{APP_DIR}/src/www/assets/git
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
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/gitolite-access-command
%attr(00755,root,root) /usr/share/gitolite3/VREF/TULEAP_MAX_NEWBIN_SIZE

%files plugin-gitlfs
%defattr(-,root,root,-)
%{APP_DIR}/plugins/gitlfs
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitlfs_authenticate
/usr/share/gitolite3/commands/git-lfs-authenticate

%files plugin-pullrequest
%defattr(-,root,root,-)
%{APP_DIR}/plugins/pullrequest
%{APP_DIR}/src/www/assets/pullrequest
%attr(00644,root,root) /etc/logrotate.d/tuleap_pullrequest
%config(noreplace) /etc/logrotate.d/tuleap_pullrequest

%files plugin-ldap
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/ldap
%{APP_DIR}/src/www/assets/ldap
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_ldap
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_ldap

%files plugin-hudson
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson
%{APP_DIR}/src/www/assets/hudson

%files plugin-hudson-svn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson_svn
%{APP_DIR}/src/www/assets/hudson_svn
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_svn
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_svn

%files plugin-hudson-git
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/hudson_git
%{APP_DIR}/src/www/assets/hudson_git
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_git
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_git

%files plugin-webdav
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav

%files plugin-svn
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/svn
%{APP_DIR}/src/www/assets/svn
%dir %{APP_DATA_DIR}/svn_plugin

%files plugin-tracker
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/tracker
%{APP_DIR}/src/www/assets/trackers
%dir %{APP_DATA_DIR}/tracker
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_tracker
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_tracker

%files plugin-graphontrackers
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/graphontrackersv5
%{APP_DIR}/src/www/assets/graphontrackersv5

%files plugin-tracker-encryption
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/tracker_encryption
%{APP_DIR}/src/www/assets/tracker_encryption

%files plugin-cardwall
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/cardwall
%{APP_DIR}/src/www/assets/cardwall

%files plugin-agiledashboard
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/agiledashboard
%{APP_DIR}/src/www/assets/agiledashboard
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_agiledashboard
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_agiledashboard

%files plugin-archivedeleteditems
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/archivedeleteditems
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems

%files plugin-fusionforge_compat

%files plugin-mediawiki
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/mediawiki
%{APP_DIR}/src/www/assets/mediawiki
%dir %{APP_DATA_DIR}/mediawiki
%dir %{APP_DATA_DIR}/mediawiki/master
%dir %{APP_DATA_DIR}/mediawiki/projects

%files plugin-openidconnectclient
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/openidconnectclient
%{APP_DIR}/src/www/assets/openidconnectclient
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_openid_connect_client
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_openid_connect_client

%files plugin-proftpd
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/proftpd
%{APP_DIR}/src/www/assets/proftpd
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/secure_ftp

%files plugin-frs
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/frs
%{APP_DIR}/src/www/assets/frs

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
%{APP_DIR}/src/www/assets/artifactsfolders

%files plugin-captcha
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/captcha
%{APP_DIR}/src/www/assets/captcha

%files plugin-bugzilla-reference
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/bugzilla_reference
%{APP_DIR}/src/www/assets/bugzilla_reference
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_bugzilla_reference

%files plugin-create-test-env
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/create_test_env
%attr(00400,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_create_test_env

%files plugin-api-explorer
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/plugins/api_explorer
%{APP_DIR}/src/www/assets/api-explorer

%if %{with enterprise}

%files plugin-crosstracker
%defattr(-,root,root,-)
%{APP_DIR}/plugins/crosstracker
%{APP_DIR}/src/www/assets/crosstracker

%files plugin-document
%defattr(-,root,root,-)
%{APP_DIR}/plugins/document
%{APP_DIR}/src/www/assets/document

%files plugin-dynamic-credentials
%defattr(-,root,root,-)
%{APP_DIR}/plugins/dynamic_credentials

%files plugin-label
%defattr(-,root,root,-)
%{APP_DIR}/plugins/label
%{APP_DIR}/src/www/assets/label

%files plugin-project-ownership
%defattr(-,root,root,-)
%{APP_DIR}/plugins/project_ownership
%{APP_DIR}/src/www/assets/project_ownership

%files plugin-projectmilestones
%defattr(-,root,root,-)
%{APP_DIR}/plugins/projectmilestones
%{APP_DIR}/src/www/assets/projectmilestones

%files plugin-prometheus-metrics
%defattr(-,root,root,-)
%{APP_DIR}/plugins/prometheus_metrics

%files plugin-taskboard
%defattr(-,root,root,-)
%{APP_DIR}/plugins/taskboard
%{APP_DIR}/src/www/assets/taskboard

%files plugin-testmanagement
%defattr(-,root,root,-)
%{APP_DIR}/plugins/testmanagement
%{APP_DIR}/src/www/assets/testmanagement

%files plugin-textualreport
%defattr(-,root,root,-)
%{APP_DIR}/plugins/textualreport

%files plugin-timetracking
%defattr(-,root,root,-)
%{APP_DIR}/plugins/timetracking
%{APP_DIR}/src/www/assets/timetracking

%files plugin-velocity
%defattr(-,root,root,-)
%{APP_DIR}/plugins/velocity
%{APP_DIR}/src/www/assets/velocity

%endif

%if %{with experimental}

%files plugin-oauth2-server
%defattr(-,root,root,-)
%{APP_DIR}/plugins/oauth2_server
%{APP_DIR}/src/www/assets/oauth2_server

%endif

#
# Themes
#

%files theme-flamingparrot
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/themes/FlamingParrot
%{APP_DIR}/src/www/themes/FlamingParrot

%files theme-burningparrot
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/src/themes/BurningParrot
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
