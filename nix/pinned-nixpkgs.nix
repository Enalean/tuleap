{}:

let
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/77fda7f672726e1a95c8cd200f27bccfc86c870b.tar.gz";
    sha256 = "07qj1d45pkqsmkahbhh7hilwwbvg8vlz1wg497hzjrlx1a57v4y5";
  } ) {};
in pinnedNixpkgs
