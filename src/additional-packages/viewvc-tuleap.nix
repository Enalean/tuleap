{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
    name = "viewvc-tuleap";
in pkgs.stdenvNoCC.mkDerivation {
    inherit name;

    srcs = [
        (pkgs.fetchFromGitHub {
             name  = "viewvc-tuleap";
             owner = "viewvc";
             repo = "viewvc";
             rev = "3f923fd12893c1c53af96d79be0114d1fa554c34";
             sha256 = "sha256-CWaoCQuS7rnHvE1y3PL9l4RTdDh+Zc+t6qSHzKYPn/8=";
           }
         )
         (./viewvc-tuleap/viewvc-tuleap.spec)
      ];

    nativeBuildInputs = [ pkgs.rpm pkgs.python39 ];

    unpackPhase = ''
      runHook preUnpack

      for _src in $srcs; do
        cp -r "$_src" $(stripHash "$_src")
      done

      runHook postUnpack
    '';

    dontConfigure = true;

    buildPhase = ''
    tar cfz ${name}.tar.gz ${name}
    rpmbuild \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_bindir /usr/bin" \
        --define "%_unitdir /usr/lib/systemd/system" \
        --define "%python_sitelib /usr/lib/python3.9/site-packages" \
        --define "%__python python" \
        --define "%_sysconfdir /etc" \
        -bb ${./viewvc-tuleap/viewvc-tuleap.spec}
    '';

    installPhase = ''
      mkdir $out/
      mv RPMS/noarch/*.rpm $out/
    '';

    dontFixUp = true;
}
