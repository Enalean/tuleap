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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import StatusPropertyWithCustomBindingForFolderUpdate from "./StatusPropertyWithCustomBindingForFolderUpdate.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { IS_STATUS_PROPERTY_USED } from "../../../../configuration-keys";

describe("StatusPropertyWithCustomBindingForFolderUpdate", () => {
    function createWrapper(
        is_status_property_used: boolean,
    ): VueWrapper<InstanceType<typeof StatusPropertyWithCustomBindingForFolderUpdate>> {
        return shallowMount(StatusPropertyWithCustomBindingForFolderUpdate, {
            props: { status_value: "none" },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [IS_STATUS_PROPERTY_USED.valueOf()]: is_status_property_used,
                },
            },
        });
    }

    it(`display status selectbox only when status property is enabled for project`, () => {
        const wrapper = createWrapper(true);

        expect(
            wrapper.find("[data-test=document-status-property-for-folder-update]").exists(),
        ).toBeTruthy();
    });

    it(`does not display status if property is not available`, () => {
        const wrapper = createWrapper(false);

        expect(
            wrapper.find("[data-test=document-status-property-for-folder-update]").exists(),
        ).toBeFalsy();
    });
});
