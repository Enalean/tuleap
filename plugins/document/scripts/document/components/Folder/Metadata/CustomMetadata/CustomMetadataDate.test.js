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

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import CustomMetadataDate from "./CustomMetadataDate.vue";

describe("CustomMetadataDate", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMetadataDate, {
                localVue,
                propsData: { ...props },
            });
        };
    });
    it(`does not render the component when type does not match`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "2019-06-30T00:00:00+03:00",
            is_required: true,
            name: "date field",
            type: "text",
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata, value: "" });
        expect(wrapper.find("[data-test=document-custom-metadata-date]").exists()).toBeFalsy();
    });

    it(`User can choose a date value`, () => {
        const currentlyUpdatedItemMetadata = {
            value: "2019-06-30T00:00:00+03:00",
            is_required: true,
            name: "date field",
            type: "text",
        };

        const wrapper = factory({ currentlyUpdatedItemMetadata, value: "" });
        wrapper.vm.custom_metadata_date = "2019-06-30";

        expect(wrapper.emitted().input).toEqual([["2019-06-30"]]);
    });
});
