%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio
%define app_group tuleap-mercure
%define app_user tuleap-mercure

%define __os_install_post %{nil}

Name:		tuleap-mercure
Version:	%{tuleap_version}
Release:	1%{?nixpkgs_epoch}%{?dist}
Summary:	Mercure realtime server for Tuleap

License:	MIT
Source0:	tuleap-mercure
Source1:    tuleap-mercure.service
Source2:    Caddyfile

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)


%prep
%setup -q -c -T
cp %{SOURCE0} .
cp %{SOURCE1} .
cp %{SOURCE2} .


%build

%pre
if ! getent group %{app_group} > /dev/null; then
        /usr/sbin/groupadd -r %{app_group}
fi
if getent passwd %{app_user} >/dev/null; then
    /usr/sbin/usermod -c 'Tuleap Mercure realtime server user' -m -d '/var/lib/tuleap-mercure'  -g "%{app_group}" -s '/sbin/nologin' %{app_user}
else
    /usr/sbin/useradd -c 'Tuleap Mercure realtime server user' -m -d '/var/lib/tuleap-mercure' -r -g "%{app_group}" -s '/sbin/nologin' %{app_user}
fi

%preun
if [ $1 -eq "0" ]; then
    /usr/bin/systemctl stop tuleap-mercure.service ||:

    /usr/bin/systemctl disable tuleap-mercure.service ||:
fi


%install
mkdir -p %{buildroot}/var/lib/tuleap-mercure/
mkdir -p %{buildroot}%{_bindir}/
cp tuleap-mercure %{buildroot}%{_bindir}/
chmod 755 %{buildroot}%{_bindir}/tuleap-mercure
mkdir -p %{buildroot}%{_unitdir}/
cp tuleap-mercure.service %{buildroot}%{_unitdir}/tuleap-mercure.service
chmod 664 %{buildroot}%{_unitdir}/tuleap-mercure.service
mkdir -p %{buildroot}/usr/share/tuleap-mercure/
cp Caddyfile %{buildroot}/usr/share/tuleap-mercure/Caddyfile
chmod 664 %{buildroot}/usr/share/tuleap-mercure/Caddyfile

%post

/usr/bin/systemctl daemon-reload &> /dev/null || :
if [ $1 -eq "1" ]; then
    /usr/bin/systemctl enable tuleap-mercure.service ||:
fi

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-mercure
%attr(-,tuleap-mercure,root) /var/lib/tuleap-mercure
/usr/share/tuleap-mercure/Caddyfile
%{_unitdir}/%{name}.service
