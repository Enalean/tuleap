{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; },
  jsBuildTool ? (import ../../tools/utils/nix/build-tools/build-tools-js.nix) { inherit pkgs; }
}:

let
  fetchNodeModules = { src, pnpm, sha256 }:
    pkgs.stdenvNoCC.mkDerivation {
      name = "node_modules";

      outputHashAlgo = "sha256";
      outputHash = sha256;
      outputHashMode = "recursive";

      nativeBuildInputs = [ pnpm ];

      buildCommand = ''
        cp -r ${src}/* .
        export HOME=.
        pnpm config set extend-node-path false
        pnpm install --frozen-lockfile
        rm node_modules/.modules.yaml
        mv node_modules $out
      '';
    };
  name = "viewvc-theme-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/3rdparty/viewvc-theme-tuleap.git";
    rev = "2d3dc5b627f59153cdaafd04b77a97ed1f2329c0";
    sha256 = "0zv3b5ajsylm1azf2p28v0lqpmj7111bsvw33q5l5307kb1bjdkm"; # Please also check if the node_modules sha256 does not need to be updated
  };
  node_modules = fetchNodeModules {
    inherit src;
    pnpm = jsBuildTool;
    sha256 = "1j0l66l68caw0rarjinjm8s52mnxw96s6ivwrxncnnk4259jrcvq";
  };
in pkgs.stdenvNoCC.mkDerivation {
  inherit name src;

  nativeBuildInputs = [ pkgs.rpm pkgs.file jsBuildTool ];

  dontConfigure = true;

  buildPhase = ''
    cp -r ${node_modules} ./node_modules
    pnpm run build
    tar cfz ${name}.tar.gz src
    rpmbuild \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "_binary_payload w9.xzdio" \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_datadir /usr/share" \
        --define "%_sysconfdir /etc" \
        -bb ${name}.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
