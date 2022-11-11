{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  pkgsWithOpenSSL307 = import (builtins.fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/1f3ebb2bd1a353a42e8f833895c26d8415c7b791.tar.gz";
    sha256 = "03y1a3lv44b4fdnykyms5nd24v2mqn8acz1xa4jkbmryc29rsgcw";
  }) { };
  tuleapVersion = builtins.readFile ../../../VERSION;
  tuleapGitBinBasePath = "/usr/lib/tuleap/git";
  gitStatic = ((pkgs.pkgsStatic.gitMinimal.override { openssl = pkgsWithOpenSSL307.openssl; }).overrideAttrs (oldAttrs: rec {
    version = "2.38.1";
    src = pkgs.fetchurl {
      url = "https://www.kernel.org/pub/software/scm/git/git-${version}.tar.xz";
      sha256 = "sha256-l9346liiueD7wlCOJFAoynWRG9ONFVFhaxSMGqV0Ctk=";
    };

    dontPatchShebangs = true;
    separateDebugInfo = false;

    makeFlags = oldAttrs.makeFlags or [] ++ [ "prefix=${tuleapGitBinBasePath}" ];

    installFlags = oldAttrs.installFlags or [] ++ [ "DESTDIR=$(out)" ];

    # Disable the postInstall of the nixpkgs Git package
    # https://github.com/NixOS/nixpkgs/blob/555ff75b3e2ec8cff0598baa6def6cd88f380a8e/pkgs/applications/version-management/git-and-tools/git/default.nix#L154-L270
    # Its role is to rewrite the installed scripts so they can find utilities like grep/cut/wc in the Nix store and to
    # deploy additional helpers like shell completions files. It is not something we need for our context and it cannot
    # work without modification because it expects to find files under $out and not under $out/$tuleapGitBinBasePath.
    postInstall = "";

    doInstallCheck = false;
  }));
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
