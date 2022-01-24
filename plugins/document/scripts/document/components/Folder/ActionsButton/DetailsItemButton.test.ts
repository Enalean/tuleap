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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DetailsItemButton from "./DetailsItemButton.vue";
import localVue from "../../../helpers/local-vue";
import * as location_helper from "../../../helpers/location-helper";
import type { Empty } from "../../../type";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("DetailsItemButton", () => {
    function createWrapper(): Wrapper<DetailsItemButton> {
        const item: Empty = {
            id: 1,
            title: "my item title",
            type: "empty",
            user_can_write: true,
        } as Empty;

        return shallowMount(DetailsItemButton, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { project_id: 101 },
                    },
                }),
            },
            localVue,
            propsData: { item, buttonClass: "" },
        });
    }

    it(`Given user click on details,
        Then he should be redirected to the legacy page`, () => {
        const redirect_to_url = jest.spyOn(location_helper, "redirectToUrl").mockImplementation();
        const wrapper = createWrapper();

        wrapper.get("[data-test=docman-go-to-details]").trigger("click");

        expect(redirect_to_url).toHaveBeenCalledWith(
            "/plugins/docman/?group_id=101&id=1&action=details&section=details"
        );
    });
});
