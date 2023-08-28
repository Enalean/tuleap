%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:           viewvc-tuleap
Conflicts:      viewvc
Version:        1.3.0
Release:	    1%{?nixpkgs_epoch}%{?dist}
Summary:        Browser interface for SVN version control repositories

Group:          Development/Tools
License:        BSD
URL:            http://www.viewvc.org/
Source0:        %{name}.tar.gz

BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

AutoReqProv: no
BuildArch:      noarch

Requires:       subversion >= 1.14
Requires:       python3-subversion
Requires:       python3-urllib3
Requires:       python3-chardet
Requires:       python3-pygments

%description
ViewVC is a browser interface for Subversion version control
repositories. It generates templatized HTML to present navigable directory,
revision, and change log listings. It can display specific versions of files
as well as diffs between those versions. Basically, ViewVC provides the bulk
of the report-like functionality you expect out of your version control tool,
but much more prettily than the average textual command-line program output.

%prep
%setup -qn viewvc-tuleap

%install
%{__rm} -rf $RPM_BUILD_ROOT
%{__python} viewvc-install --destdir="$RPM_BUILD_ROOT" --prefix="%{python_sitelib}/viewvc"

# Install config to sysconf directory
%{__install} -Dp -m0644 %{buildroot}%{python_sitelib}/viewvc/viewvc.conf %{buildroot}%{_sysconfdir}/viewvc/viewvc.conf
%{__rm} -f %{buildroot}%{python_sitelib}/viewvc/viewvc.conf

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%config(noreplace) %{_sysconfdir}/viewvc
%{python_sitelib}/*
