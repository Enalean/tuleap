Name:       tuleap-realtime
Version:    @@VERSION@@
Release:    @@RELEASE@@%{?dist}
Summary:    Tuleap realtime server

Group:      Development/Tools
License:    GPLv3
URL:        https://tuleap.net/plugins/git/tuleap/nodejs/tuleap-realtime
Source0:    %{name}.tar.gz
Source1:    %{name}.conf
%if 0%{?el6}
Source2:    %{name}.service
Source3:    logrotate.conf
%else
Source2:    %{name}.systemd-service
%endif

BuildArch:      noarch
ExclusiveArch:  %{nodejs_arches} noarch

AutoReqProv: no
Requires:        nodejs
Requires(pre):   /usr/sbin/useradd
%if 0%{?el6}
Requires:        supervisor
Requires(post):  chkconfig
%else
Requires: systemd
%endif

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
mkdir -p             %{buildroot}%{_sysconfdir}/%{name}
%if 0%{?el6}
mkdir -p             %{buildroot}%{_var}/log/%{name}
touch                %{buildroot}%{_var}/log/%{name}/%{name}.log
cp -pr               %{SOURCE1} %{buildroot}%{_sysconfdir}/%{name}.conf
install -p -D -m 755 %{SOURCE2} %{buildroot}%{_initddir}/%{name}
mkdir -p             %{buildroot}%{_sysconfdir}/logrotate.d
install -m 644       %{SOURCE3} %{buildroot}%{_sysconfdir}/logrotate.d/%{name}
%else
install -p -D -m 644 %{SOURCE2} %{buildroot}%{_unitdir}/%{name}.service
%endif
jq                   '.process_uid="tuleaprt" | .process_gid="tuleaprt"' %{buildroot}%{nodejs_sitelib}/%{name}/config/config.json > %{buildroot}%{_sysconfdir}/%{name}/config.json

%pre
getent group tuleaprt >/dev/null || groupadd -r tuleaprt
getent passwd tuleaprt >/dev/null || \
  useradd -r -g tuleaprt -s /sbin/nologin \
    -c "Tuleap realtime" tuleaprt
exit 0

%post
%if 0%{?el6}
/sbin/chkconfig --add %{name}
touch %{_var}/log/%{name}/%{name}.log
chmod -R 0640 %{_var}/log/%{name}
chown -R tuleaprt:tuleaprt %{_var}/log/%{name}
%endif

%preun
if [ $1 = 0 ]; then
    %if 0%{?el6}
    /sbin/service %{name} stop > /dev/null 2>&1
    /sbin/chkconfig --del %{name}
    %else
    /usr/bin/systemctl stop %{name} &>/dev/null || :
    /usr/bin/systemctl disable %{name} &>/dev/null || :
    %endif
fi

%posttrans
%if 0%{?el6}
/sbin/service %{name} condrestart >/dev/null 2>&1 || :
%else
/usr/bin/systemctl condrestart %{name} &>/dev/null || :
%endif


%check

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%doc README.md
%attr(0750, tuleaprt, tuleaprt) %dir %{_sysconfdir}/%{name}
%attr(0640, tuleaprt, tuleaprt) %config(noreplace) %{_sysconfdir}/%{name}/config.json
%if 0%{?el6}
%{_sysconfdir}/%{name}.conf
%{_initddir}/%{name}
%config %ghost %{_var}/log/%{name}/%{name}.log
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}
%else
%attr(00644,root,root) %{_unitdir}/%{name}.service
%endif

%{nodejs_sitelib}/%{name}

