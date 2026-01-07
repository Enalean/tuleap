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
    ];
    includes = [ "*" ];
    excludes = [
      "src/common/wiki/phpwiki/*"
      "*/_fixtures/*"
      "*/fixtures/*"
      "src/www/scripts/*"
      "*.test.ts"
      "*.graphml"
    ];
    priority = 1;
  };
  programs.gofmt.enable = true;
  programs.rustfmt.enable = true;
  programs.nixfmt.enable = true;
  programs.nixf-diagnose.enable = true;
  settings.formatter.nixf-diagnose.options = [
    "--ignore=sema-primop-overridden"
  ];
  programs.oxipng.enable = true;
  settings.formatter.eslint = {
    command = pkgs.writeShellScriptBin "tuleap-eslint" ''
      set -eou pipefail

      pnpm run eslint --fix -- "$@"
    '';
    includes = [
      "*.js"
      "*.mjs"
      "*.ts"
      "*.mts"
      "*.vue"
    ];
  };
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
  settings.formatter.phpcs = {
    command = pkgs.writeShellScriptBin "tuleap-phpcs" ''
      set -eou pipefail

      php -d short_open_tag=OFF -d memory_limit=512M ./src/vendor/bin/phpcbf --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -q --runtime-set php_version 80400 -- "$@" 2> /dev/null ||
        php -d short_open_tag=OFF -d memory_limit=512M ./src/vendor/bin/phpcs --encoding=utf-8 --standard=tests/phpcs/tuleap-ruleset-minimal.xml -q --runtime-set php_version 80400 -- "$@"
    '';
    includes = [
      "*.php"
      "*.phpstub"
    ];
  };
  programs.dockerfmt.enable = true;
}
