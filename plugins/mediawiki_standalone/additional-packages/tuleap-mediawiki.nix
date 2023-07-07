{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:
let
  mediawiki = pkgs.stdenvNoCC.mkDerivation rec {
    pname = "mediawiki";
    version = "1.35.11";

    src = pkgs.fetchurl {
      url = "https://releases.wikimedia.org/mediawiki/${pkgs.lib.versions.majorMinor version}/${pname}-${version}.tar.gz";
      hash = "sha256-xmUbDcSgWWxGqRXNI7von/iziYPjYZCLX8gvApZJwRA=";
    };

    patches = [
      ./mediawiki/drop-myisam.patch
    ];

    dontPatchShebangs = true;

    installPhase = ''
      runHook preInstall
      mkdir $out/
      mv vendor/autoload.php vendor/autoload_mediawiki.php
      cp -r * $out/
      runHook postInstall
    '';
  };
  mediawikiSkinsAndExtensions = pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-tuleap-skins-extensions";

    src = ./mediawiki-extensions;

    dontPatchShebangs = true;

    buildPhase = ''
      runHook preBuild
      rm composer.json
      rm composer.lock
      rm *.patch
      mv vendor/composer/ vendor/composer_tuleap-skins-extensions/
      substituteInPlace vendor/autoload.php --replace '/composer/' '/composer_tuleap-skins-extensions/'
      mv vendor/autoload.php vendor/autoload_tuleap-skins-extensions.php
      runHook postBuild
    '';

    installPhase = ''
      runHook preInstall
      mkdir $out/
      cp -r * $out/
      runHook postInstall
    '';
  };
  mediawikiTuleapConfig = pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-tuleap-config";

    src = ./mediawiki-tuleap-config;

    installPhase = ''
      runHook preInstall
      mkdir $out/
      cp -r * $out/
      runHook postInstall
    '';
  };
  mediawikiTuleapConfigSuspended = pkgs.stdenvNoCC.mkDerivation {
      name = "mediawiki-tuleap-config-suspended";

      src = ./mediawiki-extensions/extensions/TuleapWikiFarm/docs;

      installPhase = ''
        runHook preInstall
        mkdir $out/
        cp LocalSettings.SUSPENDED.php $out/
        runHook postInstall
      '';
    };
  mediawikiTuleapFlavorTarball = pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-tuleap-flavor.tar";

    src = pkgs.buildEnv {
      name = "tuleap-mediawiki-flavor-src";
      paths = [ mediawiki mediawikiSkinsAndExtensions mediawikiTuleapConfig mediawikiTuleapConfigSuspended ];
    };

    patches = [
      ./mediawiki-extensions/mpdf-extension-mpdf-8.patch
    ];

    unpackPhase = ''
      runHook preUnpack
      cp -rL "$src/"* ./
      chmod -R u+w ./
      runHook postUnpack
    '';

    buildPhase = ''
      runHook preBuild
      find . -name 'tests' -type d -prune -exec rm -r '{}' \;
      find . -name '.htaccess' -type f -delete
      find . -name 'Gruntfile.js' -type f -delete
      find . -name 'jsduck.json' -type f -delete
      find . -name 'phpunit.xml.dist' -type f -delete
      find . -name 'README.md' -type f -delete
      find . -name 'README' -type f -delete
      rm -r ./docs/
      rm -r ./extensions/Scribunto/includes/engines/LuaStandalone/binaries/
      runHook postBuild
    '';

    installPhase = ''
      runHook preInstall
      tar -cf "$out" .
      runHook postInstall
    '';
  };
  tuleapVersion = builtins.readFile ../../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "mediawiki-tuleap-flavor";

  srcs = [ mediawikiTuleapFlavorTarball ./mediawiki-tuleap-flavor.spec ];

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  unpackPhase = ''
    runHook preUnpack
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
    runHook postUnpack
  '';

  dontConfigure = true;

  buildPhase = ''
    runHook preBuild
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "mw_version ${mediawiki.version}" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_datadir /usr/share" \
      -bb mediawiki-tuleap-flavor.spec
      runHook postBuild
  '';

  installPhase = ''
    runHook preInstall
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
    runHook postInstall
  '';

  dontFixUp = true;
}
