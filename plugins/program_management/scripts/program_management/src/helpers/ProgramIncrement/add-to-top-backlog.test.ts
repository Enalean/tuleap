/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import {
    moveElementFromProgramIncrementToTopBackLog,
    reorderElementInTopBacklog,
} from "./add-to-top-backlog";
import { BEFORE } from "../feature-reordering";
import type { Feature } from "../../type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("Add to top backlog", () => {
    it("Move element from program increment to top backlog without order", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveElementFromProgramIncrementToTopBackLog(101, {
            order: null,
            feature: { id: 1 } as Feature,
        });

        expect(tlpPatch).toHaveBeenCalledWith(`/api/projects/101/program_backlog`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [{ id: 1 }],
                remove: [],
                remove_from_program_increment_to_add_to_the_backlog: true,
                order: null,
            }),
        });
    });

    it("Move element from program increment to top backlog with order", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveElementFromProgramIncrementToTopBackLog(101, {
            order: {
                direction: BEFORE,
                compared_to: 14,
            },
            feature: { id: 1 } as Feature,
        });

        expect(tlpPatch).toHaveBeenCalledWith(`/api/projects/101/program_backlog`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [{ id: 1 }],
                remove: [],
                remove_from_program_increment_to_add_to_the_backlog: true,
                order: { ids: [1], direction: "before", compared_to: 14 },
            }),
        });
    });

    it("Reorder elements in top backlog", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await reorderElementInTopBacklog(101, {
            feature: { id: 415 } as Feature,
            order: {
                direction: BEFORE,
                compared_to: 56,
            },
        });

        expect(tlpPatch).toHaveBeenCalledWith(`/api/projects/101/program_backlog`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [],
                remove: [],
                order: {
                    ids: [415],
                    direction: "before",
                    compared_to: 56,
                },
            }),
        });
    });

    it("If order is null, Then error is thrown during reorder backlog", async () => {
        await expect(
            reorderElementInTopBacklog(101, {
                feature: { id: 415 } as Feature,
                order: null,
            }),
        ).rejects.toThrow("Cannot reorder element #415 because order is null");
    });
});
