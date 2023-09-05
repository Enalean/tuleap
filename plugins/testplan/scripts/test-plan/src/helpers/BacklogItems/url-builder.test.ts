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

import type { BacklogItem, TestDefinition } from "../../type";
import {
    buildCreateNewTestDefinitionLink,
    buildEditBacklogItemLink,
    buildEditTestDefinitionItemLink,
    buildGoToTestExecutionLink,
} from "./url-builder";

describe("URL Builder for backlog items", () => {
    it("does not generate an URL to go to the test exec when then the test definition is not planned", () => {
        const test_definition = {
            id: 123,
            test_status: null,
            test_execution_used_to_define_status: null,
            test_campaign_defining_status: null,
        } as TestDefinition;

        const url = buildGoToTestExecutionLink(102, 12, test_definition);

        expect(url).toBeNull();
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
            "/plugins/testmanagement/?group_id=102&milestone_id=12#!/campaigns/21/741/123",
        );
    });

    it("generates an URL to go to the edition page of a backlog item", () => {
        const backlog_item = {
            id: 123,
        } as BacklogItem;

        const url = buildEditBacklogItemLink(74, backlog_item);

        expect(url).toBe("/plugins/tracker/?aid=123&ttm_backlog_item_id=123&ttm_milestone_id=74");
    });

    it("generates an URL to go to the edition page of a test definition", () => {
        const test_def = {
            id: 321,
        } as TestDefinition;
        const backlog_item = {
            id: 741,
        } as BacklogItem;

        const url = buildEditTestDefinitionItemLink(74, test_def, backlog_item);

        expect(url).toBe("/plugins/tracker/?aid=321&ttm_backlog_item_id=741&ttm_milestone_id=74");
    });

    it("generates an URL to add a new test definition linked to a backlog item", () => {
        const backlog_item = {
            id: 123,
        } as BacklogItem;

        const url = buildCreateNewTestDefinitionLink(12, 41, backlog_item);

        expect(url).toBe(
            "/plugins/tracker/?tracker=12&func=new-artifact&ttm_backlog_item_id=123&ttm_milestone_id=41",
        );
    });
});
