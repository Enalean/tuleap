%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d

%define _buildhost tuleap-builder
%define _source_payload w9T8.xzdio
%define _binary_payload w9T8.xzdio

# Define variables
%define PKG_NAME tuleap
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
Version: %{tuleap_version}
Release: %{tuleap_release}%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-src.tar
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Tuleap team <security@tuleap.org>

AutoReqProv: no

# Php and web related stuff
Requires: php82-php-common
Requires: php82-php, php82-php-mysql, php82-php-xml, php82-php-mbstring, php82-php-gd
Requires: php82-php-intl, php82-php-process, php82-php-opcache, php82-php-fpm, php82-php-pecl-redis5, php82-php-sodium
Requires: php82-php-pecl-zip
Requires: php82-php-ffi
%if "%{?dist}" == ".el9"
Requires: glibc-locale-source
%endif

Requires: sudo, openssh
Requires: perl(File::Copy)
Requires: highlight, nginx, logrotate
Requires: tuleap-realtime = %{tuleap_version}
Requires: tuleap-mercure = %{tuleap_version}
Requires: tuleap-smokescreen = %{tuleap_version}
Requires: tuleap-wasmtime-wrapper-lib = %{tuleap_version}

# xmllint
Requires: libxml2

# Unit file
Requires: systemd

# ForgeUpgrade is now provided by Tuleap
Obsoletes: forgeupgrade <= 999
Provides: forgeupgrade

%if "%{?dist}" == ".el7"

Obsoletes: tuleap-plugin-forumml <= 15.0
Provides: tuleap-plugin-forumml

Obsoletes: tuleap-core-mailman <= 15.0
Provides: tuleap-core-mailman

Obsoletes: tuleap-core-cvs <= 15.0
Provides: tuleap-core-cvs

%endif


%description
Tuleap is a web based application that address all the aspects of product development.


#
## Core component definitions
#

%package core-subversion
Summary: Subversion component for Tuleap
Group: Development/Tools
Version: 1.2
Release: %{tuleap_version}_%{tuleap_release}%{?dist}
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, mod_dav_svn
%if "%{?dist}" == ".el9"
Requires: viewvc-tuleap, httpd, glibc-langpack-en
%else
Requires: viewvc >= 1.1.30, python
%endif
Requires: viewvc-theme-tuleap >= 1.0.8
Requires: tuleap-theme-flamingparrot
Requires: sha1collisiondetector
%description core-subversion
Manage dependencies for Tuleap Subversion integration

#
## Plugins
#

%package plugin-svn
Summary: Subversion plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-core-subversion
%description plugin-svn
Integration of Subversion software configuration management tool with Tuleap.

%package plugin-git
Summary: Git plugin for Tuleap
Group: Development/Tools
AutoReqProv: no
Requires(pre): shadow-utils
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, %{name}-git-bin = %{tuleap_version}, %{name}-gitolite3 = %{tuleap_version}
Requires: sudo, openssh-server
%description plugin-git
Integration of git distributed software configuration management tool together
with Tuleap.
This package is integrated with gitolite v3 (new version)

%package plugin-gitlfs
Summary: Support of large file upload and download in Git
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, %{name}-plugin-git, sudo
Group: Development/Tools
%description plugin-gitlfs
%{summary}.

%package plugin-pullrequest
Summary: Pullrequest management for Tuleap
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, %{name}-plugin-git, %{name}-git-bin = %{tuleap_version}, grep
Group: Development/Tools
%description plugin-pullrequest
%{summary}.

%package plugin-ldap
Summary: Tuleap plugin to manage LDAP integration
Group: Development/Tools
Requires: php82-php-ldap
%description plugin-ldap
LDAP Plugin for Tuleap. Provides LDAP information, LDAP
authentication, user and group management.

%package plugin-hudson
Summary: Hudson plugin for Tuleap
Group: Development/Tools/Building
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-hudson
Plugin to install the Tuleap Hudson plugin for continuous integration

%package plugin-hudson-svn
Summary: Hudson/Jenkins plugin for Tuleap SVN multiple repositories
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-hudson, tuleap-plugin-svn
%description plugin-hudson-svn
Hudson/Jenkins plugin for Tuleap SVN multiple repositories

%package plugin-hudson-git
Summary: Hudson/Jenkins plugin for Tuleap Git repositories
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-hudson, tuleap-plugin-git
%description plugin-hudson-git
Hudson/Jenkins plugin for Tuleap Git repositories

%package plugin-webdav
Summary: WebDAV plugin for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-document
%description plugin-webdav
Plugin to access to file releases & docman though WebDAV

%package plugin-tracker
AutoReqProv: no
Summary: Tracker v5 for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, libxslt, php82-php-pecl-mailparse
%description plugin-tracker
New tracker generation for Tuleap.

%package plugin-graphontrackers
Summary: Graphs for Tracker v5
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker >= 0.8.4
%description plugin-graphontrackers
Graphs for new tracker generation

%package plugin-tracker-encryption
Summary: Encryption for tracker
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-tracker-encryption
Adding a new type of tracker fields that are encrypted.
This plugin is still in beta.

%package plugin-cardwall
Summary: Graphs for Tracker v5
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Requires: tuleap-plugin-tracker
%description plugin-cardwall
Fancy cardwall output on top of Tracker v5

%package plugin-agiledashboard
Summary: Agile dashboard
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-cardwall
%description plugin-agiledashboard
Agile Dashboard aims to provide an nice integration of Scrum/Kanban
tool on top of Tracker.

%package plugin-archivedeleteditems
Summary: Archiving plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-archivedeleteditems
Archive deleted items before purging them from filesystem

%package plugin-mediawiki
Summary: Mediawiki plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Requires: php-mediawiki-tuleap-123 >= 1.23.9-17, tuleap-plugin-mediawiki-standalone
%if "%{?dist}" == ".el7"
Requires: htmldoc
%endif
%description plugin-mediawiki
This plugin provides Mediawiki integration in Tuleap.

%package plugin-mediawiki-standalone
Summary: MediaWiki Standalone plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Requires: mediawiki-%{name}-flavor-current-lts = %{tuleap_version}, mediawiki-%{name}-flavor-1.35 = %{tuleap_version}
%description plugin-mediawiki-standalone
%{summary}.

%package plugin-onlyoffice
Summary: Integration with ONLYOFFICE
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-document
%description plugin-onlyoffice
%{summary}.

%package plugin-openidconnectclient
Summary: OpenId consumer plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-openidconnectclient
Connect to Tuleap using an OpenID Connect provider

%package plugin-frs
AutoReqProv: no
Summary: File release system plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker
%description plugin-frs
Add features to the file release system provided by Tuleap

%package plugin-captcha
Summary: Add a captcha protection to sensitive operations
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-captcha
%{summary}.

%package plugin-bugzilla-reference
Summary: References between Bugzilla and Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-bugzilla-reference
%{summary}.

%package plugin-create-test-env
Summary: Create test environment on a Tuleap server
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-botmattermost
%description plugin-create-test-env
%{summary}.

%package plugin-document
Summary: Document plugin for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Obsoletes: tuleap-plugin-docman < 13.10
Provides: tuleap-plugin-docman
%description plugin-document
%{summary}.

%package plugin-api-explorer
Summary: Web API Explorer
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Obsoletes: tuleap-api-explorer <= 999
Provides: tuleap-api-explorer
%description plugin-api-explorer
%{summary}.

%package plugin-embed
Summary: Embed various services in artifacts
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-embed
%{summary}.

%package plugin-gitlab
Summary: Provides an integration GitLab to Tuleap.
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-git
%description plugin-gitlab
%{summary}.

%package plugin-securitytxt
Summary: Add support of security.txt file (RFC 9116)
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-securitytxt
%{summary}.

%package plugin-botmattermost
Summary: Bot Mattermost management for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-botmattermost
%{summary}.

%package plugin-botmattermost-agiledashboard
Summary: Bot Mattermost AgileDashboard - Stand up summary
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-botmattermost, tuleap-plugin-agiledashboard
%description plugin-botmattermost-agiledashboard
%{summary}.

%package plugin-botmattermost-git
Summary: Bot Mattermost git - Git Notification
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-botmattermost, tuleap-plugin-git
%description plugin-botmattermost-git
%{summary}.

%if %{with enterprise}

%package plugin-baseline
Summary: Set and compare baselines
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist},  tuleap-plugin-tracker
%description plugin-baseline
%{summary}.

%package plugin-crosstracker
Summary: Cross tracker search widget
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist},  tuleap-plugin-tracker
%description plugin-crosstracker
%{summary}.

%package plugin-dynamic-credentials
Summary: Dynamic credentials generation
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-dynamic-credentials
%{summary}.

%package plugin-label
Summary: Label widget
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-label
%{summary}.

%package plugin-roadmap
Summary: Displays roadmap in a widget
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker
%description plugin-roadmap
%{summary}.

%package plugin-fts-common
Summary: Common parts of full-Text search backends
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-fts-common
%{summary}.

%package plugin-fts-db
Summary: Full-Text search DB backend
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-fts-common
%description plugin-fts-db
%{summary}.

%package plugin-fts-meilisearch
Summary: Full-Text search Meilisearch backend
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-fts-common
%description plugin-fts-meilisearch
%{summary}.

%package plugin-oauth2-server
Summary: OAuth2 Server
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-oauth2-server
%{summary}.

%package plugin-project-ownership
Summary: Project ownership
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Obsoletes: tuleap-plugin-project-certification < 999
%description plugin-project-ownership
%{summary}.

%package plugin-projectmilestones
Summary: A widget for milestones monitoring
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-agiledashboard
%description plugin-projectmilestones
%{summary}.

%package plugin-prometheus-metrics
Summary: Prometheus metrics end point
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-prometheus-metrics
%{summary}.

%package plugin-taskboard
Summary: Taskboard
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-agiledashboard
%description plugin-taskboard
%{summary}.

%package plugin-tee-container
Summary: Tuleap Enterprise Edition containers management
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-tee-container
%{summary}.

%package plugin-testmanagement
Summary: Test Management plugin for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-agiledashboard
%description plugin-testmanagement
%{summary}.

%package plugin-testplan
Summary: Integration between the agiledashboard and the testmanagement plugins
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-agiledashboard, tuleap-plugin-testmanagement
%description plugin-testplan
%{summary}.

%package plugin-timetracking
Summary: Timetracking plugin for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-timetracking
%{summary}.

%package plugin-velocity
Summary: Velocity chart
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-agiledashboard
%description plugin-velocity
%{summary}.

%package plugin-jira-import
Summary: Import Jira Projects
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-cardwall, tuleap-plugin-agiledashboard, tuleap-plugin-projectmilestones
%description plugin-jira-import
%{summary}.

%package plugin-program_management
Summary: Program Management
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker, tuleap-plugin-cardwall, tuleap-plugin-agiledashboard
%description plugin-program_management
%{summary}.

%package plugin-document_generation
Summary: Document Generation
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}, tuleap-plugin-tracker
Obsoletes: tuleap-plugin-textualreport < 13.4
Provides: tuleap-plugin-textualreport
%description plugin-document_generation
%{summary}.

%package plugin-mytuleap-contact-support
Summary: myTuleap Contact support
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-mytuleap-contact-support
%{summary}.

%package plugin-enalean-licensemanager
Summary: Manage usage of license for Tuleap
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-enalean-licensemanager
%{summary}.

%package plugin-webauthn
Summary: WebAuthn plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description plugin-webauthn
Allow users to register and use passkeys with WebAuthn protocol.

%package plugin-tracker-functions
Summary: Tracker Functions plugin
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
Obsoletes: tuleap-plugin-tracker-cce < 15.5
Provides: tuleap-plugin-tracker-cce
%description plugin-tracker-functions
%{summary}.

%endif

%if %{with experimental}

%endif

#
## Themes
#

%package theme-flamingparrot
Summary: FlamingParrot, default theme starting Tuleap 7
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description theme-flamingparrot
FlamingParrot, default theme starting Tuleap 7

%package theme-burningparrot
Summary: BurningParrot, default theme starting Tuleap 10
Group: Development/Tools
Requires: %{name} = %{tuleap_version}-%{tuleap_release}%{?dist}
%description theme-burningparrot
BurningParrot, default theme starting Tuleap 10

#
# Package setup
%prep
%setup -q -c tuleap-src

#
# Build
%build
%if %{with enterprise}
echo '%{tuleap_version}-%{tuleap_release}' > VERSION
%endif

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

#
# Install tuleap application
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}
for i in tools plugins site-content src VERSION LICENSE preload.php; do
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

%if %{with enterprise}
%else
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/projectmilestones
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/label
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/roadmap
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/baseline
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/crosstracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/timetracking
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/dynamic_credentials
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/velocity
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/prometheus_metrics
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/project_ownership
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/taskboard
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tee_container
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/testmanagement
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/testplan
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/fts_common
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/fts_db
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/fts_meilisearch
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/oauth2_server
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/document_generation
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/jira_import
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/program_management
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/mytuleap_contact_support
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/enalean_licensemanager
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/webauthn
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tracker_functions
%endif

%if %{with experimental}
%else
%endif

%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/css
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/images
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/node_modules
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/pnpm-lock.yaml
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/BurningParrot/package.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/composer.json
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/css
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/FlamingParrot/images
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/themes/common
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/src/www/tlp-doc

# Configuration
%{__install} -d $RPM_BUILD_ROOT/etc/%{APP_NAME}
%{__install} -d $RPM_BUILD_ROOT/etc/%{APP_NAME}/conf
%{__install} -d $RPM_BUILD_ROOT/etc/%{APP_NAME}/plugins
%{__install} -d $RPM_BUILD_ROOT/etc/%{APP_NAME}/plugins/pluginsadministration

# PHP configuration
%{__install} -d $RPM_BUILD_ROOT/etc/opt/remi/php82/php.d/
%{__install} src/etc/php.d/99-tuleap.ini $RPM_BUILD_ROOT/etc/opt/remi/php82/php.d/

# Data dir
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}
%{__install} -m 700 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/user
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/images
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/incoming
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/codendi
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/codendi/DELETED
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/pub
%{__ln_s} %{APP_DATA_DIR}/ftp/codendi $RPM_BUILD_ROOT/%{APP_DATA_DIR}/ftp/tuleap

# Install systemd Unit
%{__install} -d $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-workers.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} src/utils/systemd/tuleap-worker@.service $RPM_BUILD_ROOT/%{_unitdir}
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

# Install system tmpfiles
%{__install} -d $RPM_BUILD_ROOT/%{_tmpfilesdir}
%{__install} src/utils/systemd/tmpfiles/tuleap.conf $RPM_BUILD_ROOT/%{_tmpfilesdir}

# Install Tuleap executables
%{__install} -d $RPM_BUILD_ROOT/%{_bindir}
%{__install} src/utils/tuleap $RPM_BUILD_ROOT/%{_bindir}/tuleap
%{__ln_s} %{APP_DIR}/src/tuleap-cfg/tuleap-cfg.php $RPM_BUILD_ROOT/%{_bindir}/tuleap-cfg

%{__install} -d $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/gotohell $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} src/utils/fileforge.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/fileforge

#
## Install logrotate.d script
%{__install} -d $RPM_BUILD_ROOT/%{_sysconfdir}/logrotate.d
# Replace default httpd logrotate by ours
%{__install} src/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_syslog

# Cache dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/php
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/php/session

# Log dir
%{__install} -d $RPM_BUILD_ROOT/%{APP_LOG_DIR}

# Run dir
%{__install} -d $RPM_BUILD_ROOT/%{_localstatedir}/run/tuleap

# Sudoers directory
%{__install} -d $RPM_BUILD_ROOT/etc/sudoers.d
%{__install} src/utils/sudoers.d/tuleap_fileforge $RPM_BUILD_ROOT%{_sysconfdir}/sudoers.d/tuleap_fileforge

## plugin webdav
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/plugins/webdav/locks
%{__install} plugins/webdav/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_webdav
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_webdav
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_webdav

# plugin-git
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/gitolite/repositories
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/templates_c
%{__install} -d $RPM_BUILD_ROOT/%{APP_CACHE_DIR}/smarty/cache
%{__install} plugins/git/bin/sudo/gl-membership.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__sed} -i "s~%%app_user%%~%{APP_USER}~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/gl-membership.pl
%{__sed} -i "s~%app_path%~/usr/share/tuleap~g" $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}/gl-membership.pl
%{__install} plugins/git/bin/restore-tar-repository.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/bin/gitolite3-suexec-wrapper.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} plugins/git/etc/sudoers.d/gitolite3-http $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__sed} -i "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_http
%{__install} plugins/git/etc/sudoers.d/gitolite3-replace-authorized-keys $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite3_replace_authorized_keys
%{__install} plugins/git/etc/sudoers.d/git-change-default-branch $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_change_default_branch
%{__install} plugins/git/etc/sudoers.d/git-create-new-branch $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_create_new_branch
%{__install} plugins/git/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_git
%{__install} plugins/git/etc/sudoers.d/tuleap-git-postreceive $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_postreceive
%{__install} plugins/git/etc/sudoers.d/tuleap-git-prereceive $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_git_prereceive
%{__install} plugins/git/etc/sudoers.d/tuleap-plugin-git $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__sed} -i "s~%%app_user%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__sed} -i "s~%app_path%~/usr/share/tuleap~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_git
%{__install} -D plugins/git/etc/sudoers.d/gitolite-access-command $RPM_BUILD_ROOT/etc/sudoers.d/gitolite-access-command
%{__install} -D plugins/git/bin/TULEAP_MAX_NEWBIN_SIZE $RPM_BUILD_ROOT/usr/share/gitolite3/VREF/TULEAP_MAX_NEWBIN_SIZE
%{__ln_s} %{APP_DIR}/plugins/git/bin/TULEAP_PROTECT_DEFAULT_BRANCH $RPM_BUILD_ROOT/usr/share/gitolite3/VREF/TULEAP_PROTECT_DEFAULT_BRANCH
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-git.timer $RPM_BUILD_ROOT/%{_unitdir}
%{__install} plugins/git/etc/systemd/tuleap-process-system-events-git.service $RPM_BUILD_ROOT/%{_unitdir}

#
##codendiadm > gitolite sudo
%{__install} plugins/git/etc/sudoers.d/gitolite $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite
%{__sed} -i "s~%libbin_dir%~%{APP_LIBBIN_DIR}~g" $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitolite

# Plugin gitlfs
%{__install} plugins/gitlfs/etc/sudoers.d/tuleap_gitlfs_authenticate $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_gitlfs_authenticate
%{__install} -m 755 -d $RPM_BUILD_ROOT/usr/share/gitolite3/commands/
%{__ln_s} %{APP_DIR}/plugins/gitlfs/bin/git-lfs-authenticate $RPM_BUILD_ROOT/usr/share/gitolite3/commands/git-lfs-authenticate

## Plugin PullRequest
%{__install} plugins/pullrequest/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest

# Plugin svn
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/svn_plugin
%{__install} plugins/svn/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_svn_plugin
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_svn_plugin
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_svn_plugin

# Plugin docman
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/docman

# Plugin tracker
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/tracker
%{__install} plugins/tracker/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_tracker
%{__install} plugins/tracker/etc/sudoers.d/tuleap-plugin-tracker $RPM_BUILD_ROOT/etc/sudoers.d/tuleap_plugin_tracker
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tracker/doc/
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/tracker/functional/
#
# Plugin agiledashboard
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/agiledashboard
%{__install} plugins/agiledashboard/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_agiledashboard
#
## Plugin mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/master
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/mediawiki/projects

## Plugin mediawiki_standalone
%{__install} plugins/mediawiki_standalone/etc/systemd/mediawiki-tuleap-php-fpm.service $RPM_BUILD_ROOT/%{_unitdir}
%{__install} plugins/mediawiki_standalone/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_mediawiki_standalone
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_mediawiki_standalone
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_mediawiki_standalone

## Plugin onlyoffice
%{__install} plugins/onlyoffice/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_onlyoffice
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_onlyoffice
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_onlyoffice

#
## Plugin bugzilla
%{__install} plugins/bugzilla_reference/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_bugzilla_reference
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
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_ldap

# Plugin OpenID Connect Client
%{__install} plugins/openidconnectclient/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_openid_connect_client

# Plugin GitLab
%{__install} plugins/gitlab/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_gitlab
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_gitlab

# Plugin BotMattermost
%{__cp} -ar plugins/botmattermost/tuleap-plugin-botmattermost.conf $RPM_BUILD_ROOT/%{_sysconfdir}/logrotate.d/

%if %{with enterprise}

# Plugin FTS Meilisearch
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/fts_meilisearch

# Plugin program_management
%{__install} plugins/program_management/etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_program_management
%{__sed} -i "s~%PROJECT_NAME%~%{APP_NAME}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_program_management
%{__sed} -i "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/%{APP_NAME}_program_management

# Plugin Tracker CCE
%{__install} -d $RPM_BUILD_ROOT/%{APP_DATA_DIR}/tracker_functions

%endif

%if %{with experimental}

%endif

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
/usr/bin/systemctl enable \
    tuleap.service \
    tuleap-workers.service \
    tuleap-php-fpm.service &>/dev/null || :

# Clean old tuleap cache file
/usr/bin/rm -rf %{APP_CACHE_DIR}/tuleap_hooks_cache

%post core-subversion
/usr/bin/systemctl daemon-reload &>/dev/null || :

#
# Post install of git plugin
%post plugin-git
# add codendiadm to gitolite group
if ! groups codendiadm | grep -q gitolite 2> /dev/null ; then
    usermod -a -G gitolite codendiadm
fi

%post plugin-mediawiki-standalone
/usr/bin/systemctl enable mediawiki-tuleap-php-fpm.service &>/dev/null || :

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

%preun plugin-mediawiki-standalone
if [ $1 -eq 0 ]; then
    /usr/bin/systemctl stop tuleap.service &>/dev/null || :

    /usr/bin/systemctl disable mediawiki-tuleap-php-fpm.service &>/dev/null || :
fi


%preun plugin-git
if [ $1 -eq 0 ]; then
    /usr/bin/systemctl stop tuleap-process-system-events-git.timer tuleap-process-system-events-git.service &>/dev/null || :
    /usr/bin/systemctl disable tuleap-process-system-events-git.timer tuleap-process-system-events-git.service &>/dev/null || :
fi
/usr/bin/systemctl stop tuleap-process-system-events-grokmirror.timer tuleap-process-system-events-grokmirror.service &>/dev/null || :
/usr/bin/systemctl disable tuleap-process-system-events-grokmirror.timer tuleap-process-system-events-grokmirror.service &>/dev/null || :

%postun
/usr/bin/systemctl daemon-reload &>/dev/null || :

%postun core-subversion
/usr/bin/systemctl daemon-reload &>/dev/null || :

%postun plugin-mediawiki-standalone
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
%{APP_DIR}/preload.php
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
%{APP_DIR}/src/scripts
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
%{APP_DIR}/src/www/favicon.ico
%{APP_DIR}/src/www/file
%{APP_DIR}/src/www/forum
%{APP_DIR}/src/www/help
%{APP_DIR}/src/www/include
%{APP_DIR}/src/www/my
%{APP_DIR}/src/www/news
%{APP_DIR}/src/www/project
%{APP_DIR}/src/www/reference
%{APP_DIR}/src/www/scripts
%{APP_DIR}/src/www/search
%{APP_DIR}/src/www/service
%{APP_DIR}/src/www/softwaremap
# Only "common" theme is embedded into the package
%dir %{APP_DIR}/src/www/themes
%{APP_DIR}/src/www/themes/common
%{APP_DIR}/src/www/tos
%{APP_DIR}/src/www/tracker
%{APP_DIR}/src/www/user
%{APP_DIR}/src/www/widgets
%{APP_DIR}/src/www/wiki
# Plugins dir
%dir %{APP_DIR}/plugins
%{APP_DIR}/plugins/pluginsadministration
%{APP_DIR}/plugins/projectlinks
%{APP_DIR}/plugins/statistics
%{APP_DIR}/plugins/tracker_date_reminder
%{APP_DIR}/plugins/userlog

# Configuration
%attr(00750,root,codendiadm) /etc/%{APP_NAME}
%attr(00750,codendiadm,codendiadm) /etc/%{APP_NAME}/conf
%attr(00750,codendiadm,codendiadm) /etc/%{APP_NAME}/plugins
%attr(00750,codendiadm,codendiadm) /etc/%{APP_NAME}/plugins/pluginsadministration

# PHP Configuration
%attr(00755,root,root) /etc/opt/remi/php82/php.d/
%attr(00644,root,root) /etc/opt/remi/php82/php.d/99-tuleap.ini

# Data dir
%dir %attr(755,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/user
%dir %attr(-,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/images
%dir %attr(0755, root, %{ftp_group}) %{APP_DATA_DIR}/ftp
%dir %attr(3777, %{APP_USER}, %{APP_USER}) %{APP_DATA_DIR}/ftp/incoming
%dir %attr(0711, root, root) %{APP_DATA_DIR}/ftp/codendi
%dir %attr(0750, %{APP_USER}, %{APP_USER}) %{APP_DATA_DIR}/ftp/codendi/DELETED
%dir %attr(0755, %{ftpadmin_user}, %{ftpadmin_group}) %{APP_DATA_DIR}/ftp/pub
%{APP_DATA_DIR}/ftp/tuleap

# Executables (/usr/bin)
%attr(00755,%{APP_USER},%{APP_USER}) %{_bindir}/tuleap
%attr(00755,root,root) %{_bindir}/tuleap-cfg

# Executables (/usr/lib/tuleap/bin)
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIB_DIR}
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIBBIN_DIR}
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gotohell
%attr(04755,root,root) %{APP_LIBBIN_DIR}/fileforge
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_syslog
%dir %attr(00750,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}
%dir %attr(00750,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/php
%dir %attr(00750,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/php/session

# Log dir
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LOG_DIR}

# Sudoers
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_fileforge

# Run dir
%attr(00755,%{APP_USER},%{APP_USER}) %dir %{_localstatedir}/run/tuleap
%attr(00644,root,root) %{_tmpfilesdir}/tuleap.conf

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
%files core-subversion
%defattr(-,root,root,-)

#
# Plugins
#
%files plugin-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/git
%dir %{APP_DATA_DIR}/gitolite
%attr(00770,gitolite,gitolite)  %{APP_DATA_DIR}/gitolite/repositories
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/smarty
%attr(06755,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gl-membership.pl
%attr(00755,root,root) %{APP_LIBBIN_DIR}/gitolite3-suexec-wrapper.sh
%attr(00755,root,root) %{APP_LIBBIN_DIR}/restore-tar-repository.php
%attr(00644,root,root) %{_sysconfdir}/logrotate.d/%{APP_NAME}_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_http
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_postreceive
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_prereceive
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitolite3_replace_authorized_keys
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_change_default_branch
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_git_create_new_branch
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_git
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/gitolite-access-command
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-git.timer
%attr(00644,root,root) %{_unitdir}/tuleap-process-system-events-git.service
%attr(00755,root,root) /usr/share/gitolite3/VREF/TULEAP_MAX_NEWBIN_SIZE
/usr/share/gitolite3/VREF/TULEAP_PROTECT_DEFAULT_BRANCH

%files plugin-gitlfs
%defattr(-,root,root,-)
%{APP_DIR}/plugins/gitlfs
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_gitlfs_authenticate
/usr/share/gitolite3/commands/git-lfs-authenticate

%files plugin-pullrequest
%defattr(-,root,root,-)
%{APP_DIR}/plugins/pullrequest
%attr(00644,root,root) /etc/logrotate.d/tuleap_pullrequest
%config(noreplace) /etc/logrotate.d/tuleap_pullrequest

%files plugin-ldap
%defattr(-,root,root,-)
%{APP_DIR}/plugins/ldap
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_ldap
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_ldap

%files plugin-hudson
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson

%files plugin-hudson-svn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson_svn
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_svn
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_svn

%files plugin-hudson-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/hudson_git
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_hudson_git
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_hudson_git

%files plugin-webdav
%defattr(-,root,root,-)
%{APP_DIR}/plugins/webdav
%attr(00755,%{APP_USER},%{APP_USER}) %{APP_CACHE_DIR}/plugins/webdav
%attr(00644,root,root) %{_sysconfdir}/logrotate.d/%{APP_NAME}_webdav

%files plugin-svn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/svn
%attr(00750,%{APP_USER},%{APP_USER}) %dir %{APP_DATA_DIR}/svn_plugin
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_svn_plugin
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_svn_plugin

%files plugin-tracker
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tracker
%dir %attr(0750,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/tracker
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_tracker
%attr(00440,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_tracker

%files plugin-graphontrackers
%defattr(-,root,root,-)
%{APP_DIR}/plugins/graphontrackersv5

%files plugin-tracker-encryption
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tracker_encryption

%files plugin-cardwall
%defattr(-,root,root,-)
%{APP_DIR}/plugins/cardwall

%files plugin-agiledashboard
%defattr(-,root,root,-)
%{APP_DIR}/plugins/agiledashboard
%{APP_DIR}/plugins/kanban
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
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki/master
%dir %attr(0751,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/mediawiki/projects

%files plugin-mediawiki-standalone
%defattr(-,root,root,-)
%{APP_DIR}/plugins/mediawiki_standalone
%{_unitdir}/mediawiki-tuleap-php-fpm.service
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_mediawiki_standalone
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_mediawiki_standalone

%files plugin-onlyoffice
%defattr(-,root,root,-)
%{APP_DIR}/plugins/onlyoffice
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_onlyoffice
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_onlyoffice

%files plugin-openidconnectclient
%defattr(-,root,root,-)
%{APP_DIR}/plugins/openidconnectclient
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_openid_connect_client
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_openid_connect_client

%files plugin-frs
%defattr(-,root,root,-)
%{APP_DIR}/plugins/frs

%files plugin-captcha
%defattr(-,root,root,-)
%{APP_DIR}/plugins/captcha

%files plugin-bugzilla-reference
%defattr(-,root,root,-)
%{APP_DIR}/plugins/bugzilla_reference
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_bugzilla_reference

%files plugin-create-test-env
%defattr(-,root,root,-)
%{APP_DIR}/plugins/create_test_env
%attr(00400,root,root) %{_sysconfdir}/sudoers.d/tuleap_plugin_create_test_env

%files plugin-document
%defattr(-,root,root,-)
%{APP_DIR}/plugins/docman
%{APP_DIR}/plugins/document
%attr(00700,%{APP_USER},%{APP_USER}) %{APP_DATA_DIR}/docman

%files plugin-api-explorer
%defattr(-,root,root,-)
%{APP_DIR}/plugins/api_explorer

%files plugin-embed
%defattr(-,root,root,-)
%{APP_DIR}/plugins/embed

%files plugin-gitlab
%defattr(-,root,root,-)
%{APP_DIR}/plugins/gitlab
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_gitlab
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_gitlab

%files plugin-securitytxt
%defattr(-,root,root,-)
%{APP_DIR}/plugins/securitytxt

%files plugin-botmattermost
%defattr(-,root,root,-)
%{APP_DIR}/plugins/botmattermost
%attr(00644,root,root) %config(noreplace) /etc/logrotate.d/tuleap-plugin-botmattermost.conf

%files plugin-botmattermost-agiledashboard
%defattr(-,root,root,-)
%{APP_DIR}/plugins/botmattermost_agiledashboard

%files plugin-botmattermost-git
%defattr(-,root,root,-)
%{APP_DIR}/plugins/botmattermost_git

%if %{with enterprise}

%files plugin-baseline
%defattr(-,root,root,-)
%{APP_DIR}/plugins/baseline

%files plugin-crosstracker
%defattr(-,root,root,-)
%{APP_DIR}/plugins/crosstracker

%files plugin-dynamic-credentials
%defattr(-,root,root,-)
%{APP_DIR}/plugins/dynamic_credentials

%files plugin-label
%defattr(-,root,root,-)
%{APP_DIR}/plugins/label

%files plugin-roadmap
%defattr(-,root,root,-)
%{APP_DIR}/plugins/roadmap

%files plugin-fts-common
%defattr(-,root,root,-)
%{APP_DIR}/plugins/fts_common

%files plugin-fts-db
%defattr(-,root,root,-)
%{APP_DIR}/plugins/fts_db

%files plugin-fts-meilisearch
%defattr(-,root,root,-)
%{APP_DIR}/plugins/fts_meilisearch

%files plugin-oauth2-server
%defattr(-,root,root,-)
%{APP_DIR}/plugins/oauth2_server

%files plugin-project-ownership
%defattr(-,root,root,-)
%{APP_DIR}/plugins/project_ownership

%files plugin-projectmilestones
%defattr(-,root,root,-)
%{APP_DIR}/plugins/projectmilestones

%files plugin-prometheus-metrics
%defattr(-,root,root,-)
%{APP_DIR}/plugins/prometheus_metrics

%files plugin-taskboard
%defattr(-,root,root,-)
%{APP_DIR}/plugins/taskboard

%files plugin-tee-container
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tee_container

%files plugin-testmanagement
%defattr(-,root,root,-)
%{APP_DIR}/plugins/testmanagement

%files plugin-testplan
%defattr(-,root,root,-)
%{APP_DIR}/plugins/testplan


%files plugin-timetracking
%defattr(-,root,root,-)
%{APP_DIR}/plugins/timetracking

%files plugin-velocity
%defattr(-,root,root,-)
%{APP_DIR}/plugins/velocity

%files plugin-jira-import
%defattr(-,root,root,-)
%{APP_DIR}/plugins/jira_import

%files plugin-program_management
%defattr(-,root,root,-)
%{APP_DIR}/plugins/program_management
%attr(00644,root,root) /etc/logrotate.d/%{APP_NAME}_program_management
%config(noreplace) /etc/logrotate.d/%{APP_NAME}_program_management

%files plugin-document_generation
%defattr(-,root,root,-)
%{APP_DIR}/plugins/document_generation

%files plugin-mytuleap-contact-support
%defattr(-,root,root,-)
%{APP_DIR}/plugins/mytuleap_contact_support

%files plugin-enalean-licensemanager
%defattr(-,root,root,-)
%{APP_DIR}/plugins/enalean_licensemanager

%files plugin-webauthn
%defattr(-,root,root,-)
%{APP_DIR}/plugins/webauthn

%files plugin-tracker-functions
%defattr(-,root,root,-)
%{APP_DIR}/plugins/tracker_functions

%endif

%if %{with experimental}

%endif

#
# Themes
#

%files theme-flamingparrot
%defattr(-,root,root,-)
%{APP_DIR}/src/themes/FlamingParrot
%{APP_DIR}/src/www/themes/FlamingParrot

%files theme-burningparrot
%defattr(-,root,root,-)
%{APP_DIR}/src/themes/BurningParrot
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
