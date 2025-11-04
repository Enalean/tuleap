{
  pkgs,
  treefmt-nix ? (import ../pinned-treefmt-nix.nix { }),
}:

treefmt-nix.mkWrapper pkgs {
  settings.formatter.editorconfig-checker = {
    command = "${pkgs.lib.getExe pkgs.editorconfig-checker}";
    options = [
      "-disable-indent-size"
      "-disable-indentation"
      "-disable-insert-final-newline"
      "-disable-max-line-length"
      "-disable-end-of-line"
    ];
    includes = [ "*" ];
    excludes = [
      "src/common/wiki/phpwiki/*"
      "*/_fixtures/phpwiki/*"
      "src/www/scripts/*"
      "*.test.ts"
    ];
    priority = 1;
  };
  programs.gofmt.enable = true;
  programs.rustfmt.enable = true;
  programs.nixfmt = {
    enable = true;
    package = pkgs.nixfmt;
  };
  programs.oxipng.enable = true;
  settings.formatter.stylelint = {
    command = pkgs.writeShellScriptBin "tuleap-stylelint" ''
      set -eou pipefail

      pnpm run stylelint --fix --allow-empty-input -- "$@"
    '';
    includes = [
      "*.scss"
      "*.vue"
    ];
  };
  programs.actionlint.enable = true;
  programs.zizmor.enable = true;
}
