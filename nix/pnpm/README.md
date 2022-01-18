# Updating pnpm in the dev/build environment

Tuleap does not use the nixpkgs.nodePackages.pnpm to have a better control on the version we use.

To do that, we derive our own derivation of pnpm thanks to [node2nix](https://github.com/svanderburg/node2nix).

The version range usable for our pnpm derivation is defined in [node-packages.json](./node-packages.json).

After an edition of the [node-packages.json](./node-packages.json) or to update our derivation to the latest version of
the range, `./generate.sh` needs to be called.
