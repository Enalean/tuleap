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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";

import QuickLookDocumentActionButton from "./QuickLookDocumentActionButton.vue";
import { TYPE_FILE, TYPE_WIKI } from "../../../constants.js";

describe("QuickLookDocumentActionButton", () => {
    let document_action_button_factory;
    beforeEach(() => {
        document_action_button_factory = (props = {}) => {
            return shallowMount(QuickLookDocumentActionButton, {
                localVue,
                propsData: { ...props }
            });
        };
    });

    it(`[Create new version] button is displayed if item is a file`, () => {
        const item = {
            type: TYPE_FILE,
            approval_table: null
        };
        const wrapper = document_action_button_factory({ item });
        expect(
            wrapper.find("[data-test=docman-quicklook-action-button-new-version]").exists()
        ).toBeTruthy();
    });
    it(`[Create new version] button is displayed if item is a wiki without approval table`, () => {
        const item = {
            type: TYPE_WIKI,
            approval_table: null
        };
        const wrapper = document_action_button_factory({ item });
        expect(
            wrapper.find("[data-test=docman-quicklook-action-button-new-version]").exists()
        ).toBeTruthy();
    });
    it(`[Create new version] button is not displayed if item is a wiki with an approval table`, () => {
        const item = {
            type: TYPE_WIKI,
            approval_table: {
                status: "Not yet"
            }
        };
        const wrapper = document_action_button_factory({ item });
        expect(
            wrapper.find("[data-test=docman-quicklook-action-button-new-version]").exists()
        ).toBeFalsy();
    });
});
