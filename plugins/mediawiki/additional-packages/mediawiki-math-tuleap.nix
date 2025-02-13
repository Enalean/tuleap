{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

let
  pkgsPinForOCaml = import (builtins.fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/ab6176ac5b0ee4f18e9cb380a41a6e1816c7bc89.tar.gz";
    sha256 = "14d5xrm6vywqg8gkyvqsaf93x9kqmdfqcsfdj9a5xxl1qds5naiz";
  }) { };
  mathExtensionTarballSrc = pkgs.stdenvNoCC.mkDerivation {
    name = "mediawiki-extensions-Math-src";
    src = pkgs.fetchFromGitHub {
      owner = "wikimedia";
      repo = "mediawiki-extensions-Math";
      rev = "5d564a6b6796638ee2566f2963e9c386b276d2bf";
      hash = "sha256-ecTqFBswRr1rLfOpOmQfpPSrcwnVwjPwE2vrNyhyhuE=";
    };

    dontConfigure = true;
    dontBuild = true;
    dontPatch = true;
    dontFixup = true;

    installPhase = ''
      runHook preInstall

      mkdir $out/
      pushd $src/

      tar cf $out/mediawiki-extensions-Math-src.tar *

      popd

      runHook postInstall
    '';
  };
in pkgs.stdenv.mkDerivation {
  name = "mediawiki-math-tuleap";
  src = pkgs.symlinkJoin {
    name = "mediawiki-math-tuleap-src";
    paths = [
      (./mediawiki-math-tuleap)
      mathExtensionTarballSrc
    ];
  };

  buildInputs = [ pkgs.glibc.static ];
  nativeBuildInputs = [ pkgs.rpm pkgs.file pkgsPinForOCaml.ocaml ];

  dontConfigure = true;

  buildPhase = ''
    rpmbuild --nodeps \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_binary_payload w16T8.zstdio" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_datadir /usr/share" \
      -bb mediawiki-math-tuleap.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
