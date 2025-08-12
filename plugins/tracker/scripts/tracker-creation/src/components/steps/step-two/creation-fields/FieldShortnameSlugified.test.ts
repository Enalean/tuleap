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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-tests";
import FieldShortnameSlugified from "./FieldShortnameSlugified.vue";
import type { State } from "../../../../store/type";

describe("FieldShortnameSlugified", () => {
    let mock_set_slugify_mode: jest.Mock;

    beforeEach(() => {
        mock_set_slugify_mode = jest.fn();
    });

    function getWrapper(): VueWrapper {
        return shallowMount(FieldShortnameSlugified, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        tracker_to_be_created: {
                            name: "Kanban in the trees",
                            shortname: "kanban_in_the_trees",
                        },
                    } as State,
                    mutations: {
                        setSlugifyShortnameMode: mock_set_slugify_mode,
                    },
                }),
            },
        });
    }

    it("toggles the manual mode when the user clicks on the shortname", () => {
        const wrapper = getWrapper();
        wrapper.trigger("click");

        expect(mock_set_slugify_mode).toHaveBeenCalledWith(expect.anything(), false);
    });
});
