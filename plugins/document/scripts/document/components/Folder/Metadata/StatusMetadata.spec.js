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
import StatusMetadata from "./StatusMetadata.vue";

describe("StatusMetadata", () => {
    let status_factory;
    beforeEach(() => {
        status_factory = () => {
            return shallowMount(StatusMetadata, {
                localVue
            });
        };
    });
    it(`Given "none" status
        When the user creating a item
        Then it raise the 'itemStatusSelectEvent' event with the value 'none'`, () => {
        const wrapper = status_factory();

        const select_input = wrapper.find("[data-test=document-new-item-status]").findAll("option");

        select_input.at(0).setSelected();

        expect(wrapper.emitted().itemStatusSelectEvent[0]).toEqual(["none"]);
    });
    it(`Given "draft" status
        When the user creating a item
        Then it raise the 'itemStatusSelectEvent' event with the value 'draft'`, () => {
        const wrapper = status_factory();

        const select_input = wrapper.find("[data-test=document-new-item-status]").findAll("option");

        select_input.at(1).setSelected();

        expect(wrapper.emitted().itemStatusSelectEvent[0]).toEqual(["draft"]);
    });
    it(`Given "approved" status
        When the user creating a item
        Then it raise the 'itemStatusSelectEvent' event with the value 'approved'`, () => {
        const wrapper = status_factory();

        const select_input = wrapper.find("[data-test=document-new-item-status]").findAll("option");

        select_input.at(2).setSelected();

        expect(wrapper.emitted().itemStatusSelectEvent[0]).toEqual(["approved"]);
    });

    it(`Given "rejected" status
        When the user creating a item
        Then it raise the 'itemStatusSelectEvent' event with the value 'rejected'`, () => {
        const wrapper = status_factory();

        const select_input = wrapper.find("[data-test=document-new-item-status]").findAll("option");

        select_input.at(3).setSelected();

        expect(wrapper.emitted().itemStatusSelectEvent[0]).toEqual(["rejected"]);
    });
});
