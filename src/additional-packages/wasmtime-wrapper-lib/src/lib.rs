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
use std::path::Path;
use std::sync::Arc;
use std::time::Instant;
use wasmtime::*;
use wasmtime_wasi::pipe::{MemoryInputPipe, MemoryOutputPipe};
use wasmtime_wasi::preview1::WasiP1Ctx;
use wasmtime_wasi::{DirPerms, FilePerms, WasiCtxBuilder};
use wire::{
    ExecConfig, InternalErrorJson, Limitations, MountPoint, Stats, SuccessResponseJson,
    UserErrorJson,
};

mod wire;

/// # Safety
///
/// This function must be called with valid pointers (config_json_ptr, module_input_json_ptr)
#[no_mangle]
pub unsafe extern "C" fn callWasmModule(
    config_json_ptr: *const c_char,
    module_input_json_ptr: *const c_char,
) -> *mut c_char {
    if config_json_ptr.is_null() {
        return internal_error("config_json_ptr is null in callWasmModule".to_owned());
    }
    if module_input_json_ptr.is_null() {
        return internal_error("module_input_json is null in callWasmModule".to_owned());
    }
    let config_json_str = unsafe {
        CStr::from_ptr(config_json_ptr)
            .to_string_lossy()
            .into_owned()
    };
    let config_json: ExecConfig =
        serde_json::from_str(&config_json_str).expect("Failed to process config_json_str");
    let module_input_json = unsafe {
        CStr::from_ptr(module_input_json_ptr)
            .to_string_lossy()
            .into_owned()
    };

    if config_json.wasm_module_path.is_empty() {
        return internal_error("The wasm_module_path provided in config_json is empty".to_owned());
    }

    match compile_and_exec(
        config_json.wasm_module_path,
        module_input_json,
        &config_json.cache,
        &config_json.mount_points,
        &config_json.limits,
    ) {
        Ok(out_struct) => match out_struct.exec_error {
            None => success_response(out_struct.stdout, out_struct.stats),
            Some(e) => {
                if let Some(exit) = e.downcast_ref::<wasmtime_wasi::I32Exit>() {
                    if exit.0 == 0 {
                        return success_response(out_struct.stdout, out_struct.stats);
                    }
                }
                match e.downcast_ref::<Trap>() {
                        Some(&Trap::Interrupt) => user_error(format!(
                            "The module has exceeded the {} ms of allowed computation time",
                            config_json.limits.max_exec_time_in_ms
                        ),
                        out_struct.stats),
                        Some(&Trap::UnreachableCodeReached) => user_error(format!(
                            "wasm `unreachable` instruction executed, your module *most probably* tried to allocate more than the {} bytes of memory that it is allowed to use",
                            config_json.limits.max_memory_size_in_bytes
                        ),
                        out_struct.stats),
                        _ => user_error(format!("{}", e.root_cause()), out_struct.stats),
                    }
            }
        },
        Err(e) => {
            let error_message = e.to_string();
            if error_message.starts_with("memory minimum size of ")
                && error_message.ends_with(" pages exceeds memory limits")
            {
                return user_error(
                    format!("{}", e.root_cause()),
                    Stats {
                        exec_time_as_seconds: 0.0,
                        memory_in_bytes: 0,
                    },
                );
            }
            internal_error(format!("{}", e.root_cause()))
        }
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
        user_error: error_message,
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
    wasi: WasiP1Ctx,
}

struct OutputAndStats {
    stdout: String,
    exec_error: Option<Error>,
    stats: Stats,
}

fn compile_and_exec(
    wasm_module_path: String,
    module_input_json: String,
    possible_cache_config_file: &Option<String>,
    mount_points: &Vec<MountPoint>,
    limits: &Limitations,
) -> Result<OutputAndStats, anyhow::Error> {
    let stdin = MemoryInputPipe::new(module_input_json.as_bytes().to_owned());
    let stdout = MemoryOutputPipe::new(16_777_215);

    let mut config = Config::new();
    config.epoch_interruption(true);

    if let Some(cache_config_file) = possible_cache_config_file {
        config.cache_config_load(Path::new(cache_config_file))?;
        config.cranelift_opt_level(OptLevel::Speed);
    }

    let engine = Arc::new(Engine::new(&config)?);

    let mut linker = Linker::new(&engine);

    let mut ctx_builder = WasiCtxBuilder::new();
    ctx_builder.stdin(stdin);
    ctx_builder.stdout(stdout.clone());
    ctx_builder.allow_tcp(false);
    ctx_builder.allow_udp(false);
    ctx_builder.arg("./tuleap-function.wasm");

    for mount_point in mount_points {
        ctx_builder.preopened_dir(
            &mount_point.host_path,
            &mount_point.guest_path,
            DirPerms::READ,
            FilePerms::READ,
        )?;
    }

    let my_state = StoreState {
        limits: StoreLimitsBuilder::new()
            .memory_size(limits.max_memory_size_in_bytes)
            .memories(1)
            .instances(1)
            .build(),
        wasi: ctx_builder.build_p1(),
    };

    let mut store = Store::new(&engine, my_state);
    store.limiter(|state| &mut state.limits);

    wasmtime_wasi::preview1::add_to_linker_sync(&mut linker, |state: &mut StoreState| {
        &mut state.wasi
    })?;

    store.epoch_deadline_trap();
    store.set_epoch_deadline(1);

    let module = match load_module(&engine, &wasm_module_path) {
        Ok(m) => m,
        Err(e) => return Err(anyhow!("Failed to load the wasm module: {}", e)),
    };

    let instance = linker.instantiate(&mut store, &module)?;

    let max_exec_time = limits.max_exec_time_in_ms;
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

    let stdout_str = match String::from_utf8(stdout.contents().to_vec()) {
        Ok(s) => s,
        Err(e) => return Err(anyhow!(e)),
    };

    match res {
        Ok(_) => Ok(OutputAndStats {
            stdout: stdout_str,
            exec_error: None,
            stats: statistics,
        }),
        Err(e) => Ok(OutputAndStats {
            stdout: stdout_str,
            exec_error: Some(e),
            stats: statistics,
        }),
    }
}

#[cfg(test)]
#[path = "./test.rs"]
mod tests;
