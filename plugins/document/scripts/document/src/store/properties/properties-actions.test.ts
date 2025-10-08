/*
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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { loadProjectProperties } from "./properties-actions";
import * as properties_rest_querier from "../../api/properties-rest-querier";
import type { ActionContext } from "vuex";
import type { RootState } from "../../type";

vi.mock("../../helpers/emitter");

describe("Properties actions", () => {
    let context: ActionContext<RootState, RootState>, getProjectProperties: MockInstance;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
            dispatch: vi.fn(),
        } as unknown as ActionContext<RootState, RootState>;

        getProjectProperties = vi.spyOn(properties_rest_querier, "getProjectProperties");

        vi.clearAllMocks();
    });

    it(`load project properties definition`, async () => {
        const properties = [
            {
                short_name: "text",
                type: "text",
            },
        ];

        getProjectProperties.mockReturnValue(properties);

        await loadProjectProperties(context, 102);

        expect(context.commit).toHaveBeenCalledWith("saveProjectProperties", properties);
    });

    it("Handle error when properties project load fails", async () => {
        mockFetchError(getProjectProperties, {
            status: 400,
            error_json: {
                error: {
                    message: "Something bad happens",
                },
            },
        });

        await loadProjectProperties(context, 102);

        expect(context.dispatch).toHaveBeenCalled();
    });
});
