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

import { beforeEach, afterEach, vi } from "vitest";

let is_console_error_or_warning = false;

beforeEach(() => {
    is_console_error_or_warning = false;
    const originalError = global.console.error;
    vi.spyOn(global.console, "error").mockImplementation((...args: unknown[]) => {
        is_console_error_or_warning = true;
        originalError(...args);
    });
    const originalWarn = global.console.warn;
    vi.spyOn(global.console, "warn").mockImplementation((...args: unknown[]) => {
        is_console_error_or_warning = true;
        originalWarn(...args);
    });
});

afterEach(() => {
    if (is_console_error_or_warning) {
        throw new Error("Console warnings and errors are not allowed");
    }
});
