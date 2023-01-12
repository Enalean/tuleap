%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

%global __strip true

Name:          htmldoc-tuleap
Version:       %{tuleap_version}
Release:       1.%{htmldoc_version}%{?nixpkgs_epoch}%{?dist}
Summary:       Summary:	Converter from HTML into indexed HTML, PostScript, or PDF
License:       GPLv2 with exceptions
URL:           http://www.htmldoc.org/

Provides: htmldoc = %{htmldoc_version}
Obsoletes: htmldoc < %{htmldoc_version}

AutoReqProv: no

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%install

mkdir -p %{buildroot}/usr/bin %{buildroot}/usr/share/
cp -a %{htmldoc_nix_path}/bin/htmldoc %{buildroot}/usr/bin
cp -a %{htmldoc_nix_path}/share/* %{buildroot}/usr/share

%clean

%files
%defattr(-,root,root)
/usr/bin/htmldoc
/usr/share/htmldoc
/usr/share/applications/*
/usr/share/man/man1/*
/usr/share/icons/hicolor/*/apps/*
/usr/share/mime/packages/htmldoc.xml
%doc /usr/share/doc/htmldoc/*
