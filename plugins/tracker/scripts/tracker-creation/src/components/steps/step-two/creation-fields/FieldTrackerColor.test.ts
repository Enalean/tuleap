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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { State } from "../../../../store/type";
import FieldTrackerColor from "./FieldTrackerColor.vue";
import { createTrackerCreationLocalVue } from "../../../../helpers/local-vue-for-tests";

const mocked_jquery = {
    select2: jest.fn(),
    on: jest.fn(),
    off: jest.fn(),
    val: jest.fn(),
    trigger: jest.fn(),
};

jest.mock("jquery", () => {
    return (): Record<string, jest.SpyInstance> => mocked_jquery;
});

describe("FieldTrackerColor", () => {
    async function getWrapper(tracker_to_be_created = {}): Promise<Wrapper<FieldTrackerColor>> {
        return shallowMount(FieldTrackerColor, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        tracker_to_be_created,
                        color_picker_data: [{ id: "peggy-pink", text: "" }],
                        default_tracker_color: "inca-silver",
                    } as State,
                }),
            },
            localVue: await createTrackerCreationLocalVue(),
        });
    }

    beforeEach(() => {
        mocked_jquery.on.mockImplementation(() => mocked_jquery);
        mocked_jquery.off.mockImplementation(() => mocked_jquery);
        mocked_jquery.select2.mockImplementation(() => mocked_jquery);
        mocked_jquery.trigger.mockImplementation(() => mocked_jquery);
    });

    it("pre-selects the color", async () => {
        await getWrapper({
            name: "Bugs",
            shortname: "bugs",
            color: "peggy-pink",
        });

        expect(mocked_jquery.val).toHaveBeenCalledWith("peggy-pink");
    });

    it("pre-selects inca-silver if the color of the tracker to be created is not valid", async () => {
        await getWrapper({
            name: "Bugs",
            shortname: "bugs",
            color: "jône et rôse",
        });

        expect(mocked_jquery.val).toHaveBeenCalledWith("inca-silver");
    });

    it("Destroys the select2", async () => {
        const wrapper = await getWrapper();
        wrapper.destroy();

        expect(mocked_jquery.select2).toHaveBeenCalledWith("destroy");
    });
});
