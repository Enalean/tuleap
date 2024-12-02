{ stdenvNoCC, fetchurl, nodejs }:

stdenvNoCC.mkDerivation (finalAttrs: {
  pname = "pnpm";
  version = "8.15.9";

  src = fetchurl {
    url = "https://registry.npmjs.org/pnpm/-/pnpm-${finalAttrs.version}.tgz";
    hash = "sha256-2qJ6C1QbxjUyP/lsLe2ZVGf/n+bWn/ZwIVWKqa2dzDY=";
  };

  buildInputs = [ nodejs ];

  # Remove binary files from src, we don't need them
  preConfigure = ''
    rm -r dist/reflink.*node dist/vendor
  '';

  installPhase = ''
    runHook preInstall

    install -d $out/{bin,libexec}
    cp -R . $out/libexec/pnpm
    ln -s $out/libexec/pnpm/bin/pnpm.cjs $out/bin/pnpm

    runHook postInstall
  '';
})
