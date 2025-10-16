{
  pkgs,
  treefmt-nix ? (import ../pinned-treefmt-nix.nix { }),
}:

treefmt-nix.mkWrapper pkgs {
  programs.gofmt.enable = true;
  programs.rustfmt.enable = true;
  programs.nixfmt = {
    enable = true;
    package = pkgs.nixfmt;
  };
  programs.oxipng.enable = true;
}
