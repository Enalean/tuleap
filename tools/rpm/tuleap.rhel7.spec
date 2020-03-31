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

# Php and web related stuff
Requires: php73-php-common >= 7.3.15
Requires: php73-php, php73-php-mysql, php73-php-xml, php73-php-json, php73-php-mbstring, php73-php-gd, php73-php-soap
Requires: php73-php-intl, php73-php-process, php73-php-opcache, php73-php-fpm, php73-php-pecl-redis, php73-php-sodium
Requires: php73-php-pecl-zip
Requires: rh-mysql57-mysql

Requires: perl-DBI, perl-DBD-MySQL, sudo
Requires: highlight, forgeupgrade >= 1.6, nginx, logrotate

# Unit file
Requires: systemd

# It's embedded in Tuleap thanks to npm.
Obsoletes: ckeditor

%description
Tuleap is a web based application that address all the aspects of product development.

#
## Core component definitions
#

%package core-mailman
Summary: Mailman component for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Requires: mailman-tuleap
%description core-mailman
Manage dependencies for Tuleap mailman integration

%package core-cvs
Summary: CVS component for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, rcs, cvsgraph, perl-CGI
Requires: viewvc, viewvc-theme-tuleap >= 1.0.7
Requires: cvs-tuleap
Requires: libnss-mysql, nss, nscd
Requires: perl-Text-Iconv
%description core-cvs
Manage dependencies for Tuleap CVS integration


%package core-subversion
Summary: Subversion component for Tuleap
Group: Development/Tools
Version: 1.2
Release: @@VERSION@@_@@RELEASE@@%{?dist}
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, mod_dav_svn
Requires: viewvc, viewvc-theme-tuleap >= 1.0.7
Requires: python, MySQL-python
Requires: mod_perl, perl-Digest-SHA, perl-DBI, perl-DBD-MySQL, perl(Crypt::Eksblowfish::Bcrypt), perl(Redis)
Requires: tuleap-theme-flamingparrot
Requires: sha1collisiondetector
%description core-subversion
Manage dependencies for Tuleap Subversion integration

#
## Plugins
#

%package plugin-forumml
Summary: ForumML plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, php73-php-pecl-mailparse
Requires: tuleap-core-mailman
%description plugin-forumml
ForumML brings to Tuleap a very nice mail archive viewer and the possibility
to send mails through the web interface. It can replace the forums.

%package plugin-svn
Summary: Subversion plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-core-subversion
%description plugin-svn
Integration of Subversion software configuration management tool with Tuleap.

%package plugin-git
Summary: Git plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires(pre): shadow-utils
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, sclo-git212-git, gitolite3
Requires: sudo, openssh-server
%description plugin-git
Integration of git distributed software configuration management tool together
with Tuleap.
This package is integrated with gitolite v3 (new version)

%package plugin-gitlfs
Summary: Support of large file upload and download in Git
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, %{name}-plugin-git, sudo
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
Requires: php73-php-ldap, perl-LDAP
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
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-docman
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

%package plugin-docman
Summary: Docman plugin for Tuleap
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-docman
Share your data with project members.

%package plugin-api-explorer
Summary: Web API Explorer
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
Obsoletes: tuleap-api-explorer
Provides: tuleap-api-explorer
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
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}, tuleap-plugin-docman
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

%package plugin-tee-container
Summary: Tuleap Enterprise Edition containers management
Group: Development/Tools
Requires: %{name} = @@VERSION@@-@@RELEASE@@%{?dist}
%description plugin-tee-container
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
# Remove old scripts: not used and add unneeded perl depedencies to the package
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanUploader.pl
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanLegacyDownloader.pl
# No need of template
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
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tee_container
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/projectmilestones
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/label
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/crosstracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/document
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/project_ownership
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/taskboard
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/timetracking
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/velocity
%endif
%if %{with experimental}
%else
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/oauth2_server
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/assets/oauth2_server
%endif
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/composer.lock
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/gerrit_setup
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/githooks
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/version_numbers
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/autoload.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-mo.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/generate-po.php
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/run_dev/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/scripts/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/tools/utils/php73/run.sh
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/FlamingParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/BurningParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/package.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/webpack.*.js

# Link to local config for logo and themes images
# Needed for nginx try_files
%{__ln_s} /etc/%{APP_NAME}/themes/common/images $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/local

# Data dir
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}
%{__install} -m 700 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/user
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/images
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/incoming
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/codendi
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/codendi/DELETED
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/pub


# Install systemd Unit
%{__install} -d $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-workers.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-worker@.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-svn-updater.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-php-fpm.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-default.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-default.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-statistics.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-statistics.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-tv3-tv5-migration.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-process-system-events-tv3-tv5-migration.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-system-check.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-system-check.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-daily-event.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-daily-event.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-plugin-job.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-launch-plugin-job.service $RPM_BUILD_ROOT/%{_unitdir}

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
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/cvsroot
%{__install} -d $RPM_BUILD_ROOT/var/lock/cvs
%{__install} -d $RPM_BUILD_ROOT/var/run/log_accum
%{__install} src/utils/svn/commit-email.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/codendi_svn_pre_commit.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/pre-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/svn/post-revprop-change.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/fileforge.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/fileforge

# Special custom include script
%{__install} src/etc/env.inc.php.dist $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php
%{__perl} -pi -e "s~%include_path%~%{APP_PHP_INCLUDE_PATH}~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/env.inc.php
#
## Install logrotate.d script
%{__install} -d $RPM_BUILD_ROOT/%{_sysconfdir}/logrotate.d
# Replace default httpd logrotate by ours
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

# Sudoers directory
%{__install} -d $RPM_BUILD_ROOT/etc/sudoers.d
%{__install} src/utils/sudoers.d/tuleap_fileforge $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/tuleap_fileforge


## plugin webdav
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/plugins/webdav/locks

## plugin forumml
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/forumml
%{__install} plugins/forumml/etc/sudoers.d/tuleap_plugin_forumml $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/tuleap_plugin_forumml


# plugin-git
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/repositories
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/grokmirror
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/templates_c
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/cache
%{__install} plugins/git/bin/sudo/gl-membership.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__perl} -pi -e "s~%%app_user%%~%{APP_USER}~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/gl-membership.pl
%{__perl} -pi -e "s~%app_path%~/usr/share/tuleap~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/gl-membership.pl
%{__install} plugins/git/bin/restore-tar-repository.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/bin/gitolite3-suexec-wrapper.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/etc/sudoers.d/gitolite3-http $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__perl} -pi -e "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__install} plugins/git/etc/sudoers.d/gitolite3-replace-authorized-keys $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_replace_authorized_keys
%{__install} plugins/git/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__install} plugins/git/etc/sudoers.d/tuleap-git-postreceive $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_postreceive
%{__install} plugins/git/etc/sudoers.d/tuleap-plugin-git $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__perl} -pi -e "s~%%app_user%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__perl} -pi -e "s~%app_path%~/usr/share/tuleap~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__install} -D plugins/git/etc/sudoers.d/gitolite-access-command $RPM_BUILD_ROOT/etc/sudoers.d/gitolite-access-command
%{__install} -D plugins/git/bin/TULEAP_MAX_NEWBIN_SIZE $RPM_BUILD_ROOT/usr/share/gitolite3/VREF/TULEAP_MAX_NEWBIN_SIZE
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-git.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-git.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-grokmirror.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-grokmirror.service $RPM_BUILD_ROOT/%{_unitdir}

#
##codendiadm > gitolite sudo
%{__install} plugins/git/etc/sudoers.d/gitolite $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite
%{__perl} -pi -e "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite

# Plugin gitlfs
%{__install} plugins/gitlfs/etc/sudoers.d/tuleap_gitlfs_authenticate $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitlfs_authenticate
%{__install} -m 755 -d $RPM_BUILD_ROOT/usr/share/gitolite3/commands/
%{__ln_s} %{APP_DIR}/plugins/gitlfs/bin/git-lfs-authenticate $RPM_BUILD_ROOT/usr/share/gitolite3/commands/git-lfs-authenticate

## Plugin PullRequest
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
#
# Plugin agiledashboard
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/agiledashboard
%{__install} plugins/agiledashboard/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard
#
## Plugin mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/master
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/projects
#
## Plugin proftpd
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/secure_ftp
#
## Plugin bugzilla
%{__install} plugins/bugzilla_reference/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
#
## plugin archivedeleted_items
%{__install} plugins/archivedeleteditems/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_archivedeleteditems
#
## Plugin hudson_git
%{__install} plugins/hudson_git/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_hudson_git
#
## Plugin hudson_svn
%{__install} plugins/hudson_svn/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_hudson_svn
#
## Plugin create_test_env
%{__install} plugins/create_test_env/etc/sudoers.d/tuleap_plugin_create_test_env $RPM_BUILD_ROOT/%{_sysconfdir}/sudoers.d
#
# Plugin LDAP
%{__install} plugins/ldap/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap

# Plugin OpenID Connect Client
%{__install} plugins/openidconnectclient/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__perl} -pi -e "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client

# Symlink for compatibility with older version
%{__ln_s} %{APP_DIR} $RPM_BUILD_ROOT/%{_datadir}/codendi

#
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

    # tuleap
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

    # Make user mandatory unix users exist

    # tuleap
    if id %{app_user} >/dev/null 2>&1; then
        /usr/sbin/usermod -c 'Tuleap user'    -d '/var/lib/tuleap'    -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group} %{app_user}
    else
        /usr/sbin/useradd -c 'Tuleap user' -m -d '/var/lib/tuleap' -r -g "%{app_group}" -s '/bin/bash' -G %{ftpadmin_group} %{app_user}
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
    true
fi

%pre plugin-git
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

chmod 750 /var/lib/gitolite

#
#
#
%post
if [ $1 -eq 1 ]; then
    /usr/bin/systemctl enable \
        tuleap.service \
        tuleap-workers.service \
        tuleap-php-fpm.service &>/dev/null || :
    /usr/bin/systemctl mask php73-php-fpm || :
fi

# Clean old tuleap cache file
%{__rm} -f %{APP_CACHE_DIR}/tuleap_hooks_cache

%post core-cvs
if [ ! -f %{_sysconfdir}/shells ] ; then
    echo "%{APP_LIBBIN_DIR}/cvssh" > %{_sysconfdir}/shells
    echo "%{APP_LIBBIN_DIR}/cvssh-restricted" > %{_sysconfdir}/shells
else
    grep -q "^%{APP_LIBBIN_DIR}/cvssh$" %{_sysconfdir}/shells || echo "%{APP_LIBBIN_DIR}/cvssh" >> %{_sysconfdir}/shells
    grep -q "^%{APP_LIBBIN_DIR}/cvssh-restricted$" %{_sysconfdir}/shells || echo "%{APP_LIBBIN_DIR}/cvssh-restricted" >> %{_sysconfdir}/shells
fi

%post core-subversion
/usr/bin/systemctl daemon-reload &>/dev/null || :

#
# Post install of git plugin
%post plugin-git
# add codendiadm to gitolite group
if ! groups codendiadm | grep -q gitolite 2> /dev/null ; then
    usermod -a -G gitolite codendiadm
fi

%preun
if [ $1 -eq 0 ]; then
    /usr/bin/systemctl stop tuleap.service &>/dev/null || :

    /usr/bin/systemctl disable \
        tuleap.service \
        tuleap-workers.service \
        tuleap-php-fpm.service &>/dev/null || :
fi

%preun core-subversion
if [ $1 -eq 0 ]; then
    /usr/bin/systemctl stop tuleap.service &>/dev/null || :
fi

%postun
/usr/bin/systemctl unmask php73-php-fpm || :
/usr/bin/systemctl daemon-reload &>/dev/null || :

%postun core-cvs
if [ "$1" = 0 ] && [ -f %{_sysconfdir}/shells ] ; then
    sed -i '\!^%{APP_LIBBIN_DIR}/cvssh$!d' %{_sysconfdir}/shells
    sed -i '\!^%{APP_LIBBIN_DIR}/cvssh-restricted$!d' %{_sysconfdir}/shells
fi

%postun core-subversion
/usr/bin/systemctl daemon-reload &>/dev/null || :

%clean
%{__rm} -rf $RPM_BUILD_ROOT

#
#
#
%files
%defattr(-,root,root,-)
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
%{APP_DIR}/src/www/assets/*.js
%{APP_DIR}/src/www/assets/manifest.json
%{APP_DIR}/src/www/assets/account
%{APP_DIR}/src/www/assets/admindelegation
%{APP_DIR}/src/www/assets/ckeditor-*
%{APP_DIR}/src/www/assets/dashboards
%{APP_DIR}/src/www/assets/pluginsadministration
%{APP_DIR}/src/www/assets/project-registration
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
%dir %{APP_DIR}/src/www/themes/common
%{APP_DIR}/src/www/themes/common/assets
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
%{APP_DIR}/plugins/pluginsadministration
%{APP_DIR}/plugins/projectlinks
%{APP_DIR}/plugins/statistics
%{APP_DIR}/plugins/tracker_date_reminder
%{APP_DIR}/plugins/userlog

# Data dir
%dir %attr(755,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/user
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/images
%dir %attr(0755, root, %{ftp_group}) %{APP_DATA_DIR}/ftp
%dir %attr(3777, %{APP_USER}, %{APP_USER}) %{APP_DATA_DIR}/ftp/incoming
%dir %attr(0711, root, root) %{APP_DATA_DIR}/ftp/codendi
%dir %attr(0750, %{APP_USER}, %{APP_USER}) %{APP_DATA_DIR}/ftp/codendi/DELETED
%dir %attr(0755, %{ftpadmin_user}, %{ftpadmin_group}) %{APP_DATA_DIR}/ftp/pub

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
%attr(04755,root,root) %{APP_LIBBIN_DIR}/fileforge
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_syslog
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}
# Log dir
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LOG_DIR}
%attr(775,%{APP_USER},%{APP_USER}) %dir %{APP_LOG_DIR}/cvslog

# Sudoers
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_fileforge

# Run dir
%attr(00755,%{APP_USER},%{APP_USER}) %dir %{_localstatedir}/run/tuleap

# Unit files
%attr(00644,root,root) %{_unitdir}/tuleap.service
%attr(00644,root,root) %{_unitdir}/tuleap-workers.service
%attr(00644,root,root) %{_unitdir}/tuleap-worker@.service
%attr(00644,root,root) %{_unitdir}/tuleap-php-fpm.service
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-default.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-default.service
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-statistics.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-statistics.service
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-tv3-tv5-migration.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-tv3-tv5-migration.service
%attr(00644,root,root) %{_unitdir}/tuleap-launch-system-check.timer
%attr(00644,root,root) %{_unitdir}/tuleap-launch-system-check.service
%attr(00644,root,root) %{_unitdir}/tuleap-launch-daily-event.timer
%attr(00644,root,root) %{_unitdir}/tuleap-launch-daily-event.service
%attr(00644,root,root) %{_unitdir}/tuleap-launch-plugin-job.timer
%attr(00644,root,root) %{_unitdir}/tuleap-launch-plugin-job.service

# Compatibility with older version
%attr(-,root,root) %{_datadir}/codendi

#
# Core
#
%files core-mailman
%defattr(-,root,root,-)

%files core-subversion
%defattr(-,root,root,-)
%{perl_vendorlib}/Apache/Tuleap.pm
%attr(00644,root,root) %{_unitdir}/tuleap-svn-updater.service

%files core-cvs
%defattr(-,root,root,-)
%attr(00751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/cvsroot
%attr(00751,root,root) /var/lock/cvs
%attr(00777,root,root) /var/run/log_accum

#
# Plugins
#
%files plugin-forumml
%defattr(-,root,root,-)
%{APP_DIR}/plugins/forumml
%{APP_DIR}/src/www/assets/forumml
%attr(00750,%{APP_USER},%{APP_USER}) %{_localstatedir}/run/forumml
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_forumml

%files plugin-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/git
%{APP_DIR}/src/www/assets/git
%dir %{APP_DATA_DIR}/gitolite
%attr(00770,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/repositories
%attr(00775,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/grokmirror
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gl-membership.pl
%attr(00755,root,root) %{APP_LIBBIN_DIR}/gitolite3-suexec-wrapper.sh
%attr(00755,root,root) %{APP_LIBBIN_DIR}/restore-tar-repository.php
%attr(00644,root,root) %{_sysconfdir}/logrotate.d/%{APP_NAME}_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_http
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_postreceive
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_replace_authorized_keys
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/gitolite-access-command
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-git.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-git.service
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-grokmirror.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-grokmirror.service
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
%defattr(-,root,root,-)
%{APP_DIR}/plugins/ldap
%{APP_DIR}/src/www/assets/ldap
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_ldap
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_ldap

%files plugin-hudson
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson
%{APP_DIR}/src/www/assets/hudson

%files plugin-hudson-svn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson_svn
%{APP_DIR}/src/www/assets/hudson_svn
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_svn
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_svn

%files plugin-hudson-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson_git
%{APP_DIR}/src/www/assets/hudson_git
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_git
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_git

%files plugin-webdav
%defattr(-,root,root,-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav

%files plugin-svn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/svn
%{APP_DIR}/src/www/assets/svn
%attr(00750,%{APP_USER},%{APP_USER}) %dir %{APP_DATA_DIR}/svn_plugin

%files plugin-tracker
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tracker
%{APP_DIR}/src/www/assets/trackers
%dir %attr(0750,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/tracker
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_tracker
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_tracker

%files plugin-graphontrackers
%defattr(-,root,root,-)
%{APP_DIR}/plugins/graphontrackersv5
%{APP_DIR}/src/www/assets/graphontrackersv5

%files plugin-tracker-encryption
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tracker_encryption
%{APP_DIR}/src/www/assets/tracker_encryption

%files plugin-cardwall
%defattr(-,root,root,-)
%{APP_DIR}/plugins/cardwall
%{APP_DIR}/src/www/assets/cardwall

%files plugin-agiledashboard
%defattr(-,root,root,-)
%{APP_DIR}/plugins/agiledashboard
%{APP_DIR}/src/www/assets/agiledashboard
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_agiledashboard
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_agiledashboard

%files plugin-archivedeleteditems
%defattr(-,root,root,-)
%{APP_DIR}/plugins/archivedeleteditems
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_archivedeleteditems

%files plugin-mediawiki
%defattr(-,root,root,-)
%{APP_DIR}/plugins/mediawiki
%{APP_DIR}/src/www/assets/mediawiki
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki/master
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki/projects

%files plugin-openidconnectclient
%defattr(-,root,root,-)
%{APP_DIR}/plugins/openidconnectclient
%{APP_DIR}/src/www/assets/openidconnectclient
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_openid_connect_client
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_openid_connect_client

%files plugin-proftpd
%defattr(-,root,root,-)
%{APP_DIR}/plugins/proftpd
%{APP_DIR}/src/www/assets/proftpd
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/secure_ftp

%files plugin-frs
%defattr(-,root,root,-)
%{APP_DIR}/plugins/frs
%{APP_DIR}/src/www/assets/frs

%files plugin-referencealias-core
%defattr(-,root,root,-)
%{APP_DIR}/plugins/referencealias_core

%files plugin-referencealias-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/referencealias_git

%files plugin-referencealias-svn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/referencealias_svn

%files plugin-referencealias-mediawiki
%defattr(-,root,root,-)
%{APP_DIR}/plugins/referencealias_mediawiki

%files plugin-referencealias-tracker
%defattr(-,root,root,-)
%{APP_DIR}/plugins/referencealias_tracker

%files plugin-artifactsfolders
%defattr(-,root,root,-)
%{APP_DIR}/plugins/artifactsfolders
%{APP_DIR}/src/www/assets/artifactsfolders

%files plugin-captcha
%defattr(-,root,root,-)
%{APP_DIR}/plugins/captcha
%{APP_DIR}/src/www/assets/captcha

%files plugin-bugzilla-reference
%defattr(-,root,root,-)
%{APP_DIR}/plugins/bugzilla_reference
%{APP_DIR}/src/www/assets/bugzilla_reference
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_bugzilla_reference

%files plugin-create-test-env
%defattr(-,root,root,-)
%{APP_DIR}/plugins/create_test_env
%attr(00400,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_create_test_env

%files plugin-docman
%defattr(-,root,root,-)
%{APP_DIR}/plugins/docman
%{APP_DIR}/src/www/assets/docman

%files plugin-api-explorer
%defattr(-,root,root,-)
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

%files plugin-tee-container
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tee_container

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
%defattr(-,root,root,-)
%{APP_DIR}/src/www/themes/FlamingParrot

%files theme-burningparrot
%defattr(-,root,root,-)
%{APP_DIR}/src/www/themes/BurningParrot

%changelog
* Thu Mar 23 2017 Matthieu MONNIER <matthieu.monnier@enalean.com> -
- RHEL7 support

* Fri Dec 04 2015 Adrien HAMRAOUI <adrien.hamraoui@sogilis.com> -
- Add gitolite sudoer file installation.

* Mon Oct 11 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Package plugins that matters (solve dependencies install issues).

* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> -
- Initial build.
