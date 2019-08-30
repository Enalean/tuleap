/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

let is_console_error_or_warning;

beforeEach(() => {
    is_console_error_or_warning = false;
    const originalError = global.console.error;
    jest.spyOn(global.console, "error").mockImplementation((...args) => {
        is_console_error_or_warning = true;
        originalError(...args);
    });
    const originalWarn = global.console.warn;
    jest.spyOn(global.console, "warn").mockImplementation((...args) => {
        is_console_error_or_warning = true;
        originalWarn(...args);
    });
});

afterEach(() => {
    if (is_console_error_or_warning) {
        throw new Error("Console warnings and errors are not allowed");
    }
});
