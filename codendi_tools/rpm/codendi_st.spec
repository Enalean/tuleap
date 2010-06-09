Summary: Codendi forge
Name: codendi_st
Provides: codendi
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: codendi_st_customization
Packager: Manuel VACELET <manuel.vacelet@st.com>

# Define variables
%define CODENDI_DIR %{_datadir}/codendi

%description
Codendi is a web based application that address all the aspects of product development.

%prep
%setup -q

%build
# build doc
# package

%install
%{__rm} -rf $RPM_BUILD_ROOT
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{CODENDI_DIR}

# installing codendi
for i in cli plugins site-content src; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{CODENDI_DIR}
done

# Remove old scripts: not used and add unneeded perl depedencies to the package
%{__rm} -f $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/utils/DocmanUploader.pl
%{__rm} -f $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/utils/DocmanLegacyDownloader.pl

#%{__rm} -f $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/utils/analyse_language_files.pl
%{__rm} -f $RPM_BUILD_ROOT/%{CODENDI_DIR}/plugins/forumml/bin/ml_arch_2_DB.pl

# Remove salome plugin because not used and break SELinux postinstall fix (wrong symlink)
%{__rm} -rf $RPM_BUILD_ROOT/%{CODENDI_DIR}/plugins/salome

# Remove organization_logo (provided by codendi_st_customization package)
%{__rm} -f $RPM_BUILD_ROOT/%{CODENDI_DIR}/src/www/themes/common/images/organization_logo.png

%post
if [ "$1" -eq "1" ]; then
	# Install
	true
else
	# Upgrade
	# Launch forgeupgrade
	true
fi
# In any cases fix the context
/usr/bin/chcon -R root:object_r:httpd_sys_content_t $RPM_BUILD_ROOT/%{CODENDI_DIR}


%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,codendiadm,codendiadm,-)
%{CODENDI_DIR}/cli
%{CODENDI_DIR}/plugins
%{CODENDI_DIR}/site-content
%{CODENDI_DIR}/src
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/svn/backup_subversion.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/svn/bdb2fsfs.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/svn/commit-email.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/cvs2cvsnt.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/commit_prep
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/run_span.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/log_accum
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/cvssh-restricted
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/cvs_history_parse.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/cvs_watch.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/cvs1/cvssh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/download/stats_ftp_logparse.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/download/stats_nightly_filerelease.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/download/stats_logparse.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/download/stats_agr_filerelease.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/download/stats_http_logparse.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/analyse_language_files.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_programmer_doc.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_cli_package.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/compile_fileforge.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/projects-fileserver/stats_projects_logparse.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_phpdoc.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/php-launcher.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/fix_selinux_contexts.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/stats_nightly.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_top_groups_calc.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_stats_projects_nightly.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_stats_svn_history.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/run_span.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_site_stats.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_project_weekly_metric.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_stats_prepare.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_project_metric.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_rating_stats.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_project_cleanup.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_stats_cvs_history.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/underworld-root/db_stats_site_nightly.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/compute_all_daily_stats.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_doc.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/codendi.pl
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/backup_job
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_ssl_certificate.sh
# %attr(0755,-,-) %{CODENDI_DIR}/src/utils/generate_cli_doc.sh
# %attr(0755,-,-) %{CODENDI_DIR}/plugins/IM/www/webmuc/lib/jsjac/utils/packer/pack.php
# %attr(0755,-,-) %{CODENDI_DIR}/plugins/IM/www/webmuc/lib/jsjac/utils/JSDoc/jsdoc.pl
# #%attr(0755,-,-) %{CODENDI_DIR}/plugins/forumml/bin/ml_arch_2_DB.pl
# %attr(0755,-,-) %{CODENDI_DIR}/plugins/forumml/bin/mail_2_DB.php
# %attr(0755,-,-) %{CODENDI_DIR}/plugins/forumml/bin/mail_2_DB.pl
# %attr(0755,-,-) %{CODENDI_DIR}/cli/codendi.php

#%doc 
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

