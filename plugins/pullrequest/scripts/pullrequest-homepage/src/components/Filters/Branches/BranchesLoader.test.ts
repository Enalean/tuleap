/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { Branch } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as rest_querier from "../../../api/tuleap-rest-querier";
import { BranchesLoader } from "./BranchesLoader";

const branches: Branch[] = [{ name: "walnut" }, { name: "baobab" }, { name: "palm tree" }];

const repository_id = 2;
const noop = (): void => {
    // Do nothing
};

describe("BranchesLoader", () => {
    it("should load the branches and return a collection of LazyboxItem", async () => {
        vi.spyOn(rest_querier, "fetchRepositoryBranches").mockReturnValue(okAsync(branches));

        const loadItems = BranchesLoader(noop, repository_id);
        const items = await loadItems();

        expect(rest_querier.fetchRepositoryBranches).toHaveBeenCalledWith(repository_id);
        expect(items).toHaveLength(branches.length);
        expect(items).toStrictEqual([
            { value: branches[0], is_disabled: false },
            { value: branches[1], is_disabled: false },
            { value: branches[2], is_disabled: false },
        ]);
    });

    it("When an error occurres, Then it should execute the on_error_callback and return an empty array", async () => {
        const tuleap_api_error = Fault.fromMessage("Oops!");
        const on_error_callback = vi.fn();

        vi.spyOn(rest_querier, "fetchRepositoryBranches").mockReturnValue(
            errAsync(tuleap_api_error),
        );

        const loadItems = BranchesLoader(on_error_callback, repository_id);
        const items = await loadItems();

        expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        expect(items).toHaveLength(0);
    });
});
