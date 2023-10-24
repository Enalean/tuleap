/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import * as tlp from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { TestExecutionResponse } from "@tuleap/plugin-docgen-docx/src";
import { getExecutions } from "./execution-querier";
import type { Campaign } from "../../../type";

jest.mock("tlp");

describe("getExecutions", () => {
    it("should retrieve the execution of the given campaign", async () => {
        const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet");

        const executions: Array<TestExecutionResponse> = [
            {
                definition: {
                    id: 123,
                },
            } as TestExecutionResponse,
            {
                definition: {
                    id: 124,
                },
            } as TestExecutionResponse,
        ];

        mockFetchSuccess(tlpRecursiveGet, {
            return_json: {
                executions,
            },
        });

        await getExecutions({ id: 101 } as Campaign);

        expect(tlpRecursiveGet).toHaveBeenCalledWith(
            "/api/v1/testmanagement_campaigns/101/testmanagement_executions",
            {
                params: { limit: 50, definition_format: "full" },
            },
        );
    });
});
