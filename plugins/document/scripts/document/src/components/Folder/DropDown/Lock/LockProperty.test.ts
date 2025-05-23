/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LockProperty from "./LockProperty.vue";
import type { Item, LockInfo, User } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("LockProperty", () => {
    function instantiateComponent(item: Item): VueWrapper<InstanceType<typeof LockProperty>> {
        return shallowMount(LockProperty, {
            props: {
                item: { ...item },
            },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    describe("The displayed label", () => {
        it("displays the 'Lock new version' label on update if the document does not have lock", () => {
            const item = { id: 1, title: "Item", lock_info: null } as Item;
            const wrapper = instantiateComponent(item);

            const label_element = wrapper.get("[data-test='lock-property-label']");

            expect(label_element.element.textContent).toMatch("Lock new version");
        });

        it("displays the 'Keep lock?' label on update if the document does have a lock", () => {
            const item = {
                id: 1,
                title: "Item",
                lock_info: {
                    lock_by: { id: 1 } as User,
                    lock_date: "2019-04-25T16:32:59+02:00",
                } as LockInfo,
            } as Item;
            const wrapper = instantiateComponent(item);

            const label_element = wrapper.get("[data-test='lock-property-label']");

            expect(label_element.element.textContent).toMatch("Keep lock?");
        });
    });
});
