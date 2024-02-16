/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE } from "../../../../constants";
import type { Folder, Item, ItemFile, RootState, Wiki } from "../../../../type";
import ModalConfirmDeletion from "./ModalConfirmDeletion.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";
import * as router from "../../../../helpers/use-router";
import type { Router } from "vue-router";
import type { ConfigurationState } from "../../../../store/configuration";

describe("ModalConfirmDeletion", () => {
    let get_wikis: jest.Mock;
    let delete_items: jest.Mock;
    let update_preview: jest.Mock;
    let show_notifications: jest.Mock;
    let mock_replace: jest.Mock;
    beforeEach(() => {
        const fake_modal = {
            addEventListener: jest.fn(),
            show: jest.fn(),
            hide: jest.fn(),
        } as unknown as Modal;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(fake_modal);

        jest.spyOn(router, "useRouter").mockImplementation(() => {
            return { replace: mock_replace, push: jest.fn() } as unknown as Router;
        });

        get_wikis = jest.fn();
        delete_items = jest.fn();
        update_preview = jest.fn();
        show_notifications = jest.fn();
        mock_replace = jest.fn();
    });

    function createWrapper(
        item: Item,
        currently_previewed_item: Item | null,
        wiki_referencing_same_page: Array<Wiki> | null,
    ): VueWrapper<InstanceType<typeof ModalConfirmDeletion>> {
        get_wikis = jest.fn().mockReturnValue(wiki_referencing_same_page);

        return shallowMount(ModalConfirmDeletion, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: { has_modal_error: false },
                            namespaced: true,
                        },
                        configuration: {
                            state: {
                                user_id: "1",
                                project_id: "1",
                            } as ConfigurationState,
                            namespaced: true,
                        },
                    },
                    state: {
                        currently_previewed_item,
                        current_folder: { id: 42 } as Folder,
                    } as RootState,
                    actions: {
                        getWikisReferencingSameWikiPage: get_wikis,
                        deleteItem: delete_items,
                    },
                    mutations: {
                        updateCurrentlyPreviewedItem: update_preview,
                        showPostDeletionNotification: show_notifications,
                    },
                }),
                stubs: ["router-link", "router-view"],
            },
        });
    }

    describe("When the item is a wiki", () => {
        let item: Wiki;

        beforeEach(() => {
            item = {
                id: 42,
                title: "my wiki",
                wiki_properties: {
                    page_name: "my wiki",
                    page_id: 123,
                },
                type: "wiki",
            } as Wiki;
        });

        it(`When some docman wikis reference the same wiki page, then it should add a checkbox`, async () => {
            const wikis = [
                {
                    id: 43,
                    title: "my other wiki",
                    wiki_properties: {
                        page_name: "my wiki",
                        page_id: 123,
                    },
                    type: "wiki",
                } as Wiki,
            ];
            const deletion_modal = await createWrapper(item, null, wikis);
            await nextTick();

            expect(get_wikis).toHaveBeenCalled();
            expect(deletion_modal.vm.can_wiki_checkbox_be_shown).toBeTruthy();
        });

        it(`When there is a problem retrieving the wiki page referencers (either not found or either unreadable), then it should not add a checkbox`, async () => {
            const deletion_modal = await createWrapper(
                item,
                null,
                USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE,
            );

            expect(get_wikis).toHaveBeenCalled();
            expect(deletion_modal.find("[data-test=checkbox]").exists()).toBeFalsy();
        });

        it(`when it does not reference an existing wiki page, then it should not add a checkbox`, () => {
            item.wiki_properties.page_id = null;

            const deletion_modal = createWrapper(item, null, null);

            expect(get_wikis).not.toHaveBeenCalled();
            expect(deletion_modal.find("[data-test=checkbox]").exists()).toBeFalsy();
        });
    });

    it("When the item is a folder, then it should display a special warning and the checkbox should not be shown", () => {
        const item = {
            id: 42,
            title: "my folder",
            type: "folder",
        } as Folder;

        const deletion_modal = createWrapper(item, null, null);

        expect(get_wikis).not.toHaveBeenCalled();
        expect(deletion_modal.find("[data-test=delete-folder-warning]").exists()).toBeTruthy();
        expect(deletion_modal.find("[data-test=checkbox]").exists()).toBeFalsy();
    });

    it(`when I click on the delete button, it deletes the item`, () => {
        const item = {
            id: 42,
            title: "my folder",
            type: "folder",
        } as Folder;

        const deletion_modal = createWrapper(item, null, null);
        deletion_modal.get("[data-test=document-confirm-deletion-button]").trigger("click");

        expect(delete_items).toHaveBeenCalled();
    });

    describe("Redirection after deletion", () => {
        it("Closes the quick look pane when the item to be deleted is currently previewed", async () => {
            const item = {
                id: 50,
                title: "my file",
                type: "file",
                parent_id: 42,
            } as ItemFile;

            const deletion_modal = createWrapper(item, item, null);

            deletion_modal.get("[data-test=document-confirm-deletion-button]").trigger("click");
            await nextTick();
            await nextTick();

            expect(delete_items).toHaveBeenCalled();
            expect(show_notifications).toHaveBeenCalled();
            expect(update_preview).toHaveBeenCalled();
            expect(mock_replace).toHaveBeenCalled();
        });

        it("redirects to the parent folder when the item to be deleted is the current folder", async () => {
            const item = {
                id: 42,
                title: "my folder",
                type: "folder",
                parent_id: 41,
            } as Folder;

            const deletion_modal = createWrapper(item, item, null);

            deletion_modal.get("[data-test=document-confirm-deletion-button]").trigger("click");
            await nextTick();
            await nextTick();

            expect(delete_items).toHaveBeenCalled();
            expect(show_notifications).toHaveBeenCalled();
            expect(update_preview).toHaveBeenCalledWith(
                {
                    configuration: {
                        project_id: "1",
                        user_id: "1",
                    },
                    current_folder: { id: 42 },
                    currently_previewed_item: item,
                    error: { has_modal_error: false },
                },
                null,
            );
            expect(mock_replace).toHaveBeenCalledWith({
                name: "folder",
                params: { item_id: "41" },
            });
        });
    });
});
