{}:

let
    pinnedNixpkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/8e1891d5b8d0b898db8890ddab73141f0cd3c2bc.tar.gz") {};
in pinnedNixpkgs
