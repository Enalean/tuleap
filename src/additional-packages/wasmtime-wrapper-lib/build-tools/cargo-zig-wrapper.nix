{ pkgs, baseArch ? "x86_64" }:

let
  zigcc = pkgs.writeShellScriptBin "zigcc"
    ''
    ${pkgs.zig}/bin/zig cc -target ${baseArch}-linux-gnu.2.34 $@
    '';
in pkgs.writeShellScriptBin "cargo-build-glibc"
  ''
    export CC="${zigcc}/bin/zigcc"
    export CARGO_TARGET_X86_64_UNKNOWN_LINUX_GNU_LINKER="${zigcc}/bin/zigcc"
    export CARGO_UNSTABLE_TARGET_APPLIES_TO_HOST=true
    export CARGO_TARGET_APPLIES_TO_HOST=false
    # https://github.com/rust-lang/cargo/pull/9753#issuecomment-1022919343
    export __CARGO_TEST_CHANNEL_OVERRIDE_DO_NOT_USE_THIS=nightly
    cargo build --target ${baseArch}-unknown-linux-gnu $@
  ''
