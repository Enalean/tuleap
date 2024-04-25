{ stdenvNoCC, fetchurl, nodejs }:

stdenvNoCC.mkDerivation (finalAttrs: {
  pname = "pnpm";
  version = "8.7.0";

  src = fetchurl {
    url = "https://registry.npmjs.org/${finalAttrs.pname}/-/${finalAttrs.pname}-${finalAttrs.version}.tgz";
    hash = "sha256-rvjSa8F2FsYNyxXS2zCAN2YFGicg7cw71guH+4ySVHA=";
  };

  buildInputs = [ nodejs ];

  dontBuild = true;

  installPhase = ''
    runHook preInstall

    mkdir -p $out/bin/
    mv dist $out/
    mv bin/pnpm.cjs $out/bin/pnpm

    runHook postInstall
  '';
})
