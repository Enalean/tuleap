{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  tuleapMercureBin = pkgs.buildGoModule {
    name = "tuleap-mercure-bin";

    src = ./tuleap-mercure;

    vendorSha256 = "sha256-MgYCTR47ZwP1Mk02iWhr3isdoXUWtohA6bJoWugxwDw=";

    CGO_ENABLED=0;

     ldflags = [ "-s" "-w" ];
  };
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-mercure-rpm-package";
  src = tuleapMercureBin;

  nativeBuildInputs = [ pkgs.rpm ];

  dontConfigure = true;

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_sourcedir $src/bin/" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_bindir /usr/bin" \
      -bb ${./tuleap-mercure/tuleap-mercure.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
