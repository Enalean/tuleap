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
        rev = "a239c4a93093d9f3e0e34ea4d254bde463ad38b1";
        sha256 = "sha256-/cLTYBZJspmaUvPbeqhi751LqzUyrHA3wcjMOUuzxg8=";
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
    mkdir -p $out/el9/
    mv RPMS/noarch/*.rpm $out/el9/
  '';

  dontFixUp = true;
}
