{}:

let
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/ee084c02040e864eeeb4cf4f8538d92f7c675671.tar.gz";
    sha256 = "1x8amcixdaw3ryyia32pb706vzhvn5whq9n8jin0qcha5qnm1fnh";
  } ) {};
in pinnedNixpkgs
