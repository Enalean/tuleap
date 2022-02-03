{}:

let
  nixpkgsJson = builtins.fromJSON (builtins.readFile ./nixpkgs-pin.json);
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${nixpkgsJson.rev}.tar.gz";
    sha256 = nixpkgsJson.sha256;
  } ) {
    overlays = [
      (self: super: {
        # Override rpm to fix build issues macOS ARM
        rpm = super.rpm.overrideAttrs (oldAttrs: rec {
          patches = oldAttrs.patches or [] ++ self.lib.optionals (self.stdenvNoCC.hostPlatform.isDarwin && self.stdenvNoCC.hostPlatform.isAarch64) [
            (self.fetchpatch {
              url = "https://github.com/rpm-software-management/rpm/commit/ad87ced3990c7e14b6b593fa411505e99412e248.patch";
              sha256 = "sha256-WYlxPGcPB5lGQmkyJ/IpGoqVfAKtMxKzlr5flTqn638=";
            })
          ];
        });
      })
    ];
  };
in pinnedNixpkgs
