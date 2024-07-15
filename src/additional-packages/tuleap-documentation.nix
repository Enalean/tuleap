{ pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

let
  tuleapVersion = pkgs.lib.strings.fileContents ../../VERSION;
  buildTuleapDocumentation = lang:
    let
      branchName = if (builtins.match "^[0-9]+\.[0-9]+\.99.*$" tuleapVersion != null) then "master" else tuleapVersion;
      src = builtins.fetchTarball "https://github.com/Enalean/tuleap-documentation-${lang}/archive/refs/heads/${branchName}.tar.gz";
    in pkgs.stdenvNoCC.mkDerivation {
      name = "tuleap-documentation-${lang}";

      inherit src;

      nativeBuildInputs = (import "${src}/build-support/build-tools.nix" { } );

      buildPhase = ''
        runHook preBuild

        make html

        runHook postBuild
      '';

      installPhase = ''
        runHook preInstall

        cp -r _build/html/ $out/

        runHook postInstall
      '';
    };
  documentationTarball = pkgs.stdenvNoCC.mkDerivation {
    name = "tuleap-documentation-tarball";

    src = pkgs.symlinkJoin {
      name = "tuleap-documention-all-langs";
      paths = [
        (buildTuleapDocumentation "en")
        (buildTuleapDocumentation "fr")
      ];
    };

    dontConfigure = true;
    dontBuild = true;

    installPhase = ''
      runHook preInstall

      pushd $src/
      tar hczf $out *
      popd

      runHook postInstall
    '';
  };
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-documentation-rpm";
  src = documentationTarball;

  nativeBuildInputs = [ pkgs.rpm ];

  dontConfigure = true;
  dontUnpack = true;

  buildPhase = ''
    runHook preBuild

    cp $src tuleap-documentation.tar.gz
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "_sourcedir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_datadir /usr/share" \
      -bb ${./tuleap-documentation/tuleap-documentation.spec}

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
