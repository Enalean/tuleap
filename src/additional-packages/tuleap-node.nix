{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  version = "20.11.1";
  fetchNodeBin = { url, hash, name }: pkgs.fetchurl {
    inherit url hash name;
    downloadToTemp = true;
    recursiveHash = true;
    postFetch = ''
      ${pkgs.gnutar}/bin/tar xvf $downloadedFile --strip-components=2 ${name}/bin/node
      mkdir $out
      mv node $out/node
    '';
  };
  el7NodeBin = fetchNodeBin rec {
    name = "node-v${version}-linux-x64-glibc-217";
    url = "https://unofficial-builds.nodejs.org/download/release/v${version}/${name}.tar.xz";
    hash = "sha256-yz+wORSNvaLO6GUb6QL87IouGIu3ICThEpcvRBftNA8=";
  };
  el9NodeBin = fetchNodeBin rec {
    name = "node-v${version}-linux-x64";
    url = "https://nodejs.org/dist/v${version}/${name}.tar.xz";
    hash = "sha256-OaRWD8Gcu9xjxgie676lTwruHZ0J3/igMx1/PdCSXM4=";
  };
  buildNodeRPM = { version, bin, dist, outputFolder }: pkgs.stdenvNoCC.mkDerivation {
    pname = "tuleap-node-rpm-package-${dist}";
    inherit version;
    srcs = [
      "${bin}/node"
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
        --define "node_version ${version}" \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "dist .${dist}" \
        --define "_sourcedir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_bindir /usr/bin" \
        -bb ${./tuleap-node/tuleap-node.spec}
    '';

    installPhase = ''
      mkdir -p $out/${outputFolder}/
      mv RPMS/x86_64/*.rpm $out/${outputFolder}/
    '';

    dontFixUp = true;
  };
in pkgs.symlinkJoin {
  name = "all-tuleap-node-rpm-flavor";
  paths = [
    (buildNodeRPM {
      inherit version;
      bin = el7NodeBin;
      dist = "el7";
      outputFolder = "centos7";
    })
    (buildNodeRPM {
      inherit version;
      bin = el9NodeBin;
      dist = "el9";
      outputFolder = "el9";
    })
  ];
}
