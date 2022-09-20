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
    rev = "72a58bd2659e5a5d3b1c16f7fa6605b51779f7df";
    sha256 = "sha256-xki7J9Z6ERSi5IAdYFOXpBY2gHZqpjyYDjI3p/oM67k="; # Please also check if the node_modules sha256 does not need to be updated
  };
  node_modules = fetchNodeModules {
    inherit src;
    pnpm = jsBuildTool;
    sha256 = "sha256-928v1+BXSNz2uSb6iKMTxkYmikCGnhsxgD9xVqRLl0M=";
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
