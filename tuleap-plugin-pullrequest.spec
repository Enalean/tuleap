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
Requires: tuleap, tuleap-plugin-tracker

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
grunt
find www/themes -name '*.less' | xargs rm -f

# Frontend
cd www/js/angular
sed -i -e "s%tuleap_dir: .*%tuleap_dir: '/tuleap',%" build.config.js
npm install
bower install
grunt

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap-plugins/pullrequest
%{__cp} -ar include db templates README.mkd VERSION ChangeLog site-content $RPM_BUILD_ROOT/%{_datadir}/tuleap-plugins/pullrequest

# www
%{__mkdir} -p $RPM_BUILD_ROOT/%{_datadir}/tuleap-plugins/pullrequest/www/js/angular
%{__cp} -ar www/index.php www/themes $RPM_BUILD_ROOT/%{_datadir}/tuleap-plugins/pullrequest/www
%{__cp} -ar www/js/angular/bin $RPM_BUILD_ROOT/%{_datadir}/tuleap-plugins/pullrequest/www/js/angular

# conf
%{__mkdir} -p $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/
%{__cp} pullrequest.conf $RPM_BUILD_ROOT/etc/httpd/conf.d/tuleap-plugins/

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
%{_datadir}/tuleap-plugins/pullrequest
%config %{_sysconfdir}/httpd/conf.d/tuleap-plugins/pullrequest.conf

%changelog
* Tue Feb 9 2016 Manuel VACELET <manuel.vacelet@enalean.com> -
- First package
