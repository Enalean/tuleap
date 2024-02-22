%define _buildhost tuleap-builder
%define _source_payload w9T8.xzdio
%define _binary_payload w9T8.xzdio

%define __os_install_post %{nil}

Name:		  tuleap-wasmtime-wrapper-lib
Version:	%{tuleap_version}
Release:	1%{?nixpkgs_epoch}%{?dist}
Summary:	Wrapper around Wasmtime to be used by Tuleap

License:	MIT
Source0:	libwasmtimewrapper.h
Source1:  libwasmtimewrapper.so

%description
%{summary}.

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%prep
%setup -q -c -T
cp %{SOURCE0} .
cp %{SOURCE1} .

%build

%install
mkdir -p %{buildroot}/usr/lib/tuleap/wasm/
cp -a * %{buildroot}/usr/lib/tuleap/wasm/

%files
%defattr(-,root,root,-)
/usr/lib/tuleap/wasm/
