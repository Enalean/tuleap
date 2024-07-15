%define _buildhost tuleap-builder
%define _source_payload w16T8.zstdio
%define _binary_payload w16T8.zstdio

Summary: Tuleap theme for ViewVC
Name: viewvc-theme-tuleap
Version: %{tuleap_version}
Release: 1%{?nixpkgs_epoch}%{?dist}
BuildArch: noarch
License: BSD
Group: Development/Tools
URL: https://enalean.com
Source0: %{name}.tar.gz

AutoReqProv: no

%description
Tuleap theme for ViewVC

#
# Package setup
%prep
%setup -qn src/

#
# Build
%build
find . -name '*.scss' -exec rm -f "{}" \;

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -d $RPM_BUILD_ROOT/%{_datadir}/%{name}
%{__cp} -ar assets templates $RPM_BUILD_ROOT/%{_datadir}/%{name}

%clean
%{__rm} -rf $RPM_BUILD_ROOT

#
#
#
%files
%defattr(-,root,root,-)
%{_datadir}/%{name}
