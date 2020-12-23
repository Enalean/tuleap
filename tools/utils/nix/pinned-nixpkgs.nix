{}:

let
    pinnedNixpkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/2080afd039999a58d60596d04cefb32ef5fcc2a2.tar.gz") {};
in pinnedNixpkgs
