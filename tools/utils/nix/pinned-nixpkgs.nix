{}:

let
    pinnedNixpkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/efee454783c5c14ae78687439077c1d3f0544d97.tar.gz") {};
in pinnedNixpkgs
