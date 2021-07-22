{}:

let
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/1441fa74d213d7cc120d9d7d49e540c1fc59bc58.tar.gz";
    sha256 = "152qb7ch0r4bidik33zd0a9wl0929zr0dqs5l5ksm7vh3assc7sc";
  } ) {};
in pinnedNixpkgs
