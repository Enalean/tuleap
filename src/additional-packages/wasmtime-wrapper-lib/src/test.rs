#[cfg(test)]
mod tests {
    use std::ffi::{CStr, CString};
    use std::fs::File;
    use std::io::Read;
    use std::os::raw::c_char;
    use std::ptr;

    use crate::callWasmModule;
    use crate::wire::{SuccessResponseJson, UserErrorJson};

    #[test]
    fn expected_output_normal() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/happy-path.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "Hello world !";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        assert_eq!("Hello world !", json_out.data);
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn can_read_from_preopened_dir() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/read-from-preopened-dir.wasm",
            "read_only_dir": {
                "host_path": "./test-wasm-modules/TryToReadWriteHere",
                "guest_path": "/git-dir-0000/"
            },
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();
        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        let mut file = File::open("./test-wasm-modules/TryToReadWriteHere/ReadTest.txt").unwrap();
        let mut contents = String::new();
        file.read_to_string(&mut contents).unwrap();

        assert_eq!(contents, json_out.data);
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn readonlydirectory_argument_set_but_readonlydirectoryguest_empty_error() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/read-from-preopened-dir.wasm",
            "read_only_dir": {
                "host_path": "./test-wasm-modules/TryToReadWriteHere",
                "guest_path": ""
            },
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"wasmtime-wrapper-lib was called with a non empty 'host_path' but the 'guest_path' parameter is empty"}"#,
            str_out
        );
    }

    #[test]
    fn write_to_preopened_dir_fails() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/write-to-preopened-dir.wasm",
            "read_only_dir": {
                "host_path": "./test-wasm-modules/TryToReadWriteHere",
                "guest_path": "/git-dir-7331/"
            },
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();
        println!("{}", str_out);
        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        assert_eq!(
            "Write permissions are denied: Operation not permitted (os error 63)",
            json_out.data
        );
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn config_json_ptr_is_null_error() {
        let config_json_c_world = ptr::null();

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"config_json_ptr is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn module_input_json_ptr_is_null_error() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/happy-path.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json_c_world: *const c_char = ptr::null();

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"module_input_json is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn module_not_found() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/i-do-not-exist.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"Failed to load the wasm module: failed to read input file"}"#,
            str_out
        );
    }

    #[test]
    fn module_exceeding_max_running_time_gets_killed() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/running-time.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: UserErrorJson = serde_json::from_str(str_out).unwrap();

        assert_eq!(
            "The module has exceeded the 80 ms of allowed computation time",
            json_out.error
        );

        assert!(json_out.stats.exec_time_as_seconds > 0.080000);
    }

    #[test]
    fn wasm_module_allocate_too_much_memory() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/memory-alloc-fail.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: UserErrorJson = serde_json::from_str(str_out).unwrap();

        assert_eq!(
            "wasm `unreachable` instruction executed, your module *most probably* tried to allocate more than the 4194304 bytes of memory that it is allowed to use",
            json_out.error
        );

        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn wasm_module_allocate_okay_amount_of_memory() {
        let config_json = r#"{
            "wasm_module_path": "./test-wasm-modules/target/wasm32-wasi/release/memory-alloc-success.wasm",
            "limits": {
                "max_exec_time_in_ms": 80,
                "max_memory_size_in_bytes": 4194304
            }
        }"#;
        let config_json_c_str = CString::new(config_json).unwrap();
        let config_json_c_world: *const c_char = config_json_c_str.as_ptr() as *const c_char;

        let module_input_json = "";
        let module_input_json_c_str = CString::new(module_input_json).unwrap();
        let module_input_json_c_world: *const c_char =
            module_input_json_c_str.as_ptr() as *const c_char;

        let c_out = unsafe { callWasmModule(config_json_c_world, module_input_json_c_world) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        assert_eq!("Memory Ok", json_out.data);
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 3211264);
    }
}
