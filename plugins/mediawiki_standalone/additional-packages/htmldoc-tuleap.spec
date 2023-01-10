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

Provides: htmldoc
Obsoletes: htmldoc <= %{htmldoc_version}

Requires:	dejavu-sans-fonts dejavu-sans-mono-fonts dejavu-serif-fonts
Requires:	urw-base35-fonts-legacy

AutoReqProv: no

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

%description
%{summary}.

%install

mkdir -p %{buildroot}/usr/bin %{buildroot}/usr/share/htmldoc/fonts/
cp -a %{htmldoc_nix_path}/bin/htmldoc %{buildroot}/usr/bin
cp -a %{htmldoc_nix_path}/share/applications %{buildroot}/usr/share
cp -a %{htmldoc_nix_path}/share/doc %{buildroot}/usr/share
cp -a %{htmldoc_nix_path}/share/icons %{buildroot}/usr/share
cp -a %{htmldoc_nix_path}/share/man %{buildroot}/usr/share
cp -a %{htmldoc_nix_path}/share/mime %{buildroot}/usr/share

cp -a %{htmldoc_nix_path}/share/htmldoc/data %{buildroot}/usr/share/htmldoc

# From original htmldoc package shipped in epel
# https://src.fedoraproject.org/rpms/htmldoc/blob/epel7/f/htmldoc.spec#_65
(
cd %{buildroot}/usr/share/htmldoc/fonts/
ln -s /usr/share/X11/fonts/urw-fonts/n022003l.afm Courier.afm
ln -s /usr/share/X11/fonts/urw-fonts/n022004l.afm Courier-Bold.afm
ln -s /usr/share/X11/fonts/urw-fonts/n022024l.afm Courier-BoldOblique.afm
ln -s /usr/share/X11/fonts/urw-fonts/n022024l.pfb Courier-BoldOblique.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n022004l.pfb Courier-Bold.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n022023l.afm Courier-Oblique.afm
ln -s /usr/share/X11/fonts/urw-fonts/n022023l.pfb Courier-Oblique.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n022003l.pfb Courier.pfb
ln -s /usr/share/X11/fonts/urw-fonts/d050000l.afm Dingbats.afm
ln -s /usr/share/X11/fonts/urw-fonts/d050000l.pfb Dingbats.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n019003l.afm Helvetica.afm
ln -s /usr/share/X11/fonts/urw-fonts/n019004l.afm Helvetica-Bold.afm
ln -s /usr/share/X11/fonts/urw-fonts/n019024l.afm Helvetica-BoldOblique.afm
ln -s /usr/share/X11/fonts/urw-fonts/n019024l.pfb Helvetica-BoldOblique.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n019004l.pfb Helvetica-Bold.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n019023l.afm Helvetica-Oblique.afm
ln -s /usr/share/X11/fonts/urw-fonts/n019023l.pfb Helvetica-Oblique.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n019003l.pfb Helvetica.pfb
ln -s /usr/share/X11/fonts/urw-fonts/s050000l.afm Symbol.afm
ln -s /usr/share/X11/fonts/urw-fonts/s050000l.pfb Symbol.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n021004l.afm Times-Bold.afm
ln -s /usr/share/X11/fonts/urw-fonts/n021024l.afm Times-BoldItalic.afm
ln -s /usr/share/X11/fonts/urw-fonts/n021024l.pfb Times-BoldItalic.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n021004l.pfb Times-Bold.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n021023l.afm Times-Italic.afm
ln -s /usr/share/X11/fonts/urw-fonts/n021023l.pfb Times-Italic.pfb
ln -s /usr/share/X11/fonts/urw-fonts/n021003l.afm Times-Roman.afm
ln -s /usr/share/X11/fonts/urw-fonts/n021003l.pfb Times-Roman.pfb
ln -s /usr/share/fonts/dejavu/DejaVuSans-BoldOblique.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSans-Bold.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSansMono-BoldOblique.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSansMono-Bold.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSansMono-Oblique.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSansMono.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSans-Oblique.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSans.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSerif-BoldItalic.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSerif-Bold.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSerif-Italic.ttf
ln -s /usr/share/fonts/dejavu/DejaVuSerif.ttf
)

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
