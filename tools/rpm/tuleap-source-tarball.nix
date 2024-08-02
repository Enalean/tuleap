{ pkgs ? (import ../utils/nix/pinned-nixpkgs.nix) {}, gitignoreNix ? (import ../utils/nix/pinned-hercules-gitignore.nix { inherit pkgs; } ) }:

let
  tuleapVersion = pkgs.lib.strings.fileContents ../../VERSION;
  sourceFilter = src:
    let
      # IMPORTANT: use a let binding like this to memoize info about the git directories.
      srcIgnored = gitignoreNix.gitignoreFilterWith {
        basePath = src;
        extraRules = ''
          *.nix
          composer.json
          composer.lock
          package.json
          pnpm-lock.yaml
          tsconfig.json
          vite.config.ts
          webpack.common.js
          webpack.dev.js
          webpack.prod.js
          webpack.common.mjs
          webpack.dev.mjs
          webpack.prod.mjs
          Makefile
          jest.config.js
          build-manifest.json
          !ENTERPRISE_BUILD
          !*.mo
          *.po
          !src/vendor/
          !src/tuleap-cfg/vendor/
          !src/themes/*/vendor/
          !plugins/*/vendor/
          adr/
          src/additional-packages
          plugins/*/additional-packages
          tests/
          plugins/*/tests
          /lib/
          !src/scripts/*/frontend-assets/
          /src/scripts/tlp-doc/
          /plugins/*/scripts/lib/
          !plugins/*/backend-assets
          !plugins/*/*/backend-assets
          !plugins/*/frontend-assets
          !plugins/*/scripts/*/frontend-assets
          !plugins/*/resources/templates/*
          plugins/*/themes
          tools/publish_js_library/
          tools/rpm/
          !tools/utils/setup_templates/*/*_template.xml
          !tools/utils/setup_templates/*/*_generated.xml
          tools/utils/gerrit_setup
          tools/utils/githooks
          tools/utils/jetbrains
          tools/utils/nix
          tools/utils/signing-keys
          tools/utils/version_numbers
          tools/utils/generate-mo.sh
          tools/utils/generate-smarty-mo.php
          tools/utils/generate-po.php
          tools/utils/TuleapDev/
          tools/utils/xml-templates-to-php/
          tools/utils/scripts/
          tools/utils/publish_js_libraries/
        '';
      };
      # Clean files src/scripts/<name>/(not "frontend_assets" directory)
      cleanCoreScriptsSubAppCode = path: type:
              ! ( ( ( baseNameOf path ) != "frontend-assets" ) &&
                  ( ( baseNameOf ( dirOf ( dirOf path ) ) ) == "scripts" ) &&
                  ( ( baseNameOf ( dirOf ( dirOf ( dirOf path ) ) ) ) == "src" )
                );
      # Clean files in plugins/<name>/scripts/
      cleanPluginScriptsFiles = path: type:
        ! ( ( type != "directory" ) &&
            ( ( baseNameOf ( dirOf path ) ) == "scripts" ) &&
            ( ( baseNameOf ( dirOf ( dirOf ( dirOf path ) ) ) ) == "plugins" )
          );
      # Clean plugins/<name>/scripts/<sub_app_name>/(not "frontend_assets" directory)
      cleanPluginScriptsSubAppCode = path: type:
        ! ( ( ( baseNameOf path ) != "frontend-assets" ) &&
            ( ( baseNameOf ( dirOf ( dirOf path ) ) ) == "scripts" ) &&
            ( ( baseNameOf ( dirOf ( dirOf ( dirOf ( dirOf path ) ) ) ) ) == "plugins" )
          );
    in
      path: type:
        srcIgnored path type && cleanCoreScriptsSubAppCode path type && cleanPluginScriptsSubAppCode path type && cleanPluginScriptsFiles path type;
  name = "tuleap-${tuleapVersion}-tarball";
  rootFolderSrc = ../..;
in pkgs.stdenv.mkDerivation {
  inherit name;
  src = pkgs.lib.cleanSourceWith {
    filter = sourceFilter rootFolderSrc;
    src = rootFolderSrc;
    name = name + "-source";
  };

  dontConfigure = true;
  dontBuild = true;
  dontPatch = true;
  dontFixup = true;

  doCheck = true;
  checkPhase = ''
    runHook preCheck

    find src/scripts/ -empty -type d -print -exec sh -c 'echo "Empty folders found, aborting" && false' {} +

    runHook postCheck
  '';

  installPhase = ''
    runHook preInstall

    mkdir $out/
    pushd $src/

    tar cf $out/tuleap-src.tar *

    popd

    runHook postInstall
  '';
}
