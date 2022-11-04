extern crate cbindgen;

use cbindgen::Config;

fn main() {
    let crate_dir = std::env::var("CARGO_MANIFEST_DIR").unwrap();
    let config = Config::from_file("cbindgen.toml").unwrap();

    cbindgen::generate_with_config(&crate_dir, config)
        .expect("Unable to generate C bindings.")
        .write_to_file("prereceiveanalyze.h");
}
