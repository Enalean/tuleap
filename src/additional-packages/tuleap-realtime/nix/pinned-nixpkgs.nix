{}:

let
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/b283b64580d1872333a99af2b4cef91bb84580cf.tar.gz";
    sha256 = "0gmrpfzc622xl1lv3ffaj104j2q3nmia7jywafqmgmrcdm9axkkp";
  } ) {};
in pinnedNixpkgs
