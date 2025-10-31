{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; },
}:

let
  tuleapSmokescreenBin = pkgs.buildGoLatestModule {
    name = "tuleap-smokescreen-bin";

    src = ./tuleap-smokescreen;

    vendorHash = "sha256-9TFT2XCXGN6NMMpGF4tdjBFaDBh2HaiOvf5U28mymmk=";

    env.CGO_ENABLED = 0;

    ldflags = [
      "-s"
      "-w"
    ];
  };
  tuleapVersion = builtins.readFile ../../VERSION;
in
pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-smokescreen-rpm-package";
  srcs = [
    "${tuleapSmokescreenBin}/bin/tuleap-smokescreen"
    ./tuleap-smokescreen/tuleap-smokescreen.service
  ];

  nativeBuildInputs = [ pkgs.rpm ];

  dontConfigure = true;

  unpackPhase = ''
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
  '';

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_bindir /usr/bin" \
      --define "%_unitdir /usr/lib/systemd/system" \
      -bb ${./tuleap-smokescreen/tuleap-smokescreen.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
