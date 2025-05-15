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
import DocumentTitleLockInfo from "./DocumentTitleLockInfo.vue";
import { TYPE_EMBEDDED } from "../../../constants";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("DocumentTitleLockInfo", () => {
    let document_locked_factory;

    beforeEach(() => {
        const store = createStoreMock({});

        document_locked_factory = (props = {}) => {
            return shallowMount(DocumentTitleLockInfo, {
                props: { ...props },
                mocks: { $store: store },
                global: { ...getGlobalTestOptions({}) },
            });
        };
    });

    it(`Given document is not locked
        When we display lock info
        Then we should not display anything`, () => {
        const item = {
            id: 42,
            title: "my unlocked document",
            type: TYPE_EMBEDDED,
        };

        const wrapper = document_locked_factory({
            item,
            isDisplayingInHeader: true,
        });

        expect(wrapper.find("[data-test=document-lock-information]").exists()).toBeFalsy();
    });

    it(`Given document is locked
        When we display lock info
        Then we should display badge`, () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            lock_info: {
                locked_by: {
                    display_name: "lock owner name",
                },
            },
        };

        const wrapper = document_locked_factory({
            item,
            isDisplayingInHeader: true,
        });

        expect(wrapper.find("[data-test=document-lock-information]").exists()).toBeTruthy();
    });

    it(`Given document is displayed in item view
        When we display lock info
        Then we should have dedicated badges classes`, () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            lock_info: {
                locked_by: {
                    display_name: "lock owner name",
                },
            },
        };

        const wrapper = document_locked_factory({
            item,
            isDisplayingInHeader: true,
        });

        expect(wrapper.find(".document-display-lock").exists()).toBeTruthy();
        expect(wrapper.find(".document-display-lock-icon").exists()).toBeTruthy();
        expect(wrapper.find(".document-tree-item-toggle-quicklook-lock-icon").exists()).toBeFalsy();
    });

    it(`Given document is displayed in tree view
        When we display lock info
        Then we should not have item header title style`, () => {
        const item = {
            id: 42,
            title: "my locked document",
            type: TYPE_EMBEDDED,
            lock_info: {
                locked_by: {
                    display_name: "lock owner name",
                },
            },
        };

        const wrapper = document_locked_factory({
            item,
            isDisplayingInHeader: false,
        });

        expect(wrapper.find(".document-display-lock").exists()).toBeFalsy();
        expect(wrapper.find(".document-display-lock-icon").exists()).toBeFalsy();
        expect(
            wrapper.find(".document-tree-item-toggle-quicklook-lock-icon").exists(),
        ).toBeTruthy();
    });
});
