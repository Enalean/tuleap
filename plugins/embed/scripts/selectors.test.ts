/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import selectors from "./selectors";

describe("selectors", () => {
    describe("in trackers", () => {
        it("includes follow-up comments", () => {
            expect(selectors.includes(".tracker_artifact_followup_comment_body")).toBe(true);
        });
        it("includes artifact field values", () => {
            expect(selectors.includes(".textarea-value")).toBe(true);
            expect(selectors.includes(".tracker-string-field-value")).toBe(true);
        });
    });
});
