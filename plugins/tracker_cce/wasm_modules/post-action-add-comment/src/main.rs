/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
use std::io::stdin;
use std::error::Error;
use serde_json::{Value, json};

fn main() -> Result<(), Box<dyn Error>> {
    let json: Value = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;

    let user_name = &json["user"]["real_name"];
    let artifact_id = &json["id"];

    println!("{}", json!({
        "values": [],
        "comment": {
            "body": format!("Artifact #{} updated by {}", artifact_id, user_name),
            "format": "text"
        }
    }).to_string());

    Ok(())
}
