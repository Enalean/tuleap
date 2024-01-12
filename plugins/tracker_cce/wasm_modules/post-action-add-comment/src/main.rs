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
use serde_json::{json, Value};
use std::error::Error;
use std::io::stdin;

fn is_odd(number: i64) -> bool {
    number & 1 == 1
}

fn main() -> Result<(), Box<dyn Error>> {
    let json: Value = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;

    let values = &json["current"]["values"].as_array();

    let field_a_value = values
        .and_then(|fields| {
            fields
                .into_iter()
                .find(|&field| field["label"] == "field_a")
        })
        .and_then(|field| field["value"].as_i64());
    let field_b_value = values
        .and_then(|fields| {
            fields
                .into_iter()
                .find(|&field| field["label"] == "field_b")
        })
        .and_then(|field| field["value"].as_i64());
    let field_sum = values.and_then(|fields| {
        fields
            .into_iter()
            .find(|&field| field["label"] == "field_sum")
    });

    if field_a_value.is_none() {
        return Err("Cannot find field_a")?;
    } else if field_b_value.is_none() {
        return Err("Cannot find field_b")?;
    } else if field_sum.is_none() {
        return Err("Cannot find field_sum")?;
    } else {
        let field_sum_id = field_sum.unwrap()["field_id"].as_i64().unwrap_or(0);
        let value_a = field_a_value.unwrap();
        let value_b = field_b_value.unwrap();

        let sum = value_a + value_b;

        if is_odd(sum) {
            println!("{}", json!({
                "values": [{
                    "field_id": field_sum_id,
                    "value": "odd"
                }],
                "comment": {
                    "body": format!("Sum of field_a and field_b is odd -> {value_a} + {value_b} = {sum}"),
                    "format": "text"
                }
            }).to_string());
        } else {
            println!("{}", json!({
                "values": [{
                    "field_id": field_sum_id,
                    "value": "even"
                }],
                "comment": {
                    "body": format!("Sum of field_a and field_b is even -> {value_a} + {value_b} = {sum}"),
                    "format": "text"
                }
            }).to_string());
        }
    }

    Ok(())
}

#[cfg(test)]
mod tests {
    use crate::is_odd;

    #[test]
    fn test_is_odd() {
        assert_eq!(true, is_odd(1));
        assert_eq!(false, is_odd(2));
    }
}
