/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { TestDefinition } from "../../type";
import { buildGoToTestExecutionLink } from "./url-builder";

describe("URL Builder for backlog items", () => {
    it("does not generate an URL to go to the test exec when then the test definition is not planned", () => {
        const test_definition = {
            id: 123,
            test_status: null,
            test_execution_used_to_define_status: null,
            test_campaign_defining_status: null,
        } as TestDefinition;

        const url = buildGoToTestExecutionLink(102, 12, test_definition);

        expect(url).toBe(null);
    });

    it("generates an URL to go to the test exec for a planned test definition", () => {
        const test_definition = {
            id: 123,
            test_status: "passed",
            test_execution_used_to_define_status: {
                id: 741,
            },
            test_campaign_defining_status: {
                id: 21,
            },
        } as TestDefinition;

        const url = buildGoToTestExecutionLink(102, 12, test_definition);

        expect(url).toBe(
            "/plugins/testmanagement/?group_id=102&milestone_id=12#!/campaigns/21/741/123"
        );
    });
});
