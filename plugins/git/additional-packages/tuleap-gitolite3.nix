{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

let
  tuleapVersion = builtins.readFile ../../../VERSION;
in pkgs.stdenvNoCC.mkDerivation rec {
  pname = "tuleap-gitolite3";
  version = "3.6.12";

  srcs = [
    (pkgs.fetchurl {
         url = "https://github.com/sitaramc/gitolite/archive/v${version}.tar.gz";
         sha256 = "sha256-jFWXbtVhuOq/OQl7f8ucbodBws7qhe9FKaBrGt/4ULY=";
       }
     )
     (./tuleap-gitolite3.spec)
  ];

  nativeBuildInputs = [ pkgs.rpm pkgs.file pkgs.perl ];

  unpackPhase = ''
        for srcFile in $srcs; do
          cp -a $srcFile $(stripHash $srcFile)
        done
      '';

  dontConfigure = true;

  buildPhase = ''
    rpmbuild \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "gitolite_version ${version}" \
        --define "tuleap_version ${tuleapVersion}" \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_datadir /usr/share" \
        --define "%_bindir /usr/bin" \
        -bb tuleap-gitolite3.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
