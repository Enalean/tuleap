{ pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

let
  shatteredDoc1 = pkgs.fetchurl {
    url = "https://shattered.io/static/shattered-1.pdf";
    hash = "sha256-K7eHpz43NS+SODq+fikCk20QWa2fG6baqpweWO5pcNA=";
  };
  shatteredDoc2 = pkgs.fetchurl {
    url = "https://shattered.io/static/shattered-2.pdf";
    hash = "sha256-1EiHddKb3veZM2fVQQZNvdpQ04P4nwqhOm/y4IlLpf8=";
  };
  rustBinWithMuslTarget = pkgs.rust-bin.stable.latest.minimal.override {
    targets = [ "x86_64-unknown-linux-musl" ];
  };
  sha1collisiondetectorBin = pkgs.stdenvNoCC.mkDerivation {
    name = "sha1collisiondetector";

    src = ./sha1collisiondetector;

    nativeBuildInputs = [ pkgs.rustPlatform.cargoSetupHook rustBinWithMuslTarget ];

    cargoDeps = pkgs.rustPlatform.importCargoLock {
      lockFile = ./sha1collisiondetector/Cargo.lock;
    };

    buildPhase = ''
      runHook preBuild

      cargo build --frozen --release --target x86_64-unknown-linux-musl

      runHook postBuild
    '';

    installPhase = ''
      runHook preInstall

      mkdir -p $out/bin/
      cp target/x86_64-unknown-linux-musl/release/sha1collisiondetector $out/bin/

      runHook postInstall
    '';

    doInstallCheck = true;
    installCheckPhase = ''
      runHook preCheck

      echo "Testing not colliding file"
      $out/bin/sha1collisiondetector < ${./sha1collisiondetector/Cargo.toml}
      echo "Testing colliding files"
      $out/bin/sha1collisiondetector < ${shatteredDoc1} && exit 1 || test $? -eq 3
      $out/bin/sha1collisiondetector < ${shatteredDoc2} && exit 1 || test $? -eq 3

      runHook postCheck
    '';
  };
in pkgs.stdenvNoCC.mkDerivation {
  name = "sha1collisiondetector-rpm-package";
  src = sha1collisiondetectorBin;

  nativeBuildInputs = [ pkgs.rpm ];

  dontConfigure = true;

  buildPhase = ''
    rpmbuild \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_sourcedir $src/bin/" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_bindir /usr/bin" \
      -bb ${./sha1collisiondetector/sha1collisiondetector.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}