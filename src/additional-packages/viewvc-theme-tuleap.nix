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
        pnpm install --frozen-lockfile --side-effects-cache-readonly --ignore-scripts
        find node_modules/ -wholename '*/.bin/*' -type f -exec sed -i 's/  export NODE_PATH=.*/  true/' {} \;
        rm node_modules/.modules.yaml
        mv node_modules $out
      '';
    };
  name = "viewvc-theme-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/3rdparty/viewvc-theme-tuleap.git";
    rev = "fe83d432e5c60b6e39fd3fe029a13862af5262af";
    sha256 = "sha256-7I9+dICCxyj3WQGhpYwUd/XjgAddwitMvvncyM7TNrI="; # Please also check if the node_modules sha256 does not need to be updated
  };
  node_modules = fetchNodeModules {
    inherit src;
    pnpm = jsBuildTool;
    sha256 = "sha256-tFHeoAsfoGZc2u31ZeAlqwn3VfJ8XCizzl9yZVWmLmc=";
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
