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
use serde::{Deserialize, Serialize};
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use std::sync::Arc;
use std::time::Instant;
use wasi_common::pipe::{ReadPipe, WritePipe};
use wasmtime::*;
use wasmtime_wasi::sync::WasiCtxBuilder;
use wasmtime_wasi::WasiCtx;
use wire::{InternalErrorJson, SuccessResponseJson, UserErrorJson};

mod wire;
mod preview1;

/// # Safety
///
/// This function must be called with valid pointers (filename_ptr, json_ptr and read_only_dir_path_ptr)
#[no_mangle]
pub unsafe extern "C" fn callWasmModule(
    filename_ptr: *const c_char,
    json_ptr: *const c_char,
    read_only_dir_path_ptr: *const c_char,
    read_only_dir_guest_path_ptr: *const c_char,
    max_exec_time_in_ms: u64,
    max_memory_size_in_bytes: usize,
) -> *mut c_char {
    if filename_ptr.is_null() {
        return internal_error("filename_ptr is null in callWasmModule".to_owned());
    }
    if json_ptr.is_null() {
        return internal_error("json_ptr is null in callWasmModule".to_owned());
    }
    if read_only_dir_path_ptr.is_null() {
        return internal_error("read_only_dir_path_ptr is null in callWasmModule".to_owned());
    }
    if read_only_dir_guest_path_ptr.is_null() {
        return internal_error("read_only_dir_guest_path_ptr is null in callWasmModule".to_owned());
    }
    let filename = unsafe { CStr::from_ptr(filename_ptr).to_string_lossy().into_owned() };
    let input = unsafe { CStr::from_ptr(json_ptr).to_string_lossy().into_owned() };
    let read_only_dir_str = unsafe { CStr::from_ptr(read_only_dir_path_ptr).to_string_lossy().into_owned() };
    let read_only_dir_guest_str = unsafe { CStr::from_ptr(read_only_dir_guest_path_ptr).to_string_lossy().into_owned() };
    let limits = Limitations {
        max_exec_time: max_exec_time_in_ms,
        max_memory: max_memory_size_in_bytes,
    };
    match compile_and_exec(filename, input, read_only_dir_str, read_only_dir_guest_str, &limits) {
        Ok(out_struct) => match out_struct.result {
            Ok(output_message) => success_response(output_message, out_struct.stats),
            Err(e) => {
                return match e.downcast_ref::<Trap>() {
                        Some(&Trap::Interrupt) => user_error(format!(
                            "The module has exceeded the {} ms of allowed computation time",
                            limits.max_exec_time
                        ),
                        out_struct.stats),
                        Some(&Trap::UnreachableCodeReached) => user_error(format!(
                            "wasm `unreachable` instruction executed, your module *most probably* tried to allocate more than the {} bytes of memory that it is allowed to use",
                            limits.max_memory
                        ),
                        out_struct.stats),
                        _ => user_error(format!("{}", e.root_cause()), out_struct.stats),
                    };
            }
        },
        Err(e) => internal_error(format!("{}", e)),
    }
}

/// # Safety
///
/// This function must only be called with a valid pointer (json_ptr)
#[no_mangle]
pub unsafe extern "C" fn freeCallWasmModuleOutput(json_ptr: *mut c_char) {
    unsafe {
        let _ = CString::from_raw(json_ptr);
    };
}

fn success_response(wasm_stdout: String, statistics: Stats) -> *mut c_char {
    let response = SuccessResponseJson {
        data: wasm_stdout,
        stats: statistics,
    };
    let c_str = CString::new(serde_json::to_string(&response).unwrap()).unwrap();
    CString::into_raw(c_str)
}

fn internal_error(error_message: String) -> *mut c_char {
    let err = InternalErrorJson {
        internal_error: error_message,
    };
    let c_str = CString::new(serde_json::to_string(&err).unwrap()).unwrap();
    CString::into_raw(c_str)
}

fn user_error(error_message: String, statistics: Stats) -> *mut c_char {
    let err = UserErrorJson {
        error: error_message,
        stats: statistics,
    };
    let c_str = CString::new(serde_json::to_string(&err).unwrap()).unwrap();
    CString::into_raw(c_str)
}

fn load_module(engine: &Engine, path: &String) -> Result<Module> {
    Module::from_file(engine, path)
}

struct StoreState {
    limits: StoreLimits,
    wasi: WasiCtx,
}

struct Limitations {
    max_exec_time: u64,
    max_memory: usize,
}

#[derive(Serialize, Debug, Deserialize)]
pub struct Stats {
    pub exec_time_as_seconds: f64,
    pub memory_in_bytes: usize,
}

struct OutputAndStats {
    result: Result<String, Error>,
    stats: Stats,
}

fn compile_and_exec(
    filename: String,
    input: String,
    read_only_dir: String,
    read_only_dir_guest: String,
    limits: &Limitations,
) -> Result<OutputAndStats, anyhow::Error> {
    let stdin = ReadPipe::from(input);
    let stdout = WritePipe::new_in_memory();

    let my_state = StoreState {
        limits: StoreLimitsBuilder::new()
            .memory_size(limits.max_memory)
            .memories(1)
            .instances(1)
            .build(),
        wasi: WasiCtxBuilder::new()
            .stdin(Box::new(stdin))
            .stdout(Box::new(stdout.clone()))
            .build(),
    };

    let mut config = Config::new();
    config.epoch_interruption(true);
    let engine = Arc::new(Engine::new(&config)?);

    let mut linker = Linker::new(&engine);

    let mut store = Store::new(&engine, my_state);
    store.limiter(|state| &mut state.limits);

    if ! read_only_dir.is_empty() {
        let cap_std_dir =
            cap_std::fs::Dir::open_ambient_dir(read_only_dir, cap_std::ambient_authority())?;
        let dir_host =
            Box::new(wasmtime_wasi::dir::Dir::from_cap_std(cap_std_dir));
        let read_only_dir_host = Box::new(preview1::ReadOnlyDir(dir_host));

        if ! read_only_dir_guest.is_empty() {
            store.data_mut().wasi.push_preopened_dir(read_only_dir_host, read_only_dir_guest)?;
        } else {
            return Err(anyhow!("wasmtime-wrapper-lib was called with a non empty 'read_only_dir' but the 'read_only_dir_guest' parameter is empty"))
        }
    }

    wasmtime_wasi::add_to_linker(&mut linker, |state: &mut StoreState| &mut state.wasi)?;

    store.epoch_deadline_trap();
    store.set_epoch_deadline(1);

    let module = load_module(&engine, &filename)?;

    let instance = linker
        .instantiate(&mut store, &module)
        .expect("Failed to instantiate imported instance");

    let max_exec_time = limits.max_exec_time;
    std::thread::spawn(move || {
        std::thread::sleep(std::time::Duration::from_millis(max_exec_time));
        engine.increment_epoch();
    });

    let now = Instant::now();
    let res = instance
        .get_typed_func::<(), ()>(&mut store, "_start")?
        .call(&mut store, ());
    let exec_time = now.elapsed().as_secs_f64();

    let memory = instance
        .get_memory(&mut store, "memory")
        .ok_or(anyhow::format_err!("failed to find `memory` export"))?;

    let statistics = Stats {
        exec_time_as_seconds: exec_time,
        memory_in_bytes: memory.data_size(&store),
    };

    drop(store);

    match res {
        Ok(_) => {
            let raw_output: Vec<u8> = stdout
                .try_into_inner()
                .expect("stdout reference still exists")
                .into_inner();

            let str = match String::from_utf8(raw_output) {
                Ok(s) => s,
                Err(e) => return Err(anyhow!(e)),
            };

            let output = OutputAndStats {
                result: Ok(str),
                stats: statistics,
            };

            Ok(output)
        }
        Err(e) => {
            let output = OutputAndStats {
                result: Err(e),
                stats: statistics,
            };

            Ok(output)
        }
    }
}

#[cfg(test)]
#[path = "./test.rs"]
mod tests;