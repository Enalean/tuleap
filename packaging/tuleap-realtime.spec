Name:       tuleap-realtime
Version:    @@VERSION@@
Release:    @@RELEASE@@
Summary:    Tuleap realtime server

Group:      Development/Tools
License:    GPLv3
URL:        https://tuleap.net/plugins/git/tuleap/nodejs/tuleap-realtime
Source0:    %{name}.tar.gz
Source1:    %{name}.conf
Source2:    %{name}.service
Source3:    logrotate.conf

BuildArch:      noarch
ExclusiveArch:  %{nodejs_arches} noarch

AutoReqProv: no
Requires:        nodejs supervisor
Requires(pre):   /usr/sbin/useradd
Requires(post):  chkconfig

BuildRequires:    npm jq
BuildRequires:    nodejs-packaging

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

%description
Tuleap realtime server

%prep
%setup -cqn tuleap-realtime
rm -rf spec
rm -rf ssl

%build
npm install --production

%install
rm -rf               %{buildroot}
mkdir -p             %{buildroot}%{nodejs_sitelib}/%{name}
cp -pr               . %{buildroot}%{nodejs_sitelib}/%{name}
mkdir -p             %{buildroot}%{_var}/log/%{name}
touch                %{buildroot}%{_var}/log/%{name}/%{name}.log
mkdir -p             %{buildroot}%{_sysconfdir}/%{name}
cp -pr               %{SOURCE1} %{buildroot}%{_sysconfdir}/%{name}.conf
install -p -D -m 755 %{SOURCE2} %{buildroot}%{_initddir}/%{name}
jq                   '.process_uid="tuleaprt" | .process_gid="tuleaprt"' %{buildroot}%{nodejs_sitelib}/%{name}/config/config.json > %{buildroot}%{_sysconfdir}/%{name}/config.json
mkdir -p             %{buildroot}%{_sysconfdir}/logrotate.d
install -m 644       %{SOURCE3} %{buildroot}%{_sysconfdir}/logrotate.d/%{name}

%pre
getent group tuleaprt >/dev/null || groupadd -r tuleaprt
getent passwd tuleaprt >/dev/null || \
  useradd -r -g tuleaprt -s /sbin/nologin \
    -c "Tuleap realtime" tuleaprt
exit 0

%post
/sbin/chkconfig --add %{name}

%preun
if [ $1 = 0 ]; then
    /sbin/service %{name} stop > /dev/null 2>&1
    /sbin/chkconfig --del %{name}
fi

%posttrans
    /sbin/service %{name} condrestart >/dev/null 2>&1 || :

%check

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc README.md
%config %{_sysconfdir}/%{name}
%{_sysconfdir}/%{name}.conf
%{_initddir}/%{name}
%config %ghost %{_var}/log/%{name}/%{name}.log
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}

%{nodejs_sitelib}/%{name}

%changelog
* Fri Mar 25 2016 Thomas Gerbet <thomas.gerbet@enalean.com> - 0.0.3-3
- Add a logrotate configuration

* Fri Feb 7 2016 Juliana Leclaire <juliana.leclaire@enalean.com> - 0.0.3-2
- Packaging to start node.js server

* Sun Feb 7 2016 Thomas Gerbet <thomas.gerbet@enalean.com> - 0.0.3-1
- Initial packaging
