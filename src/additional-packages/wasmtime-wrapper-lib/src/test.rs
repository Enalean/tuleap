#[cfg(test)]
mod tests {
    use std::ffi::{CStr, CString};
    use std::fs::File;
    use std::io::Read;
    use std::os::raw::c_char;
    use std::ptr;

    use crate::callWasmModule;
    use crate::wire::{SuccessResponseJson, UserErrorJson};

    const MAX_EXEC_TIME: u64 = 80;
    const MAX_MEMORY_SIZE: usize = 4194304; /* 4 Mo */

    #[test]
    fn expected_output_normal() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/happy-path.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "Hello world !";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        assert_eq!("Hello world !", json_out.data);
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn can_read_from_preopened_dir() {
        let wasm_module_path =
            "./test-wasm-modules/target/wasm32-wasi/release/read-from-preopened-dir.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "./test-wasm-modules/TryToReadWriteHere";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "/git-dir-0000/";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
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
        let wasm_module_path =
            "./test-wasm-modules/target/wasm32-wasi/release/read-from-preopened-dir.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "./test-wasm-modules/TryToReadWriteHere";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"wasmtime-wrapper-lib was called with a non empty 'read_only_dir' but the 'read_only_dir_guest' parameter is empty"}"#,
            str_out
        );
    }

    #[test]
    fn write_to_preopened_dir_fails() {
        let wasm_module_path =
            "./test-wasm-modules/target/wasm32-wasi/release/write-to-preopened-dir.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "./test-wasm-modules/TryToReadWriteHere";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "/git-dir-7331/";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
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
    fn filename_ptr_is_null_error() {
        let wasm_c_world = ptr::null();

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"filename_ptr is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn json_ptr_is_null_error() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/happy-path.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json_c_world = ptr::null();

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"json_ptr is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn module_not_found() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/do-not-exist.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(r#"{"internal_error":"failed to read input file"}"#, str_out);
    }

    #[test]
    fn module_exceeding_max_running_time_gets_killed() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/running-time.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: UserErrorJson = serde_json::from_str(str_out).unwrap();

        assert_eq!(
            format!(
                "The module has exceeded the {} ms of allowed computation time",
                MAX_EXEC_TIME
            ),
            json_out.error
        );

        assert!(json_out.stats.exec_time_as_seconds > 0.080000);
    }

    #[test]
    fn wasm_module_allocate_too_much_memory() {
        let wasm_module_path =
            "./test-wasm-modules/target/wasm32-wasi/release/memory-alloc-fail.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: UserErrorJson = serde_json::from_str(str_out).unwrap();

        assert_eq!(
            format!(
                "wasm `unreachable` instruction executed, your module *most probably* tried to allocate more than the {} bytes of memory that it is allowed to use",
                MAX_MEMORY_SIZE
            ),
            json_out.error
        );

        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 1114112);
    }

    #[test]
    fn wasm_module_allocate_okay_amount_of_memory() {
        let wasm_module_path =
            "./test-wasm-modules/target/wasm32-wasi/release/memory-alloc-success.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let read_only_directory = "";
        let read_only_directory_c_str = CString::new(read_only_directory).unwrap();
        let read_only_directory_c_world: *const c_char = read_only_directory_c_str.as_ptr() as *const c_char;

        let read_only_directory_guest = "";
        let read_only_directory_guest_c_str = CString::new(read_only_directory_guest).unwrap();
        let read_only_directory_guest_c_world: *const c_char = read_only_directory_guest_c_str.as_ptr() as *const c_char;

        let c_out =
            unsafe { callWasmModule(wasm_c_world, json_c_world, read_only_directory_c_world, read_only_directory_guest_c_world, MAX_EXEC_TIME, MAX_MEMORY_SIZE) };
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        let json_out: SuccessResponseJson = serde_json::from_str(str_out).unwrap();

        assert_eq!("Memory Ok", json_out.data);
        assert!(json_out.stats.exec_time_as_seconds > 0.0);
        assert!(json_out.stats.memory_in_bytes == 3211264);
    }
}