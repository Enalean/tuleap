{ fetchgit, git-lfs }:

# fetchgit with fetchLFS=true is broken upstream, so we override part of the fetchgit derivation to fix it
{ url, rev, sha256 }:
(fetchgit {
  inherit url rev sha256;
}).overrideAttrs (oldAttrs: {
  nativeBuildInputs = oldAttrs.nativeBuildInputs or [ ] ++ [ git-lfs ];
  postHook = ''
    export HOME=$PWD
    git lfs install
  '';
})
