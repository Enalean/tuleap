Summary: Codendi forge
Name: codendi_st
Provides: codendi
Version: @@VERSION@@
Release: 1%{?dist}
#BuildArch: noarch
BuildArch: i386
License: GPL
Group: Development/Tools
URL: http://codendi.org
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: codendi_st_customization
Requires: codendi_fileforge
Packager: Manuel VACELET <manuel.vacelet@st.com>

# Define variables
%define APP_NAME codendi
%define APP_USER codendiadm
%define APP_DIR %{_datadir}/%{APP_NAME}
%define APP_LIB_DIR %{_libdir}/codendi-test
%define APP_LIBBIN_DIR %{_libdir}/codendi-test/bin


%description
Codendi is a web based application that address all the aspects of product development.

%prep
%setup -q

%build
# build fileforge
%{__cc} src/utils/fileforge.c -o src/utils/fileforge
#gcc src/utils/fileforge.c -o src/utils/fileforge
#cd src/utils
#%{__make}

%install
%{__rm} -rf $RPM_BUILD_ROOT

#
# Install codendi application
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}
for i in cli plugins site-content src; do
	%{__cp} -ar $i $RPM_BUILD_ROOT/%{APP_DIR}
done
# Remove old scripts: not used and add unneeded perl depedencies to the package
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanUploader.pl
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/utils/DocmanLegacyDownloader.pl
# Hard-coded perl include that breaks packging
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/plugins/forumml/bin/ml_arch_2_DB.pl
# Remove salome plugin because not used and breaks SELinux postinstall fix (wrong symlink)
%{__rm} -rf $RPM_BUILD_ROOT/%{APP_DIR}/plugins/salome
# Remove organization_logo (provided by codendi_st_customization package)
%{__rm} -f $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/images/organization_logo.png

#
# Install Codendi executables
%{__install} -m 00755 -d $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/gotohell $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 04755 src/utils/fileforge $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00740 src/utils/backup_job $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00740 src/utils/svn/backup_subversion.sh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 04755 src/utils/cvs1/log_accum $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/cvs1/commit_prep $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/cvs1/cvssh $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/cvs1/cvssh-restricted $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/svn/commit-email.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 00755 src/utils/svn/codendi_svn_pre_commit.php $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}
%{__install} -m 06755 plugins/forumml/bin/mail_2_DB.pl $RPM_BUILD_ROOT/%{APP_LIBBIN_DIR}

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
/usr/bin/chcon -R root:object_r:httpd_sys_content_t $RPM_BUILD_ROOT/%{APP_DIR}


%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,%{APP_USER},%{APP_USER},-)
%{APP_DIR}/cli
%{APP_DIR}/plugins
%{APP_DIR}/site-content
%{APP_DIR}/src
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIB_DIR}
%attr(755,%{APP_USER},%{APP_USER}) %dir %{APP_LIBBIN_DIR}
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/gotohell
%attr(-,root,root) %{APP_LIBBIN_DIR}/fileforge
%attr(-,root,root) %{APP_LIBBIN_DIR}/backup_job
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/backup_subversion.sh
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/log_accum
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/commit_prep
%attr(-,root,root) %{APP_LIBBIN_DIR}/cvssh
%attr(-,root,root) %{APP_LIBBIN_DIR}/cvssh-restricted
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/commit-email.pl
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/codendi_svn_pre_commit.php
%attr(-,%{APP_USER},%{APP_USER}) %{APP_LIBBIN_DIR}/mail_2_DB.pl

#%doc 
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

