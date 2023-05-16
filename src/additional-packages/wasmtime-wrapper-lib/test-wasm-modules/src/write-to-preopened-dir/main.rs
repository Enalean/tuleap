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
use std::fs::File;
use std::path::Path;
use std::io::Error;

fn main() -> Result<(), Error> {

    let dir = Path::new("/git-dir-7331/");
    let temp_file = dir.join(".write_test");
    match File::create(&temp_file) {
        Ok(_) => {
            print!("Write permissions are granted.");
        }
        Err(err) => {
            print!("Write permissions are denied: {}", err);
        }
    }
    Ok(())
}