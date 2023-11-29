{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:
let
  buildMediawikiTarball = { version, tuleapUsage, srcHash }: pkgs.stdenvNoCC.mkDerivation rec {
    pname = "mediawiki";

    inherit version tuleapUsage;

    src = pkgs.fetchurl {
      url = "https://releases.wikimedia.org/mediawiki/${pkgs.lib.versions.majorMinor version}/${pname}-${version}.tar.gz";
      hash = srcHash;
    };

    dontPatchShebangs = true;

    installPhase = ''
      runHook preInstall
      mkdir $out/
      mv vendor/autoload.php vendor/autoload_mediawiki.php
      cp -r * $out/
      runHook postInstall
    '';
  };
  mediawikiCurrent = buildMediawikiTarball { version = "1.39.5"; tuleapUsage = "current-lts"; srcHash = "sha256-eFsJxBhRRPmvRnjFi1nzEv3zhyyveC/n2IFkkth+05w="; };
  mediawiki135 = buildMediawikiTarball { version = "1.35.13"; tuleapUsage = "1.35"; srcHash = "sha256-KpcAzhk9sZMtt74+G/3fE11iLUOZumvW0FcORR22O2E="; };
  buildMediawikiSkinsAndExtensions = mediawiki: pkgs.stdenvNoCC.mkDerivation rec {
    name = "mediawiki-tuleap-skins-extensions-${mediawiki.tuleapUsage}";

    src = ./. + "/mediawiki-extensions-${mediawiki.tuleapUsage}";

    dontPatchShebangs = true;

    buildPhase = ''
      runHook preBuild
      rm composer.json
      rm composer.lock
      rm *.patch || true
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
  buildMediawikiTuleapConfigSuspended = mediawiki: pkgs.stdenvNoCC.mkDerivation {
      name = "mediawiki-tuleap-config-suspended-${mediawiki.tuleapUsage}";

      src = ./. + "/mediawiki-extensions-${mediawiki.tuleapUsage}/extensions/TuleapWikiFarm/docs";

      installPhase = ''
        runHook preInstall
        mkdir $out/
        cp LocalSettings.SUSPENDED.php $out/
        runHook postInstall
      '';
    };
  buildMediawikiTuleapFlavorTarball = mediawiki: patches: pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-tuleap-flavor-${mediawiki.tuleapUsage}.tar";

    src = pkgs.buildEnv {
      name = "tuleap-mediawiki-flavor-${mediawiki.tuleapUsage}-src";
      paths = [ mediawiki (buildMediawikiSkinsAndExtensions mediawiki) mediawikiTuleapConfig (buildMediawikiTuleapConfigSuspended mediawiki) ];
    };

    inherit patches;

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
  buildMediawikiTuleapFlavorRPM = mediawiki: patches: pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-tuleap-flavor";

    srcs = [ (buildMediawikiTuleapFlavorTarball mediawiki patches) ./mediawiki-tuleap-flavor.spec ];

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
       --define "mw_tuleap_usage ${mediawiki.tuleapUsage}" \
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
  };
in pkgs.symlinkJoin {
  name = "all-mediawiki-tuleap-flavor-rpm";
  paths = [
    (buildMediawikiTuleapFlavorRPM
      mediawikiCurrent
      [
        (./. + "/mediawiki-extensions-current-lts/mpdf-extension-mpdf-8.patch")
      ]
    )
    (buildMediawikiTuleapFlavorRPM mediawiki135 [])
  ];
}
