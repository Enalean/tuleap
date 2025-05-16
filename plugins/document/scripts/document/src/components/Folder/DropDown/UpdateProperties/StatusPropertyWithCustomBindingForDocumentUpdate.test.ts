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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import StatusPropertyWithCustomBindingForDocumentUpdate from "./StatusPropertyWithCustomBindingForDocumentUpdate.vue";
import type { ConfigurationState } from "../../../../store/configuration";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("StatusPropertyWithCustomBindingForDocumentUpdate", () => {
    function createWrapper(
        is_status_property_used: boolean,
    ): VueWrapper<InstanceType<typeof StatusPropertyWithCustomBindingForDocumentUpdate>> {
        return shallowMount(StatusPropertyWithCustomBindingForDocumentUpdate, {
            props: { status_value: "none" },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                is_status_property_used,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it(`display status selectbox only when status property is enabled for project`, () => {
        const wrapper = createWrapper(true);

        expect(wrapper.find("[data-test=document-status-for-item-update]").exists()).toBeTruthy();
    });

    it(`does not display status if property is not available`, () => {
        const wrapper = createWrapper(false);

        expect(wrapper.find("[data-test=document-status-for-item-update]").exists()).toBeFalsy();
    });
});
