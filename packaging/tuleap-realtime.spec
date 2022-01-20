%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio
%define debug_package %{nil}
%define __os_install_post %{nil}

%define target_path /usr/lib/tuleap-realtime

Name:       tuleap-realtime
Version:    @@VERSION@@
Release:    @@RELEASE@@%{?dist}
Summary:    Tuleap realtime server

Group:      Development/Tools
License:    GPLv3
URL:        https://tuleap.net/plugins/git/tuleap/nodejs/tuleap-realtime
Source0:    %{name}.tar.gz
Source2:    %{name}.systemd-service

BuildArch:      x86_64

AutoReqProv: no
Requires(pre):   /usr/sbin/useradd
Requires: systemd

BuildRequires: jq

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

%description
Tuleap realtime server

%prep
%setup -cqn tuleap-realtime

%build

%install
rm -rf               %{buildroot}
mkdir -p             %{buildroot}%{target_path}
cp -pr               ./dist/%{name} %{buildroot}%{target_path}/%{name}
mkdir -p             %{buildroot}%{_sysconfdir}/%{name}
install -p -D -m 644 %{SOURCE2} %{buildroot}%{_unitdir}/%{name}.service
jq                   '.process_uid="tuleaprt" | .process_gid="tuleaprt"' ./config/config.json > %{buildroot}%{_sysconfdir}/%{name}/config.json

%pre
getent group tuleaprt >/dev/null || groupadd -r tuleaprt
getent passwd tuleaprt >/dev/null || \
  useradd -r -g tuleaprt -s /sbin/nologin \
    -c "Tuleap realtime" tuleaprt
exit 0

%post

%preun
if [ $1 = 0 ]; then
    /usr/bin/systemctl stop %{name} &>/dev/null || :
    /usr/bin/systemctl disable %{name} &>/dev/null || :
fi

%posttrans
/usr/bin/systemctl condrestart %{name} &>/dev/null || :

%check

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%attr(0750, tuleaprt, tuleaprt) %dir %{_sysconfdir}/%{name}
%attr(0640, tuleaprt, tuleaprt) %config(noreplace) %{_sysconfdir}/%{name}/config.json
%attr(00644,root,root) %{_unitdir}/%{name}.service

%{target_path}/%{name}

