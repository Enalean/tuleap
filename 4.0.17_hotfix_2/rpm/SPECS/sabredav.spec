Name:            Sabre_DAV
Version:         1.0.14
Release:         1%{?dist}
Summary:         WebDAV framework for PHP

Group:           Development/Libraries
License:         New BSD
URL:             http://sabredav.googlecode.com/
Source0:         http://sabredav.googlecode.com/files/%{name}-%{version}.tgz
BuildRoot:       %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:       noarch

Requires:        php

%description
SabreDAV allows you to easily add WebDAV support to a PHP application. SabreDAV is meant to cover the entire standard, and attempts to allow integration using an easy to understand API. 

%prep
%setup -qn %{name}-%{version}

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__mkdir_p} $RPM_BUILD_ROOT%{_datadir}/sabredav
%{__cp} -pr lib $RPM_BUILD_ROOT%{_datadir}/sabredav

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/sabredav
%doc LICENSE ChangeLog

%changelog

* Tue Oct 12 2010 Hatem Ounis <hatem.ounis@st.com> - 1.0.14-1
- initial build