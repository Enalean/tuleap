{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  version = "22.15.1";
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
  nodeBin = fetchNodeBin rec {
    name = "node-v${version}-linux-x64";
    url = "https://nodejs.org/dist/v${version}/${name}.tar.xz";
    hash = "sha256-mQ4vEnuvDGx9i31mvI/+QAnGXtNkHC7elDzCMS6nFQY=";
  };
  buildNodeRPM = { version, bin }: pkgs.stdenvNoCC.mkDerivation {
    pname = "tuleap-node-rpm-package";
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
        --define "_sourcedir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_bindir /usr/bin" \
        -bb ${./tuleap-node/tuleap-node.spec}
    '';

    installPhase = ''
      mkdir -p $out/
      mv RPMS/x86_64/*.rpm $out/
    '';

    dontFixUp = true;
  };
in buildNodeRPM {
  inherit version;
  bin = nodeBin;
}
