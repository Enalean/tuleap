/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";
import { TYPE_WIKI } from "../../../constants.js";
import ConfirmationModal from "./ModalConfirmDeletion.vue";
import { tlp } from "tlp-mocks";
import { createStoreMock } from "../../../helpers/store-wrapper.spec-helper.js";

describe("ModalConfirmationDeletion", () => {
    let state, store_options, store;

    function getDeletionModal(item = {}) {
        return shallowMount(ConfirmationModal, {
            localVue,
            mocks: { $store: store },
            propsData: {
                item: { ...item }
            },
            stubs: {
                "delete-associated-wiki-page-checkbox": `<div data-test="checkbox"></div>`
            }
        });
    }

    beforeEach(() => {
        state = {
            error: {
                has_modal_error: false
            }
        };
        store_options = {
            state
        };
        store = createStoreMock(store_options);

        tlp.modal.and.returnValue({
            addEventListener: () => {},
            show: jasmine.createSpy("show")
        });
    });

    it(`When the item is a wiki, then it should add a checkbox`, () => {
        const item = {
            id: 42,
            title: "my wiki",
            wiki_properties: {
                page_name: "my wiki"
            },
            type: TYPE_WIKI
        };

        const deletion_modal = getDeletionModal(item);

        expect(deletion_modal.contains("[data-test=checkbox]")).toBeTruthy();
    });
});
