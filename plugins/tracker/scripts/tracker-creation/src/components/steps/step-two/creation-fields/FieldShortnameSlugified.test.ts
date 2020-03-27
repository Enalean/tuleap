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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../../helpers/local-vue-for-tests";
import FieldShortnameSlugified from "./FieldShortnameSlugified.vue";

describe("FieldShortnameSlugified", () => {
    async function getWrapper(): Promise<Wrapper<FieldShortnameSlugified>> {
        return shallowMount(FieldShortnameSlugified, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        tracker_to_be_created: {
                            name: "Kanban in the trees",
                            shortname: "kanban_in_the_trees",
                        },
                    },
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });
    }

    it("toggles the manual mode when the user clicks on the shortname", async () => {
        const wrapper = await getWrapper();
        wrapper.trigger("click");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setSlugifyShortnameMode", false);
    });
});
