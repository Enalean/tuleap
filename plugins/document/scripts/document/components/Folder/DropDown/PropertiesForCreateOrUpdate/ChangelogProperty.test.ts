/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ChangelogProperty from "./ChangelogProperty.vue";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

jest.mock("../../../../helpers/emitter");

describe("ChangelogProperty", () => {
    function createWrapper(): VueWrapper<InstanceType<typeof ChangelogProperty>> {
        return shallowMount(ChangelogProperty, {
            props: { value: "a changelog" },
            global: { ...getGlobalTestOptions({}) },
        });
    }

    it(`send a custom event on change`, () => {
        const wrapper = createWrapper();

        wrapper.find("[data-test=document-update-changelog]").setValue("an updated changelog");

        expect(emitter.emit).toHaveBeenCalledWith(
            "update-changelog-property",
            "an updated changelog",
        );
    });
});
