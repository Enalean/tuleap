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
use wire::{JsonData, JsonResult};

mod wire;

fn main() -> Result<(), Box<dyn Error>> {
    let json: JsonData = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;

    let content = json.content;

    if content.contains("Tuleap") {
        let res = JsonResult {
            result: Some("the git object content contains the string Tuleap".to_owned()),
        };
        print!("{}", serde_json::to_string(&res).unwrap());
    } else {
        let res = JsonResult {
            result: None,
        };
        print!("{}", serde_json::to_string(&res).unwrap());
    }

    Ok(())
}
