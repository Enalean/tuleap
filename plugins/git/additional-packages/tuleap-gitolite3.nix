{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

let
  tuleapVersion = builtins.readFile ../../../VERSION;
in pkgs.stdenvNoCC.mkDerivation rec {
  pname = "tuleap-gitolite3";
  version = "3.6.13";

  src = pkgs.stdenvNoCC.mkDerivation {
    name = "gitolite-src.tar";

    src = pkgs.fetchFromGitHub {
      owner = "sitaramc";
      repo = "gitolite";
      rev = "v${version}";
      hash = "sha256-/VBu+aepIrxWc2padPa/WoXbIdKfIwqmA/M8d1GE5FI=";
    };

    installPhase = ''
      runHook preInstall
      tar -cf "$out" .
      runHook postInstall
    '';
  };

  nativeBuildInputs = [ pkgs.rpm pkgs.file pkgs.perl ];

  unpackPhase = ''
    ln -s $src $(stripHash $src)
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
        -bb ${./tuleap-gitolite3.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
