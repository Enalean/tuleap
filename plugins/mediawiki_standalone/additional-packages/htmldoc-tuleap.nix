{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
    tuleapVersion = builtins.readFile ../../../VERSION;
    htmldocStatic = pkgs.pkgsStatic.htmldoc;
in pkgs.stdenvNoCC.mkDerivation {
      name = "htmldoc-tuleap";
      src = ./htmldoc-tuleap.spec;

      nativeBuildInputs = [ pkgs.rpm pkgs.file ];

      dontUnpack = true;
      dontConfigure = true;

      buildPhase = ''
        rpmbuild \
          --define "tuleap_version ${tuleapVersion}" \
          --define "htmldoc_nix_path ${htmldocStatic}" \
          --define "htmldoc_version ${htmldocStatic.version}" \
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
