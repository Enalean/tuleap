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

import * as tlp from "tlp";
import { shallowMount, Wrapper } from "@vue/test-utils";
import {
    createStoreMock,
    Store
} from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { createTaskboardLocalVue } from "../../helpers/local-vue-for-test";
import UnderConstructionModal from "./UnderConstructionModal.vue";

jest.mock("tlp", () => {
    return {
        __esModule: true,
        modal: jest.fn()
    };
});

async function createWrapper<G, Spy>(
    store: Store<TestState, G, Spy>
): Promise<Wrapper<UnderConstructionModal>> {
    return shallowMount(UnderConstructionModal, {
        localVue: await createTaskboardLocalVue(),
        mocks: {
            $store: store
        }
    });
}

interface TestState {
    user: {
        user_id: number;
    };
}

const storage_key = "tuleap-taskboard-under-construction-modal-hidden-104";

describe("UnderConstructionModal", () => {
    let store: Store<TestState>;
    const tlp_modal = (tlp.modal as unknown) as jest.SpyInstance;
    let hide_listener: Function;
    beforeEach(() => {
        store = createStoreMock({ state: { user: { user_id: 104 } } });
        tlp_modal.mockReset();
        tlp_modal.mockImplementation(() => {
            return {
                addEventListener: jest
                    .fn()
                    .mockImplementation((event_name: string, listener: Function) => {
                        hide_listener = listener;
                    }),
                show: jest.fn()
            };
        });
        sessionStorage.clear();
    });

    it(`When the modal has not been opened before,
        it will open the modal as soon as the component is mounted`, async () => {
        const actual_tlp = jest.requireActual("tlp");
        jest.spyOn(tlp, "modal").mockImplementation(actual_tlp.modal);

        const wrapper = await createWrapper(store);

        expect(wrapper.element).toMatchSnapshot();
    });

    it(`When the modal is closed, it will save the current date/time`, async () => {
        await createWrapper(store);
        hide_listener();

        const saved_date = sessionStorage.getItem(storage_key);
        expect(saved_date).not.toBeNull();
    });

    it(`When the modal has been opened in the last 24 hours,
        it won't open the modal`, async () => {
        const six_hours_ago = new Date();
        six_hours_ago.setHours(six_hours_ago.getHours() - 6);
        sessionStorage.setItem(storage_key, six_hours_ago.toUTCString());

        await createWrapper(store);

        expect(tlp_modal).not.toHaveBeenCalled();
    });

    it(`When the modal has been opened before the last 24 hours,
        it will open the modal again`, async () => {
        const thirty_six_hours_ago = new Date();
        thirty_six_hours_ago.setDate(thirty_six_hours_ago.getDate() - 1);
        thirty_six_hours_ago.setHours(thirty_six_hours_ago.getHours() - 12);
        sessionStorage.setItem(storage_key, thirty_six_hours_ago.toUTCString());

        await createWrapper(store);

        expect(tlp_modal).toHaveBeenCalled();
    });

    it(`When I'm browsing as anonymous user,
        the session storage key will be suffixed by "0"`, async () => {
        store.state.user.user_id = 0;
        await createWrapper(store);
        hide_listener();

        const saved_date = sessionStorage.getItem(
            "tuleap-taskboard-under-construction-modal-hidden-0"
        );
        expect(saved_date).not.toBeNull();
    });
});
