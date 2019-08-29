/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import CreateNewVersionEmptyModal from "./CreateNewVersionEmptyModal.vue";
import { TYPE_EMPTY, TYPE_FILE } from "../../../constants.js";
import { tlp } from "tlp-mocks";

describe("CreateNewVersionEmptyModal", () => {
    let factory;

    beforeEach(() => {
        factory = props => {
            return shallowMount(CreateNewVersionEmptyModal, {
                localVue,
                propsData: { ...props }
            });
        };

        tlp.modal.and.returnValue({
            addEventListener: () => {},
            show: () => {},
            hide: () => {}
        });
    });

    afterEach(() => {});

    it("Default type for creation of new version of an empty document is file", () => {
        const wrapper = factory({
            item: { id: 10, type: TYPE_EMPTY }
        });

        expect(wrapper.vm.new_item_version.type).toBe(TYPE_FILE);
    });
});
