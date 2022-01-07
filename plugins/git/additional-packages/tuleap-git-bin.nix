{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  tuleapVersion = builtins.readFile ../../../VERSION;
  tuleapGitBinBasePath = "/usr/lib/tuleap/git";
  # We wrap the git binary to override some default absolute paths set at build time
  # You can find details about those environment variables here:
  # * https://git-scm.com/book/en/v2/Git-Internals-Environment-Variables
  # * https://git-scm.com/docs/git-init#_template_directory
  wrapperGitWithTuleapOptions = pkgs.writeTextFile {
    name = "git";
    executable = true;
    text = ''#! /usr/bin/bash -e
    export GIT_EXEC_PATH='${tuleapGitBinBasePath}/libexec/git-core'
    export GIT_TEMPLATE_DIR='${tuleapGitBinBasePath}/share/git-core/templates/'
    exec -a "$0" "${tuleapGitBinBasePath}/bin/.git-base" "$@"
    '';
  };
  gitStatic = (pkgs.pkgsStatic.gitMinimal.overrideAttrs (oldAttrs: rec {
    version = "2.34.1";
    src = pkgs.fetchurl {
      url = "https://www.kernel.org/pub/software/scm/git/git-${version}.tar.xz";
      sha256 = "sha256-OgdV3Rz6txok3ZbfNJjCnNCs0TsE89CL+TPoEobbgCw=";
    };

    dontPatchShebangs = true;

    postInstall = oldAttrs.postInstall or "" + ''
      mv $out/bin/{git,.git-base}
      cp ${wrapperGitWithTuleapOptions} $out/bin/git
    '';
  })).override { openssh = "/usr"; };
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-git-bin";
  src = ./tuleap-git-bin.spec;

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontUnpack = true;
  dontConfigure = true;

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "tuleap_git_base_path ${tuleapGitBinBasePath}" \
      --define "git_static_path ${gitStatic}" \
      --define "git_version ${gitStatic.version}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      -bb $src
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
