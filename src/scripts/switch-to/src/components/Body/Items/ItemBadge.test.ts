/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import ItemBadge from "./ItemBadge.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ItemBadge", () => {
    it("should use the color of the badge", () => {
        const wrapper = shallowMount(ItemBadge, {
            global: getGlobalTestOptions(),
            props: {
                badge: {
                    label: "On going",
                    color: "fiesta-red",
                },
            },
        });

        expect(wrapper.element).toMatchInlineSnapshot(`
            <span
              class="tlp-badge-outline tlp-badge-on-dark-background tlp-badge-fiesta-red"
            >
              On going
            </span>
        `);
    });

    it("should default to secondary if no color defined", () => {
        const wrapper = shallowMount(ItemBadge, {
            global: getGlobalTestOptions(),
            props: {
                badge: {
                    label: "On going",
                    color: null,
                },
            },
        });

        expect(wrapper.element).toMatchInlineSnapshot(`
            <span
              class="tlp-badge-outline tlp-badge-on-dark-background tlp-badge-secondary"
            >
              On going
            </span>
        `);
    });
});
