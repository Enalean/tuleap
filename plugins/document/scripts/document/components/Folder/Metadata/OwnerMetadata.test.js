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

import localVue from "../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import OwnerMetadata from "./OwnerMetadata.vue";
import { TYPE_FILE } from "../../../constants.js";

describe("OwnerMetadata", () => {
    let owner_factory;
    beforeEach(() => {
        owner_factory = (props = {}) => {
            return shallowMount(OwnerMetadata, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`Given owner value is updated
              Then the props used for document creation is updated`, () => {
        const wrapper = owner_factory({
            currentlyUpdatedItem: {
                owner: {
                    id: 137,
                },
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
        });

        wrapper.vm.owner_id = 102;

        expect(wrapper.vm.currentlyUpdatedItem.owner_id).toEqual(102);
    });
});
