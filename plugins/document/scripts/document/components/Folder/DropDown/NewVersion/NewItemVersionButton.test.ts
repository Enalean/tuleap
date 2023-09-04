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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import CreateNewItemVersionButton from "./NewItemVersionButton.vue";

import type { Embedded, Empty, Item, ItemFile, Link, Wiki } from "../../../../type";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

jest.mock("../../../../helpers/emitter");

describe("CreateNewItemVersionButton", () => {
    let load_document: jest.Mock;

    beforeEach(() => {
        load_document = jest.fn();
    });

    function createWrapper(
        item: Item,
    ): VueWrapper<InstanceType<typeof CreateNewItemVersionButton>> {
        return shallowMount(CreateNewItemVersionButton, {
            props: { item: item, buttonClasses: "", iconClasses: "" },
            global: {
                ...getGlobalTestOptions({
                    actions: { loadDocument: load_document },
                }),
            },
        });
    }

    it(`Given item is a wiki with an approval table
        When we click on [create new version]
        Then no event should be dispatched`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "wiki",
            user_can_write: true,
            approval_table: {
                approval_state: "not yet",
            },
        } as Wiki;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(emitter.emit).not.toHaveBeenCalled();
    });

    it(`Given item is a file
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: true,
        } as ItemFile;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object),
        );
    });

    it(`Given item is an embedded file
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "embedded",
            user_can_write: true,
        } as Embedded;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object),
        );
    });

    it(`Given item is a wiki with no approval table
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "wiki",
            user_can_write: true,
            approval_table: null,
        } as Wiki;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object),
        );
    });

    it(`Given item is an empty document
        When we click on [create new version]
        Then create-new-item-version event should be dispatched`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "empty",
            user_can_write: true,
        } as Empty;
        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith(
            "show-create-new-item-version-modal",
            expect.any(Object),
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
        } as Link;

        const wrapper = createWrapper(item);

        wrapper.get("[data-test=document-new-item-version-button]").trigger("click");

        expect(load_document).toHaveBeenCalledWith(expect.anything(), 1);
    });

    it(`Given user can't write in folder
        Then update link is not available`, () => {
        const item = {
            id: 1,
            title: "my item title",
            type: "file",
            user_can_write: false,
        } as ItemFile;
        const wrapper = createWrapper(item);

        expect(wrapper.find("[data-test=document-new-item-version-button]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-folder-update-button]").exists()).toBeFalsy();
    });
});
