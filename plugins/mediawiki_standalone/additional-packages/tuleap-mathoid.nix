{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:
let
  mathoidNpmPackage = pkgs.buildNpmPackage rec {
    name = "mathoid";

    src = pkgs.nix-gitignore.gitignoreSourcePure
      ''
      *
      !*.js
      !package.json
      !package-lock.json
      !lib/
      !routes/
      ''
      (pkgs.fetchFromGitLab {
        domain = "gitlab.wikimedia.org";
        owner = "repos";
        repo = "mediawiki/services/mathoid";
        rev = "221d528610a851c458db8a177e9e2803209dd43c";
        hash = "sha256-zjCgGkEOq+oidTBPBsmNH8an7ADGOrxXn4lfxGjFeTk=";
      })
    ;

    patches = [
      ./mediawiki-mathoid/cleanup-mathoid-deps.patch
    ];

    npmDepsHash = "sha256-Sjh4h+Tvc1h+L1FBzSpOSaKqlW6ASabLNZu7dGNNNHY=";

    # Dropping service-runner as it depends on heapdump which require some specific binary (and we do not need it)
    postPatch = ''
      substituteInPlace package.json --replace '"service-runner": "^3.1.0",' ""
    '';

    dontNpmBuild = true;
    dontPatchShebangs = true;

    nodejs = pkgs.nodejs_20;
  };
  unpackPhaseCopyMultipleSrcs = ''
    runHook preUnpack
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
    runHook postUnpack
  '';
  mathoidTarball = pkgs.stdenvNoCC.mkDerivation rec {
    name = "tuleap-mathoid.tar";

    srcs = [ mathoidNpmPackage ./mediawiki-mathoid/config.yaml ];

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
