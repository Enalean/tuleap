%global __strip true

%define _buildhost tuleap-builder
%define _source_payload w16T8.zstdio
%define _binary_payload w16T8.zstdio

Name:          tuleap-git-bin
Version:       %{tuleap_version}
Release:       1.%{git_version}%{?nixpkgs_epoch}%{?dist}
Summary:       Git binaries for Tuleap usage
Group:         Development/Tools
License:       GPLv2

AutoReqProv: no

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%install
mkdir -p %{buildroot}%{tuleap_git_base_path}/share/
cp -a %{git_static_path}/%{tuleap_git_base_path}/bin %{buildroot}%{tuleap_git_base_path}/
cp -a %{git_static_path}/%{tuleap_git_base_path}/libexec %{buildroot}%{tuleap_git_base_path}/
cp -a %{git_static_path}/%{tuleap_git_base_path}/share/git-core %{buildroot}%{tuleap_git_base_path}/share/

%clean

%files
%defattr(-,root,root)
/usr/lib/tuleap/git/*
