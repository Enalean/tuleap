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
use anyhow::Result;
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use wasi_common::pipe::{ReadPipe, WritePipe};
use wasmtime::*;
use wasmtime_wasi::sync::WasiCtxBuilder;
use wire::{InternalErrorJson, RejectionMessageJson, UserErrorJson, WasmExpectedOutputJson};

mod wire;

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
                Err(err) => {
                    println!("{}", err);
                    return user_error("The wasm module did not return valid JSON".to_owned());
                }
            };
        }
        Err(_e) => {
            return internal_error("Unexpected error".to_owned());
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

    let engine = Engine::default();

    let mut linker = Linker::new(&engine);
    wasmtime_wasi::add_to_linker(&mut linker, |s| s)?;

    let mut store = Store::new(&engine, wasi);

    let module = load_module(&engine, &filename)?;

    linker
        .module(&mut store, "", &module)
        .expect("linking the function");
    linker
        .get_default(&mut store, "")
        .expect("should get the wasi runtime")
        .typed::<(), (), _>(&store)
        .expect("should type the function")
        .call(&mut store, ())
        .expect("should call the function");

    drop(store);

    let contents: Vec<u8> = stdout
        .try_into_inner()
        .map_err(|_err| anyhow::Error::msg("sole remaining reference"))?
        .into_inner();
    let str: String = String::from_utf8(contents).unwrap();

    Ok(str.to_string())
}

#[cfg(test)]
mod tests {
    use std::ffi::{CStr, CString};
    use std::os::raw::c_char;
    use std::ptr;

    use crate::callWasmModule;

    #[test]
    fn expected_output_rejection() {
        let wasm_module_path =
            "../pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = r#"{"obj_type":"commit","content":"tree 33a2d661f62e986b3678ed8f074214aaeae47a53\nparent e966c3507a6b884d66aaacdaa7ea8ee643b3fa7d\nauthor Thomas PIRAS <thomas.piras@enalean.com> 1663938895 +0200\ncommitter Thomas PIRAS <thomas.piras@enalean.com> 1663938895 +0200\n\nInitialize Tuleap"}"#;
        let json_c_str = CString::new(json).unwrap();
        let json_c_world: *const c_char = json_c_str.as_ptr() as *const c_char;

        let c_out = callWasmModule(wasm_c_world, json_c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            r#"{"rejection_message":"the git object content contains the string Tuleap"}"#,
            str_out
        );
    }

    #[test]
    fn expected_output_normal() {
        let wasm_module_path =
            "../pre-receive-hook-example/target/wasm32-wasi/release/pre-receive-hook-example.wasm";
        let wasm_c_str = CString::new(wasm_module_path).unwrap();
        let wasm_c_world: *const c_char = wasm_c_str.as_ptr() as *const c_char;

        let json = r#"{"obj_type":"commit","content":"tree 6f4bc3560dbb87a9fa79b0defe6f503c8626f51c\nauthor Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\ncommitter Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\n\nNumber One"}"#;
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

        let json = r#"{"obj_type":"commit","content":"tree 6f4bc3560dbb87a9fa79b0defe6f503c8626f51c\nauthor Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\ncommitter Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\n\nNumber One"}"#;
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
}
