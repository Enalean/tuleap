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
import type { State } from "../../../../store/type";
import FieldTrackerColor from "./FieldTrackerColor.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-tests";

function noop(): void {
    //Do nothing
}

interface MockedJQuery {
    select2(): void;
    off(): this;
    val(): string;
    trigger(): void;
}

const mocked_jquery: MockedJQuery = {
    select2: noop,
    off() {
        return this;
    },
    val: () => "",
    trigger: noop,
};

jest.mock("jquery", () => {
    return (): MockedJQuery => mocked_jquery;
});

describe("FieldTrackerColor", () => {
    function getWrapper(tracker_to_be_created = {}): VueWrapper {
        return shallowMount(FieldTrackerColor, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        tracker_to_be_created,
                        color_picker_data: [{ id: "peggy-pink", text: "" }],
                        default_tracker_color: "inca-silver",
                    } as State,
                }),
            },
        });
    }

    it("pre-selects the color", () => {
        const val = jest.spyOn(mocked_jquery, "val");

        getWrapper({
            name: "Bugs",
            shortname: "bugs",
            color: "peggy-pink",
        });

        expect(val).toHaveBeenCalledWith("peggy-pink");
    });

    it("pre-selects inca-silver if the color of the tracker to be created is not valid", () => {
        const val = jest.spyOn(mocked_jquery, "val");

        getWrapper({
            name: "Bugs",
            shortname: "bugs",
            color: "jône et rôse",
        });

        expect(val).toHaveBeenCalledWith("inca-silver");
    });

    it("Destroys the select2", () => {
        const select2 = jest.spyOn(mocked_jquery, "select2");

        const wrapper = getWrapper();
        wrapper.unmount();

        expect(select2).toHaveBeenCalledWith("destroy");
    });
});
