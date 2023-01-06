/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
use anyhow::{anyhow, Result};
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use std::sync::Arc;
use wasi_common::pipe::{ReadPipe, WritePipe};
use wasmtime::*;
use wasmtime_wasi::sync::WasiCtxBuilder;
use wire::{InternalErrorJson, RejectionMessageJson, UserErrorJson, WasmExpectedOutputJson};

mod wire;

const MAX_EXEC_TIME_IN_MS: u64 = 80;

#[no_mangle]
pub extern "C" fn callWasmModule(
    filename_ptr: *const c_char,
    json_ptr: *const c_char,
) -> *mut c_char {
    if filename_ptr.is_null() {
        return internal_error("filename_ptr is null in callWasmModule".to_owned());
    }
    if json_ptr.is_null() {
        return internal_error("json_ptr is null in callWasmModule".to_owned());
    }
    let filename = unsafe { CStr::from_ptr(filename_ptr).to_string_lossy().into_owned() };
    let input = unsafe { CStr::from_ptr(json_ptr).to_string_lossy().into_owned() };

    match compile_and_exec(filename, input) {
        Ok(s) => {
            match serde_json::from_str::<WasmExpectedOutputJson>(&s) {
                Ok(res) => {
                    let msg = RejectionMessageJson {
                        rejection_message: res.result,
                    };
                    let c_str = CString::new(serde_json::to_string(&msg).unwrap()).unwrap();
                    return CString::into_raw(c_str);
                }
                Err(_err) => {
                    return user_error("The wasm module did not return valid JSON".to_owned());
                }
            };
        }
        Err(e) => {
            return match e.downcast_ref::<Trap>() {
                Some(&Trap::Interrupt) => user_error(format!(
                    "The module has exceeded the {} ms of allowed computation time",
                    MAX_EXEC_TIME_IN_MS
                )),
                None => internal_error(format!("{}", e.to_string())),
                _ => user_error(format!("{}", e.root_cause())),
            };
        }
    };
}

#[no_mangle]
pub extern "C" fn freeCallWasmModuleOutput(json_ptr: *mut c_char) -> () {
    unsafe {
        let _ = CString::from_raw(json_ptr);
    };
}

fn internal_error(error_message: String) -> *mut c_char {
    let err = InternalErrorJson {
        internal_error: error_message,
    };
    let c_str = CString::new(serde_json::to_string(&err).unwrap()).unwrap();
    return CString::into_raw(c_str);
}

fn user_error(error_message: String) -> *mut c_char {
    let err = UserErrorJson {
        error: error_message,
    };
    let c_str = CString::new(serde_json::to_string(&err).unwrap()).unwrap();
    return CString::into_raw(c_str);
}

fn load_module(engine: &Engine, path: &String) -> Result<Module> {
    Module::from_file(engine, path)
}

fn compile_and_exec(filename: String, input: String) -> Result<String, anyhow::Error> {
    let stdin = ReadPipe::from(input.to_owned());
    let stdout = WritePipe::new_in_memory();

    let wasi = WasiCtxBuilder::new()
        .stdin(Box::new(stdin.clone()))
        .stdout(Box::new(stdout.clone()))
        .build();

    let mut config = Config::new();
    config.epoch_interruption(true);
    let engine = Arc::new(Engine::new(&config)?);

    let mut linker = Linker::new(&engine);

    let mut store = Store::new(&engine, wasi);
    wasmtime_wasi::add_to_linker(&mut linker, |s| s)?;

    store.epoch_deadline_trap();
    store.set_epoch_deadline(1);

    let module = load_module(&engine, &filename)?;

    linker
        .module(&mut store, "", &module)
        .expect("linking the function");

    let run = linker
        .get_default(&mut store, "")
        .expect("should get the wasi runtime")
        .typed::<(), ()>(&store)
        .expect("should type the function");

    std::thread::spawn(move || {
        std::thread::sleep(std::time::Duration::from_millis(MAX_EXEC_TIME_IN_MS));
        engine.increment_epoch();
    });

    let res = run.call(&mut store, ());
    drop(store);

    match res {
        Ok(_) => {
            let raw_output: Vec<u8> = stdout
                .try_into_inner()
                .expect("stdout reference still exists")
                .into_inner();

            let str = match String::from_utf8(raw_output) {
                Ok(s) => s.to_owned(),
                Err(e) => return Err(anyhow!(e)),
            };

            Ok(str)
        }
        Err(e) => return Err(e),
    }
}

#[cfg(test)]
mod tests {
    use std::ffi::{CStr, CString};
    use std::os::raw::c_char;
    use std::ptr;

    use crate::{callWasmModule, MAX_EXEC_TIME_IN_MS};

    #[test]
    fn expected_output_rejection() {
        let wasm_module_path =
            "../pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = r#"{
            "updated_references": {
                "refs\/heads\/master": {
                    "old_value": "c8ee0a8bcf3f185a272a04d6493456b3562f5050",
                    "new_value": "e6ecbb16e4e9792fa8c5824204e1a58f2007dc31"
                }
            }
        }"#;
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"rejection_message":"the following reference refs/heads/master does not start by refs/heads/tuleap-..."}"#,
            str_out
        );
    }

    #[test]
    fn expected_output_normal() {
        let wasm_module_path =
            "../pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = r#"{
            "updated_references": {
                "refs\/heads\/tuleap-master": {
                    "old_value": "c8ee0a8bcf3f185a272a04d6493456b3562f5050",
                    "new_value": "e6ecbb16e4e9792fa8c5824204e1a58f2007dc31"
                },
                "refs\/heads\/tuleap-hello": {
                    "old_value": "0000000000000000000000000000000000000000",
                    "new_value": "0066b8447a411086ecd19210dd3f5df818056f47"
                }
            }
        }"#;
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(r#"{"rejection_message":null}"#, str_out);
    }

    #[test]
    fn filename_ptr_is_null_error() {
        let wasm_c_world = ptr::null();

        let json = r#"{
            "updated_references": {
                "refs\/heads\/tuleap-master": {
                    "old_value": "c8ee0a8bcf3f185a272a04d6493456b3562f5050",
                    "new_value": "e6ecbb16e4e9792fa8c5824204e1a58f2007dc31"
                },
                "refs\/heads\/tuleap-hello": {
                    "old_value": "0000000000000000000000000000000000000000",
                    "new_value": "0066b8447a411086ecd19210dd3f5df818056f47"
                }
            }
        }"#;
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"filename_ptr is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn json_ptr_is_null_error() {
        let wasm_module_path =
            "../pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json_c_world = ptr::null();

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"internal_error":"json_ptr is null in callWasmModule"}"#,
            str_out
        );
    }

    #[test]
    fn module_exceeding_max_running_time_gets_killed() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/running-time.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            format!(
                r#"{{"error":"The module has exceeded the {} ms of allowed computation time"}}"#,
                MAX_EXEC_TIME_IN_MS
            ),
            str_out
        );
    }

    #[test]
    fn module_returns_broken_json() {
        let wasm_module_path = "./test-wasm-modules/target/wasm32-wasi/release/wrong-json.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = "";
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);

        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"error":"The wasm module did not return valid JSON"}"#,
            str_out
        );
    }
}
