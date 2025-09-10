{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  name = "viewvc-tuleap";
  pkgsPython39 = import (pkgs.fetchFromGitHub {
    name = "nixpkgs-with-python-39";
    owner = "NixOS";
    repo = "nixpkgs";
    rev = "199169a2135e6b864a888e89a2ace345703c025d";
    hash = "sha256-igS2Z4tVw5W/x3lCZeeadt0vcU9fxtetZ/RyrqsCRQ0=";
  }) { };
  buildViewVCRPM = { python, OS }: pkgs.stdenvNoCC.mkDerivation {
   inherit name;

   srcs = [
     (pkgs.fetchFromGitHub {
         name  = "viewvc-tuleap";
         owner = "viewvc";
         repo = "viewvc";
         rev = "bd9858e201a45318fd100b0632e8e029187e2e2a";
         hash = "sha256-gjQ4lX/WjVsWZf/W1uMW/dYigjJ38SmzoJGrR29xAXo=";
       }
     )
     (./viewvc-tuleap/viewvc-tuleap.spec)
   ];

   nativeBuildInputs = [
     pkgs.rpm
     python
   ];

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
       --define "%python_sitelib /usr/lib/python${pkgs.lib.versions.majorMinor python.version}/site-packages" \
       --define "%__python python" \
       --define "%_sysconfdir /etc" \
       -bb ${./viewvc-tuleap/viewvc-tuleap.spec}
   '';

   installPhase = ''
     mkdir -p $out/${OS}/
     mv RPMS/noarch/*.rpm $out/${OS}/
   '';

   dontFixUp = true;
 };
in pkgs.symlinkJoin {
  name = "myexample";
  paths = [
    (buildViewVCRPM { python = pkgsPython39.python39; OS = "el9"; } )
    (buildViewVCRPM { python = pkgs.python312; OS = "el10"; } )
  ];
}
