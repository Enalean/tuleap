{}:

let
    pinnedNixpkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/267761cf44498f9e1aa81dbdb92d77d5873dd9f6.tar.gz") {};
in pinnedNixpkgs
