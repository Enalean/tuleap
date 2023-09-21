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
use std::{
    error::Error,
    fs::{self, File},
    io::{stdin, BufRead, BufReader},
};
use wire::{HookData, JsonResult};

mod wire;

const RE_TULEAP_TAG: &str = r"^(\d+)\.(\d+)$";
const RE_LIB_TAG: &str = r"[A-Za-z]+(-?[A-Za-z]+)_([0-9]+)\.([0-9]+)\.([0-9]+)$";

fn extract_tag(line: &str) -> Option<&str> {
    let trimmed_line = line.trim();
    return trimmed_line.strip_prefix("refs/tags/");
}

fn tag_already_exists(vec: &Vec<(String, i32, i32, i32)>, target_tag: &str) -> bool {
    for (string, _, _, _) in vec {
        if string == target_tag {
            return true;
        }
    }
    false
}

fn match_tuleap_tags(tag: &str) -> Option<(i32, i32)> {
    let re_tuleap_tag = Regex::new(RE_TULEAP_TAG).unwrap();

    if let Some(captures) = re_tuleap_tag.captures(tag) {
        let major = captures
            .get(1)
            .map_or("", |m| m.as_str())
            .parse::<i32>()
            .expect("Failed to parse digits as i32");
        let minor = captures
            .get(2)
            .map_or("", |m| m.as_str())
            .parse::<i32>()
            .expect("Failed to parse digits as i32");

        return Some((major, minor));
    }

    return None;
}

fn match_subpackage_tags(tag: &str) -> Option<(i32, i32, i32)> {
    let re_lib_file = Regex::new(RE_LIB_TAG).unwrap();

    if let Some(captures) = re_lib_file.captures(tag) {
        let major = captures
            .get(2)
            .map_or("", |m| m.as_str())
            .parse::<i32>()
            .expect("Failed to parse digits as i32");
        let minor = captures
            .get(3)
            .map_or("", |m| m.as_str())
            .parse::<i32>()
            .expect("Failed to parse digits as i32");
        let patch = captures
            .get(4)
            .map_or("", |m| m.as_str())
            .parse::<i32>()
            .expect("Failed to parse digits as i32");

        return Some((major, minor, patch));
    }

    return None;
}

fn get_local_tags(
    repository_path: &String,
    additional_path: &String,
) -> Result<Vec<(String, i32, i32, i32)>, Box<dyn Error>> {
    let mut already_existing_tags: Vec<(String, i32, i32, i32)> = Vec::new();

    let search_for_tuleap_tags = additional_path.eq("/refs/tags/");
    let mut could_not_read_refs = false;
    let mut could_not_read_packed_refs = false;

    let directory_path = repository_path.to_owned() + additional_path;
    match fs::read_dir(&directory_path) {
        Ok(entries) => {
            for entry in entries {
                if let Ok(entry) = entry {
                    if let Ok(file_name) = entry.file_name().into_string() {
                        if search_for_tuleap_tags {
                            if let Some(version_tupple_tuleap) = match_tuleap_tags(&file_name) {
                                if ! tag_already_exists(&already_existing_tags, &file_name) {
                                    already_existing_tags.push((
                                        file_name,
                                        version_tupple_tuleap.0,
                                        version_tupple_tuleap.1,
                                        -1,
                                    ));
                                }
                            }
                        } else {
                            if let Some(version_tupple_lib) = match_subpackage_tags(&file_name) {
                                if ! tag_already_exists(&already_existing_tags, &file_name) {
                                    already_existing_tags.push((
                                        file_name,
                                        version_tupple_lib.0,
                                        version_tupple_lib.1,
                                        version_tupple_lib.2,
                                    ));
                                }
                            }
                        }
                    } else {
                        return Err("Error converting existing tag to string.".into());
                    }
                } else {
                    return Err("Error accessing directory entry.".into());
                }
            }
        }
        Err(_e) => {
            could_not_read_refs = true;
        }
    }

    let file_path = repository_path.to_owned() + "packed-refs";
    match File::open(file_path) {
        Ok(file) => {
            let reader = BufReader::new(file);

            for line in reader.lines() {
                if let Ok(line_content) = line {
                    if let Some(tag) = extract_tag(&line_content) {
                        if search_for_tuleap_tags {
                            if let Some(version_tupple_tuleap) = match_tuleap_tags(&tag) {
                                already_existing_tags.push((
                                    tag.to_owned(),
                                    version_tupple_tuleap.0,
                                    version_tupple_tuleap.1,
                                    -1,
                                ));
                            }
                        } else {
                            if let Some(version_tupple_lib) = match_subpackage_tags(&tag) {
                                already_existing_tags.push((
                                    tag.to_owned(),
                                    version_tupple_lib.0,
                                    version_tupple_lib.1,
                                    version_tupple_lib.2,
                                ));
                            }
                        }
                    }
                }
            }
        }
        Err(_err) => {
            could_not_read_packed_refs = true;
        }
    }

    if could_not_read_refs && could_not_read_packed_refs {
        return Err("Error: could not read the /refs directory or the packed-refs file".into());
    }

    return Ok(already_existing_tags);
}

fn main() -> Result<(), Box<dyn Error>> {
    let json: HookData = serde_json::from_reader(stdin()).map_err(|e| {
        eprintln!("ser: {e}");
        e
    })?;

    let re_tuleap_tag = Regex::new(RE_TULEAP_TAG).unwrap();
    let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();

    let mut fetch_tuleap_tags = true;
    let mut fetch_subpackage_tags = true;
    let mut tuleap_tags: Vec<(String, i32, i32, i32)> = Vec::new();
    let mut subpackage_tags: Vec<(String, i32, i32, i32)> = Vec::new();

    let repository_path = json.repository_path;

    let mut not_matching_tags: Vec<&String> = Vec::new();
    let mut wrong_version_tags: Vec<&String> = Vec::new();

    for keys in json.updated_references.keys() {
        if let Some(stripped_tag) = keys.strip_prefix("refs/tags/") {
            if !(re_tuleap_tag.is_match(stripped_tag) | re_lib_tag.is_match(stripped_tag)) {
                not_matching_tags.push(keys);
                continue;
            }

            if re_tuleap_tag.is_match(stripped_tag) {
                if fetch_tuleap_tags {
                    tuleap_tags = get_local_tags(&repository_path, &"/refs/tags/".to_owned())?;
                    fetch_tuleap_tags = false;
                }

                let version: Vec<i32> = keys
                    .rsplitn(2, '/')
                    .next()
                    .expect("Invalid input format")
                    .split('.')
                    .map(|part| part.parse().expect("Invalid number"))
                    .collect();

                for tags in &tuleap_tags {
                    if !(version[0] > tags.1) && !(version[0] == tags.1 && version[1] > tags.2) {
                        wrong_version_tags.push(keys);
                        break;
                    }
                }
            } else if re_lib_tag.is_match(stripped_tag) {
                if fetch_subpackage_tags {
                    subpackage_tags =
                        get_local_tags(&repository_path, &"/refs/tags/@tuleap/".to_owned())?;
                    fetch_subpackage_tags = false;
                }

                let version: Vec<i32> = keys
                    .rsplitn(2, '_')
                    .next()
                    .expect("Invalid input format")
                    .split('.')
                    .map(|part| part.parse().expect("Invalid number"))
                    .collect();

                for tags in &subpackage_tags {
                    if !(version[0] > tags.1)
                        && !(version[0] == tags.1 && version[1] > tags.2)
                        && !(version[0] == tags.1 && version[1] == tags.2 && version[2] > tags.3)
                    {
                        wrong_version_tags.push(keys);
                        break;
                    }
                }
            }
        }
    }

    let not_matching_tags_len = not_matching_tags.len();
    let wrong_version_tags_len = wrong_version_tags.len();

    let mut res = JsonResult {
        rejection_message: None,
    };

    if not_matching_tags_len > 0 && wrong_version_tags_len == 0 {
        let full_err_str = format!(
            "the following submited tag(s) '{:?}' do not respect either of the imposed patern: X.Y or @tuleap/<package_name>_A.B.C",
                not_matching_tags
        );

        res = JsonResult {
            rejection_message: Some(full_err_str),
        };
    } else if not_matching_tags_len == 0 && wrong_version_tags_len > 0 {
        let full_err_str = format!(
            "the following tag(s) {:?} already exist with a superior or equal version (version must be incremental)",
                wrong_version_tags
        );

        res = JsonResult {
            rejection_message: Some(full_err_str),
        };
    } else if not_matching_tags_len > 0 && wrong_version_tags_len > 0 {
        let full_err_str = format!(
            r#"There is two errors:
            1) the following submited tag(s) {:?} do not respect either of the imposed patern: X.Y or @tuleap/<package_name>_A.B.C
            2) the following tags {:?} already exist with a superior or equal version (version must be incremental)
            "#,
            not_matching_tags, wrong_version_tags
        );

        res = JsonResult {
            rejection_message: Some(full_err_str),
        };
    }

    print!("{}", serde_json::to_string(&res).unwrap());
    Ok(())
}

#[cfg(test)]
mod tests {
    use regex::Regex;

    use crate::{RE_LIB_TAG, RE_TULEAP_TAG};

    #[test]
    fn re_tuleap_match_x_y() {
        let re_tuleap_tag = Regex::new(RE_TULEAP_TAG).unwrap();
        let input = r"14.9";

        assert!(re_tuleap_tag.is_match(input));
    }

    #[test]
    fn re_tuleap_no_match_x() {
        let re_tuleap_tag = Regex::new(RE_TULEAP_TAG).unwrap();
        let input = r"14";

        assert_eq!(re_tuleap_tag.is_match(input), false);
    }

    #[test]
    fn re_tuleap_no_match_x_y_z() {
        let re_tuleap_tag = Regex::new(RE_TULEAP_TAG).unwrap();
        let input = r"14.9.1";

        assert_eq!(re_tuleap_tag.is_match(input), false);
    }

    #[test]
    fn re_lib_happy_complex_project_name() {
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        let input = r"@tuleap/project-sidebar_2.2.1";

        assert!(re_lib_tag.is_match(input));
    }

    #[test]
    fn re_lib_happy_normal_project_name() {
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        let input = r"@tuleap/fictional_1.0.0";

        assert!(re_lib_tag.is_match(input));
    }

    #[test]
    fn re_lib_no_match_no_version() {
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        let input = "@tuleap/fictional";

        assert_eq!(re_lib_tag.is_match(input), false);
    }

    #[test]
    fn re_lib_no_match_x_y() {
        let input = "@tuleap/project-sidebar_2.0";
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        assert_eq!(re_lib_tag.is_match(input), false);
    }

    #[test]
    fn re_lib_no_match_missing_prefix() {
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        let input = "project-sidebar_2.0";

        assert_eq!(re_lib_tag.is_match(input), false);
    }

    #[test]
    fn re_lib_no_match_w_x_y_z() {
        let re_lib_tag = Regex::new(RE_LIB_TAG).unwrap();
        let input = "@tuleap/project-sidebar_2.0.0.0";

        assert_eq!(re_lib_tag.is_match(input), false);
    }
}
