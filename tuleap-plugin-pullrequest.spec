%define APP_USER codendiadm

Summary: Pullrequest management for Tuleap
Name: tuleap-plugin-pullrequest
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPLv3
Group: Development/Tools
URL: https://enalean.com
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@enalean.com>

AutoReqProv: no

Requires: php(language) >= 5.3
Requires: tuleap, tuleap-plugin-git

%description
Pullrequest management for Tuleap

#
# Package setup
%prep
%setup -q

#
# Build
%build
# Backend
npm install
npm run build
find www/themes -name '*.scss' | xargs rm -f

# Frontend
cd www/js/angular
npm install
bower install
npm run build

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest
%{__cp} -ar include db templates README.mkd VERSION ChangeLog site-content $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest

%{__install} -D etc/sudoers.d/gitolite-access-command $RPM_BUILD_ROOT/etc/sudoers.d/gitolite-access-command

# logrotate
%{__mkdir} -p $RPM_BUILD_ROOT/etc/logrotate.d
%{__install} etc/logrotate.syslog.dist $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest
%{__perl} -pi -e "s~%%APP_USER%%~%{APP_USER}~g" $RPM_BUILD_ROOT/etc/logrotate.d/tuleap_pullrequest

# www
%{__mkdir} -p $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest/www/js/angular
%{__cp} -ar www/index.php www/themes $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest/www
%{__cp} -ar www/js/angular/bin $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest/www/js/angular
find www/js/ -mindepth 1 -maxdepth 1 ! -name angular -exec %{__cp} -ar {} $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/pullrequest/www/js \;

%pre
if [ "$1" -eq "1" ]; then
    # Install
    true
else
    # Update
    true
fi

%clean
%{__rm} -rf $RPM_BUILD_ROOT

#
#
#
%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/pullrequest
%config(noreplace) /etc/logrotate.d/tuleap_pullrequest
%attr(00600,root,root) /etc/sudoers.d/gitolite-access-command

%changelog
* Tue Feb 9 2016 Manuel VACELET <manuel.vacelet@enalean.com> -
- First package
