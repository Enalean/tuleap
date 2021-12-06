/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ModalFeedback from "./ModalFeedback.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { RootState } from "../../../type";
import type { ErrorState } from "../../../store/error/module";

describe("ModalFeedback", () => {
    function createWrapper(has_error: boolean): Wrapper<ModalFeedback> {
        const error = { has_modal_error: has_error } as unknown as ErrorState;
        const state = { error: error } as RootState;

        const store_options = {
            state,
        };

        const store = createStoreMock(store_options);
        return shallowMount(ModalFeedback, {
            mocks: { $store: store },
        });
    }
    it("Does not display anything when no error", () => {
        const wrapper = createWrapper(false);
        expect(wrapper.find("[data-test=modal-has-error]").exists()).toBeFalsy();
    });

    it("Displays error", () => {
        const wrapper = createWrapper(true);
        expect(wrapper.find("[data-test=modal-has-error]").exists()).toBeTruthy();
    });
});
