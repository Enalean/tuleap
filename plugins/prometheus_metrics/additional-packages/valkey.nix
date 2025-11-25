{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:
# Pull temporarily valkey RPM into Tuleap repository Rocky 9.7/10.1 are released
let
  rpmEl9 = pkgs.fetchurl {
    urls = [
      "https://last-public-almalinux.snap.mirrors.ovh.net/9.7/AppStream/x86_64/os/Packages/valkey-8.0.4-3.el9_7.x86_64.rpm"
      "https://web.archive.org/web/20251124140900/https://last-public-almalinux.snap.mirrors.ovh.net/9.7/AppStream/x86_64/os/Packages/valkey-8.0.4-3.el9_7.x86_64.rpm"
    ];
    hash = "sha256-ofKT0ctDdvxK7Z0m7S8c7bqKLOmq1Y1Ka2+1ZhGTa9U=";
  };
  rpmEl10 = pkgs.fetchurl {
    urls = [
      "https://last-public-almalinux.snap.mirrors.ovh.net/10.1/AppStream/x86_64/os/Packages/valkey-8.0.6-1.el10_0.x86_64.rpm"
      "https://web.archive.org/web/20251124141428/https://last-public-almalinux.snap.mirrors.ovh.net/10.1/AppStream/x86_64/os/Packages/valkey-8.0.6-1.el10_0.x86_64.rpm"
    ];
    hash = "sha256-Jo0i79gA49z83rrvlxchrKScBs0iL6g6mfrLMjUNlPw=";
  };
in
pkgs.stdenvNoCC.mkDerivation {
  name = "valkey-rpm";
  dontUnpack = true;
  dontPatch = true;
  dontConfigure = true;
  dontBuild = true;
  dontFixup = true;
  installPhase = ''
    mkdir -p $out/el9/
    cp ${rpmEl9} $out/el9/$(stripHash "${rpmEl9}")
    mkdir -p $out/el10/
    cp ${rpmEl10} $out/el10/$(stripHash "${rpmEl10}")
  '';
}
