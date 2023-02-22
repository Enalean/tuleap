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
use std::{error::Error, io::stdin};
use wire::{HookData, JsonResult};

mod wire;

fn main() -> Result<(), Box<dyn Error>> {
    let json: HookData = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;


    let mut ref_name_vec = Vec::new();
    for keys in json.updated_references.keys() {
        if !keys.starts_with("refs/heads/tuleap-") {
            ref_name_vec.push(keys);
        }
    }

    let vec_len = ref_name_vec.len();
    if vec_len == 0 {
        let res = JsonResult { result: None };
        print!("{}", serde_json::to_string(&res).unwrap());
    } else {
        let mut full_err_str = format!(
            "the following reference {} does not start by refs/heads/tuleap-...",
            ref_name_vec[0]
        )
        .to_owned();
        if vec_len > 1 {
            full_err_str = format!(
                "the following references {:?} do not start by refs/heads/tuleap-...",
                ref_name_vec
            )
            .to_owned();
        }

        let res = JsonResult {
            result: Some(full_err_str),
        };
        print!("{}", serde_json::to_string(&res).unwrap());
    }
    Ok(())
}
