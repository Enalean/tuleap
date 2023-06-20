use regex::Regex;
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

const RE_TULEAP: &str = r"refs/tags/[0-9]*\.[0-9]+";
const RE_LIB: &str = r"refs/tags/@tuleap/[A-Za-z]+(-?[A-Za-z]+)_[0-9]*\.[0-9]*\.[0-9]";

fn main() -> Result<(), Box<dyn Error>> {
    let json: HookData = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;

    let re_tuleap = Regex::new(RE_TULEAP).unwrap();
    let re_lib = Regex::new(RE_LIB).unwrap();

    let mut ref_name_vec = Vec::new();
    for keys in json.updated_references.keys() {
        if keys.starts_with("refs/tags") & !(re_tuleap.is_match(keys) | re_lib.is_match(keys)) {
            ref_name_vec.push(keys);
        }
    }

    let vec_len = ref_name_vec.len();
    if vec_len == 0 {
        let res = JsonResult {
            rejection_message: None,
        };
        print!("{}", serde_json::to_string(&res).unwrap());
    } else {
        let mut full_err_str = format!(
            "the following tag {} does not respect either of the imposed patern: X.Y or @tuleap/<package_name>_A.B.C",
            ref_name_vec[0]
        );

        if vec_len > 1 {
            full_err_str = format!(
                "the following tags {:?} do not respect either of the imposed patern: X.Y or @tuleap/<package_name>_A.B.C",
                ref_name_vec
            );
        }

        let res = JsonResult {
            rejection_message: Some(full_err_str),
        };
        print!("{}", serde_json::to_string(&res).unwrap());
    }
    Ok(())
}

#[cfg(test)]
mod tests {
    use regex::Regex;

    use crate::{RE_LIB, RE_TULEAP};

    #[test]
    fn re_tuleap_happy() {
        let re_tuleap = Regex::new(RE_TULEAP).unwrap();
        let input = r"refs/tags/14.9";

        assert!(re_tuleap.is_match(input));
    }

    #[test]
    fn re_tuleap_unhappy1() {
        let re_tuleap = Regex::new(RE_TULEAP).unwrap();
        let input = r"refs/tags/14";

        assert_eq!(re_tuleap.is_match(input), false);
    }

    #[test]
    fn re_lib_happy1() {
        let re_lib = Regex::new(RE_LIB).unwrap();
        let input = r"refs/tags/@tuleap/project-sidebar_2.2.1";

        assert!(re_lib.is_match(input));
    }

    #[test]
    fn re_lib_happy2() {
        let re_lib = Regex::new(RE_LIB).unwrap();
        let input = r"refs/tags/@tuleap/fictional_1.0.0";

        assert!(re_lib.is_match(input));
    }

    #[test]
    fn re_lib_unhappy1() {
        let re_lib = Regex::new(RE_LIB).unwrap();
        let input = "refs/tags/@tuleap/fictional";

        assert_eq!(re_lib.is_match(input), false);
    }

    #[test]
    fn re_lib_unhappy2() {
        let input = "refs/tags/@tuleap/project-sidebar_2.0";
        let re_lib = Regex::new(RE_LIB).unwrap();
        assert_eq!(re_lib.is_match(input), false);
    }

    #[test]
    fn re_lib_unhappy3() {
        let re_lib = Regex::new(RE_LIB).unwrap();
        let input = "refs/tags/project-sidebar_2.0";

        assert_eq!(re_lib.is_match(input), false);
    }
}
