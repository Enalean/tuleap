%global _hardened_build 1
%global mediawiki_tuleap_extensions_path /usr/share/mediawiki-tuleap-123/extensions

Name:          mediawiki-math-tuleap
Version:       1.23
Release:       2%{?nixpkgs_epoch}%{?dist}
Summary:       Mediawiki Math Extension
Group:         Development/Tools
License:       GPLv2+
URL:           https://github.com/wikimedia/mediawiki-extensions-Math
Source0:       mediawiki-extensions-Math-src.tar
Source1:       cancel.sty
Patch0:        0001-Allow-to-pass-custom-CFLAGS-options-to-ocamlopt.patch
Patch1:        0002-Use-absolute-paths-to-call-LaTeX-tools.patch

BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:     x86_64

Requires: texlive, texlive-latex, dvipng, php-mediawiki-tuleap-123
BuildRequires: ocaml, make

%description
%{summary}.

%prep
%setup -q -c mediawiki-extensions-Math

%patch 0 -p1
%patch 1 -p1

%build
CFLAGS='-O2 -Wall -Werror -Wextra -pedantic -fstack-protector-strong -Wl,-z,relro,-z,now -D_FORTIFY_SOURCE=2 -static'
export CFLAGS
make %{?_smp_mflags} -C math/
make %{?_smp_mflags} -C texvccheck/

%install
rm -rf %{buildroot}

mkdir -p %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/
cp -rp *.php db/ i18n/ images/ maintenance/ mathoid/ modules/ %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/
mkdir -p %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/math/
cp -p math/texvc %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/math/
mkdir -p %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/texvccheck/
cp -p texvccheck/texvccheck %{buildroot}%{mediawiki_tuleap_extensions_path}/Math/texvccheck/
mkdir -p %{buildroot}/usr/share/texmf/tex/latex/ltxmisc/
cp -p %{SOURCE1} %{buildroot}/usr/share/texmf/tex/latex/ltxmisc/

%post
/usr/bin/texhash >/dev/null 2>&1 || :

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%{!?_licensedir:%global license %%doc}
%license COPYING
%doc README
%{mediawiki_tuleap_extensions_path}/Math/
/usr/share/texmf/tex/latex/ltxmisc/cancel.sty
