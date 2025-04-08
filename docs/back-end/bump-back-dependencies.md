# Bump dependencies

At the beginning of each sprint, feature team has responsibility to
update dependencies of plugin they are working on.

## Composer
### How to detect outdated dependencies

You can run `composer outdated`, you will have a list of dependencies to
bump.

### How to do the bump

Run the following command:

``` bash
composer update --with-all-dependencies
```
## Cargo

Cargo is used for wasm modules, see [Untrusted code execution](../untrusted-code-exec.md) for details.

### How to do the bump

```bash
cd src/additional-packages/wasmtime-wrapper-lib
nix-shell
cargo update tokio # feel free to replace tokio by any dependency
cargo test
```

To test in the dev container you need to build and install the corresponding rpm:

```bash
nix-build src/additional-packages/tuleap-wasmtime-wrapper-lib.nix
cp result/tuleap-wasmtime-wrapper-lib-xxxxxx.x86_64.rpm .
make bash-web
rpm -Uvh --nodeps tuleap-wasmtime-wrapper-lib-xxxxxx.x86_64.rpm
systemctl restart tuleap-php-fpm
systemctl restart tuleap-worker*
```
