/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import { computedProgress } from "./progress-computation-helper";

describe("computedProgress", () => {
    it("should computed progress of upload for files", () => {
        const files = [
            { file_name: "aa", progress: 0 },
            { file_name: "bb", progress: 0 },
        ];

        let progress = 0;
        progress = computedProgress(files, "aa", 10, 100);
        expect(progress).toBe(5);
        progress = computedProgress(files, "bb", 20, 40);
        expect(progress).toBe(30);
    });
});
