/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { mount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.test-helper.js";
import store_options from "../../store/store_options";
import DeleteBaselineButton from "./DeleteBaselineButton.vue";
import { create } from "../../support/factories";

describe("DeleteBaselineButton", () => {
    const baseline = create("baseline", { id: 1 });

    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(store_options);

        wrapper = mount(DeleteBaselineButton, {
            localVue,
            mocks: {
                $store,
            },
            propsData: {
                baseline,
            },
        });
    });

    describe("when clicking", () => {
        beforeEach(() => wrapper.trigger("click"));

        it("shows modal", () => {
            expect($store.commit).toHaveBeenCalledWith(
                "dialog_interface/showModal",
                expect.any(Object)
            );
        });
    });
});
