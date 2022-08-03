{ pkgs }:

[
    # Override to be removed the next time our nixpkgs pin is updated
    (pkgs.docker-compose_2.overrideAttrs(oldAttrs: rec {
      installPhase = ''
        runHook preInstall

        ${oldAttrs.installPhase}

        mkdir -p $out/bin
        ln -s $out/libexec/docker/cli-plugins/docker-compose $out/bin/docker-compose
        runHook postInstall
      '';
    }))
]
