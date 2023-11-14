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

import { describe, expect, it } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import TroveCatLink from "./TroveCatLink.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { IS_TROVE_CAT_ENABLED } from "../../injection-keys";

describe("TroveCatLink", () => {
    it("Display the link", () => {
        const wrapper = shallowMount(TroveCatLink, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [IS_TROVE_CAT_ENABLED as symbol]: true,
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does net display the link to trove cat if it is deactivated", () => {
        const wrapper = shallowMount(TroveCatLink, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [IS_TROVE_CAT_ENABLED as symbol]: false,
                },
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
