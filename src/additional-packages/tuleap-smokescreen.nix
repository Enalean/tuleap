{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  tuleapSmokescreenBin = pkgs.buildGoModule {
    name = "tuleap-smokescreen-bin";

    src = pkgs.fetchFromGitHub {
      owner = "stripe";
      repo = "smokescreen";
      rev = "8c0fa26edf63f35d5632ba7682d78ff07a306819";
      hash = "sha256-+v0Yr+KtKEYgSwyxDOqWjCKxTsC8B2nKFQL1qLBjCaE=";
    };

    vendorHash = null;

    subPackages = [ "." ];

    CGO_ENABLED=0;

    ldflags = [ "-s" "-w" ];

    # Do not attempt to run the tests: the most interesting ones require network access (which we do not have in Nix sandbox)
    # and it does not play well with a TMPDIR mounted on a tmpfs directory
    doCheck = false;
  };
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-smokescreen-rpm-package";
  srcs = [
    "${tuleapSmokescreenBin}/bin/smokescreen"
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
