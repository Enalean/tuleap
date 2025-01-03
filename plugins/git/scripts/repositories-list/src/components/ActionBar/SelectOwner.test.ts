/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import SelectOwner from "./SelectOwner.vue";
import * as repo_list from "../../repository-list-presenter";
import type { RepositoryOwner } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";

describe("SelectOwner", () => {
    it("displays a dropdown with user forks", () => {
        const forks: Array<RepositoryOwner> = [
            { id: 1, display_name: "Fork A" },
            { id: 2, display_name: "Fork B" },
        ];

        jest.spyOn(repo_list, "getRepositoriesOwners").mockReturnValue(forks);

        const store_options = {
            actions: {
                changeRepositories: jest.fn(),
            },
        };

        const wrapper = shallowMount(SelectOwner, {
            global: { ...getGlobalTestOptions(store_options) },
        });

        expect(wrapper).toMatchSnapshot();
    });
});
