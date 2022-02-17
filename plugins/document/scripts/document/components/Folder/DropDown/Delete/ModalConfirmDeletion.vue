<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-confirm-deletion-modal-title"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-confirm-deletion-modal-title" v-translate>
                Hold on a second!
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <modal-feedback />
        <div class="tlp-modal-body">
            <p>{{ modal_description }}</p>
            <div
                class="tlp-alert-warning"
                v-if="is_item_a_folder(item)"
                data-test="delete-folder-warning"
                v-translate
            >
                When you delete a folder, all its content is also deleted. Please think wisely!
            </div>
            <delete-associated-wiki-page-checkbox
                v-if="can_wiki_checkbox_be_shown"
                v-model="additional_options"
                v-bind:item="item"
                v-bind:wiki-page-referencers="wiki_page_referencers"
                data-test="delete-wiki-checkbox"
            />
            <span class="document-confirm-deletion-modal-wiki-page-referencers-loading">
                <i
                    class="fa fa-spin fa-circle-o-notch"
                    v-if="is_item_a_wiki(item) && wiki_page_referencers_loading"
                ></i>
            </span>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                Cancel
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="document-confirm-deletion-button"
                v-on:click="deleteItem()"
                v-bind:class="{ disabled: is_confirm_button_disabled }"
                v-bind:disabled="is_confirm_button_disabled"
            >
                <i
                    class="fa tlp-button-icon"
                    v-bind:class="{
                        'fa-spin fa-circle-o-notch': is_an_action_on_going,
                        'fa-trash-o': !is_an_action_on_going,
                    }"
                ></i>
                <span v-translate>Delete</span>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { sprintf } from "sprintf-js";
import type { Modal } from "tlp";
import { createModal } from "tlp";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import { isFolder, isWiki } from "../../../../helpers/type-check-helper";
import { Component, Prop, Vue } from "vue-property-decorator";
import type { Folder, Item } from "../../../../type";
import { namespace, State } from "vuex-class";
import type { ItemPath } from "../../../../store/actions-helpers/build-parent-paths";

const error = namespace("error");

@Component({
    components: {
        ModalFeedback,
        "delete-associated-wiki-page-checkbox": () =>
            import("./DeleteAssociatedWikiPageCheckbox.vue"),
    },
})
export default class ModalConfirmDeletion extends Vue {
    @Prop({ required: true })
    readonly item!: Item;

    @Prop({ required: true })
    readonly shouldRedirectToParentAfterDeletion!: boolean;

    @error.State
    readonly has_modal_error!: boolean;

    @State
    readonly current_folder!: Folder;

    @State
    readonly currently_previewed_item!: Item;

    private modal: null | Modal = null;
    private is_item_being_deleted = false;
    private wiki_page_referencers_loading = false;
    private additional_options = {};
    private wiki_page_referencers: null | Array<ItemPath> = null;

    get is_confirm_button_disabled(): boolean {
        return this.has_modal_error || this.is_an_action_on_going;
    }
    get is_an_action_on_going(): boolean {
        return this.is_item_being_deleted || this.wiki_page_referencers_loading;
    }
    get can_wiki_checkbox_be_shown(): boolean {
        return (
            isWiki(this.item) &&
            !this.wiki_page_referencers_loading &&
            this.wiki_page_referencers !== null
        );
    }
    get close(): string {
        return this.$gettext("Close");
    }
    get modal_description(): string {
        return sprintf(
            this.$gettext('You are about to delete "%s" permanently. Please confirm your action.'),
            this.item.title
        );
    }

    mounted(): void {
        if (!this.$el) {
            return;
        }
        this.modal = createModal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.resetModal);

        this.modal.show();

        if (isWiki(this.item) && this.item.wiki_properties.page_id !== null) {
            this.setWikiPageReferencers();
        }
    }

    async deleteItem(): Promise<void> {
        const deleted_item_parent_id = this.item.parent_id;
        this.is_item_being_deleted = true;

        await this.$store.dispatch("deleteItem", [this.item, this.additional_options]);

        if (!this.has_modal_error && this.modal && deleted_item_parent_id) {
            this.$store.commit("showPostDeletionNotification");
            await this.redirectToParentFolderIfNeeded(deleted_item_parent_id.toString());

            this.modal.hide();
        }

        this.is_item_being_deleted = false;
    }

    async setWikiPageReferencers(): Promise<void> {
        this.wiki_page_referencers_loading = true;

        const referencers = await this.$store.dispatch(
            "getWikisReferencingSameWikiPage",
            this.item
        );

        this.wiki_page_referencers_loading = false;
        this.wiki_page_referencers = referencers;
    }

    resetModal(): void {
        this.$store.commit("error/resetModalError");
        this.$emit("delete-modal-closed");
    }

    async redirectToParentFolderIfNeeded(deleted_item_parent_id: string) {
        const is_item_the_current_folder = this.item.id === this.current_folder.id;
        const is_item_being_previewed =
            this.currently_previewed_item !== null &&
            this.currently_previewed_item.id === this.item.id;

        if (!is_item_the_current_folder && !is_item_being_previewed) {
            return;
        }

        this.$store.commit("updateCurrentlyPreviewedItem", null);
        await this.$router.replace({
            name: "folder",
            params: { item_id: deleted_item_parent_id },
        });
    }

    is_item_a_wiki(item: Item): boolean {
        return isWiki(item);
    }

    is_item_a_folder(item: Item): boolean {
        return isFolder(item);
    }
}
</script>
