%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

%define __os_install_post %{nil}

Name:		sha1collisiondetector
Version:	1.1.0
Release:	1%{?nixpkgs_epoch}%{?dist}
Summary:	A SHA-1 collision detector CLI tool

License:	MIT
Source0:	sha1collisiondetector

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%prep
%setup -q -c -T
cp %{SOURCE0} .

%build

%install
mkdir -p %{buildroot}%{_bindir}/
cp -p sha1collisiondetector %{buildroot}%{_bindir}/

%files
%defattr(-,root,root,-)
%{_bindir}/sha1collisiondetector