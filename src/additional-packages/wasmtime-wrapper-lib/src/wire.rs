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
use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize)]
pub struct ExecConfig {
    pub wasm_module_path: String,
    pub mount_points: Vec<MountPoint>,
    pub cache: Option<String>,
    pub limits: Limitations,
}

#[derive(Serialize, Deserialize)]
pub struct MountPoint {
    pub host_path: String,
    pub guest_path: String,
}

#[derive(Serialize, Deserialize)]
pub struct Limitations {
    pub max_exec_time_in_ms: u64,
    pub max_memory_size_in_bytes: usize,
}

#[derive(Serialize, Deserialize)]
pub struct SuccessResponseJson {
    pub data: String,
    pub stats: Stats,
}

#[derive(Serialize, Debug, Deserialize)]
pub struct Stats {
    pub exec_time_as_seconds: f64,
    pub memory_in_bytes: usize,
}

#[derive(Serialize)]
pub struct InternalErrorJson {
    pub internal_error: String,
}

#[derive(Serialize, Deserialize)]
pub struct UserErrorJson {
    pub error: String,
    pub stats: Stats,
}
