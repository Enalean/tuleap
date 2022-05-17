{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:
let
  unpackPhaseCopyMultipleSrcs = ''
    runHook preUnpack
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
    runHook postUnpack
  '';
  mathoidTarball = pkgs.stdenvNoCC.mkDerivation rec {
    name = "tuleap-mathoid.tar";

    srcs = [ ./mediawiki-mathoid/dist/mathoid-cli ./mediawiki-mathoid/src/config.yaml ];

    unpackPhase = unpackPhaseCopyMultipleSrcs;

    dontBuild = true;

    installPhase = ''
      runHook preInstall
      tar -cf "$out" .
      runHook postInstall
    '';
  };
  tuleapVersion = builtins.readFile ../../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-mathoid";

  srcs = [ mathoidTarball ./tuleap-mathoid.spec ];

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  unpackPhase = unpackPhaseCopyMultipleSrcs;

  dontConfigure = true;

  buildPhase = ''
    runHook preBuild
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      -bb tuleap-mathoid.spec
    runHook postBuild
 '';

 installPhase = ''
   runHook preInstall
   mkdir $out/
   mv RPMS/x86_64/*.rpm $out/
   runHook postInstall
 '';

 dontFixUp = true;
}
