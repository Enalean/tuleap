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
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import CreateNewItemVersionButton from "./NewItemVersionButton.vue";

import localVue from "../../../helpers/local-vue.js";
import EventBus from "../../../helpers/event-bus.js";

describe("CreateNewItemVersionButton", () => {
    let create_new_item_version_button_factory, store;
    beforeEach(() => {
        store = createStoreMock({});
        create_new_item_version_button_factory = (props = {}) => {
            return shallowMount(CreateNewItemVersionButton, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given item is a file
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object)
        );
    });

    it(`Given item is an embedded file
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "embedded",
                user_can_write: true,
            },
        });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object)
        );
    });

    it(`Given item is a wiki with no approval table
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "wiki",
                user_can_write: true,
                approval_table: null,
            },
        });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object)
        );
    });

    it(`Given item is a wiki with an approval table
        When we click on [create new version]
        Then no event should be dispatched`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "wiki",
                user_can_write: true,
                approval_table: {
                    approval_state: "not yet",
                },
            },
        });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(event_bus_emit).not.toHaveBeenCalled();
    });

    it(`Given item is an empty document
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "empty",
                user_can_write: true,
            },
        });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(event_bus_emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object)
        );
    });

    it(`Given item is a link document
        When we click on [create new version]
        Then we should load link`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "link",
            user_can_write: true,
        };

        const wrapper = create_new_item_version_button_factory({ item });

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith("loadDocument", 1);
    });

    it(`Given user can't write in folder
        Then update link is not available`, () => {
        const wrapper = create_new_item_version_button_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            },
        });

        expect(wrapper.contains("[data-test=document-new-item-version-button]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-folder-update-button]")).toBeFalsy();
    });
});
