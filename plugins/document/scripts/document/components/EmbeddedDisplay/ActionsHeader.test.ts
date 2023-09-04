/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ActionsHeader from "./ActionsHeader.vue";
import type { Item } from "../../type";

describe("ActionsHeader", () => {
    function getWrapper(item: Item): VueWrapper<InstanceType<typeof ActionsHeader>> {
        return shallowMount(ActionsHeader, {
            props: { item },
        });
    }

    it(`Given user can write
        When he displays item actions
        Then user should be able to create a new version`, () => {
        const wrapper = getWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as Item);

        expect(
            wrapper.find("[data-test=item-action-create-new-version-button]").exists(),
        ).toBeTruthy();
    });

    it(`Given user can read item
        When he displays item actions
        Then user should not be able to create a new version`, () => {
        const wrapper = getWrapper({
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: false,
        } as Item);

        expect(
            wrapper.find("[data-test=item-action-create-new-version-button]").exists(),
        ).toBeFalsy();
    });
});
