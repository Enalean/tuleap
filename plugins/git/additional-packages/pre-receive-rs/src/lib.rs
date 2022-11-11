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
use std::ffi::{CStr, CString};
use std::os::raw::c_char;
use wire::JsonData;

mod wire;

#[no_mangle]
pub extern "C" fn analyze(json_ptr: *const c_char) -> *mut c_char {
    if json_ptr.is_null() {
        let c_str = CString::new("error").unwrap();
        return CString::into_raw(c_str);
    }

    let json_cstr: &CStr = unsafe { CStr::from_ptr(json_ptr) };
    let json_str: &str = json_cstr.to_str().unwrap();

    let json: JsonData = serde_json::from_str(json_str).unwrap();
    let content = json.content;

    if content.contains("Tuleap") {
        let c_str = CString::new(
            "{{'rejection_message': 'the git object content contains the string Tuleap'}}",
        )
        .unwrap();
        return CString::into_raw(c_str);
    }

    let c_str = CString::new("{{'rejection_message': null}}").unwrap();
    return CString::into_raw(c_str);
}

#[no_mangle]
pub extern "C" fn freeAnalyzeOutput(json_ptr: *mut c_char) -> () {
    unsafe {
        let _ = CString::from_raw(json_ptr);
    };
}

#[cfg(test)]
mod tests {
    use std::ffi::{CStr, CString};
    use std::os::raw::c_char;

    use crate::analyze;

    #[test]
    fn expected_output_error() {
        let json_err = r#"{"obj_type":"commit","content":"tree 33a2d661f62e986b3678ed8f074214aaeae47a53\nparent e966c3507a6b884d66aaacdaa7ea8ee643b3fa7d\nauthor Thomas PIRAS <thomas.piras@enalean.com> 1663938895 +0200\ncommitter Thomas PIRAS <thomas.piras@enalean.com> 1663938895 +0200\n\nInitialize Tuleap"}"#;
        let c_str = CString::new(json_err).expect("CString::new failed");
        let c_world: *const c_char = c_str.as_ptr() as *const c_char;

        let c_out = analyze(c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!(
            "{{'rejection_message': 'the git object content contains the string Tuleap'}}",
            str_out
        );
    }

    #[test]
    fn expected_output_normal() {
        let json_err = r#"{"obj_type":"commit","content":"tree 6f4bc3560dbb87a9fa79b0defe6f503c8626f51c\nauthor Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\ncommitter Thomas PIRAS <thomas.piras@enalean.com> 1663936581 +0200\n\nNumber One"}"#;
        let c_str = CString::new(json_err).expect("CString::new failed");
        let c_world: *const c_char = c_str.as_ptr() as *const c_char;

        let c_out = analyze(c_world);
        let cstr_out: &CStr = unsafe { CStr::from_ptr(c_out) };
        let str_out: &str = cstr_out.to_str().unwrap();

        assert_eq!("{{'rejection_message': null}}", str_out);
    }
}
