/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { MoveFieldsAPIRequestParams } from "./save-new-fields-order";
import { saveNewFieldsOrder } from "./save-new-fields-order";
import { isSaveNewFieldOrderFault } from "./SaveNewFieldOrderFaultBuilder";

const move_fields_request: MoveFieldsAPIRequestParams = {
    field_id: 100,
    parent_id: 90,
    next_sibling_id: 91,
};

describe("save-new-fields-order", () => {
    it("Given a MoveFieldsAPIRequestParams object, Then it should make a PATCH request to the api", async () => {
        const patch = vi.spyOn(fetch_result, "patchJSON").mockReturnValue(okAsync(null));

        const result = await saveNewFieldsOrder(move_fields_request);

        expect(result.isOk()).toBe(true);
        expect(patch).toHaveBeenCalledOnce();
        expect(patch.mock.calls[0]).toStrictEqual([
            uri`/api/v1/tracker_fields/${move_fields_request.field_id}`,
            {
                move: {
                    parent_id: move_fields_request.parent_id,
                    next_sibling_id: move_fields_request.next_sibling_id,
                },
            },
        ]);
    });

    it("When an error occurres, Then it should return a SaveNewFieldOrderFault", async () => {
        const api_fault = Fault.fromMessage("Nope");
        vi.spyOn(fetch_result, "patchJSON").mockReturnValue(errAsync(api_fault));

        const result = await saveNewFieldsOrder(move_fields_request);

        if (!result.isErr()) {
            throw new Error("Expected an error.");
        }
        expect(isSaveNewFieldOrderFault(result.error));
        expect(String(result.error)).toBe(String(api_fault));
    });
});
