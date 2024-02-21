%define _buildhost tuleap-builder
%define _source_payload w9T8.xzdio
%define _binary_payload w9T8.xzdio
%define app_group tuleap-smokescreen
%define app_user tuleap-smokescreen

%define __os_install_post %{nil}

Name:		tuleap-smokescreen
Version:	%{tuleap_version}
Release:	1%{?nixpkgs_epoch}%{?dist}
Summary:	Tuleap Smokescreen (proxy filtering outbound HTTP requests)

License:	MIT
Source0: smokescreen
Source1: tuleap-smokescreen.service

%description
%{summary}.

%prep
%setup -q -c -T
cp %{SOURCE0} .
cp %{SOURCE1} .

%build

%pre
if ! getent group %{app_group} > /dev/null; then
        /usr/sbin/groupadd -r %{app_group}
fi
if getent passwd %{app_user} >/dev/null; then
    /usr/sbin/usermod -c 'Tuleap Smokescreen user' -d '/'  -g "%{app_group}" -s '/sbin/nologin' %{app_user}
else
    /usr/sbin/useradd -c 'Tuleap Smokescreen user' -d '/' -r -g "%{app_group}" -s '/sbin/nologin' %{app_user}
fi

%preun
if [ $1 -eq "0" ]; then
    /usr/bin/systemctl stop tuleap-smokescreen.service ||:

    /usr/bin/systemctl disable tuleap-smokescreen.service ||:
fi

%install
mkdir -p %{buildroot}%{_bindir}/
cp smokescreen %{buildroot}%{_bindir}/tuleap-smokescreen
chmod 755 %{buildroot}%{_bindir}/tuleap-smokescreen
mkdir -p %{buildroot}%{_unitdir}/
cp tuleap-smokescreen.service %{buildroot}%{_unitdir}/tuleap-smokescreen.service
chmod 664 %{buildroot}%{_unitdir}/tuleap-smokescreen.service

%post

/usr/bin/systemctl daemon-reload &> /dev/null || :
if [ $1 -eq "1" ]; then
    /usr/bin/systemctl enable tuleap-smokescreen.service ||:
fi

%files
%defattr(-,root,root,-)
%{_bindir}/tuleap-smokescreen
%{_unitdir}/%{name}.service
