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

import { isDefaultTemplateSelected } from "./is-default-template-selected";
import type { State } from "./type";
import {
    FROM_JIRA,
    NONE_YET,
    TRACKER_ANOTHER_PROJECT,
    TRACKER_EMPTY,
    TRACKER_TEMPLATE,
    TRACKER_XML_FILE,
} from "./type";

describe("isDefaultTemplateSelected", () => {
    it("returns true if default template is selected", () => {
        expect(isDefaultTemplateSelected({ active_option: "default-bug" } as State)).toBe(true);
        expect(isDefaultTemplateSelected({ active_option: "default-activity" } as State)).toBe(
            true,
        );
    });
    it("returns false if default template is not selected", () => {
        [
            NONE_YET,
            TRACKER_TEMPLATE,
            TRACKER_XML_FILE,
            TRACKER_EMPTY,
            TRACKER_ANOTHER_PROJECT,
            FROM_JIRA,
        ].forEach((active_option: string) => {
            expect(isDefaultTemplateSelected({ active_option } as State)).toBe(false);
        });
    });
});
