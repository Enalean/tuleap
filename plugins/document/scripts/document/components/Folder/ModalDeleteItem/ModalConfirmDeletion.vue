<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
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
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                ×
            </div>
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

<script>
import { sprintf } from "sprintf-js";
import { mapState, mapGetters } from "vuex";
import { modal } from "tlp";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";

export default {
    components: {
        ModalFeedback,
        "delete-associated-wiki-page-checkbox": () =>
            import("./AdditionalCheckboxes/DeleteAssociatedWikiPageCheckbox.vue"),
    },
    props: {
        item: Object,
        shouldRedirectToParentAfterDeletion: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            modal: null,
            is_item_being_deleted: false,
            wiki_page_referencers_loading: false,
            additional_options: {},
            wiki_page_referencers: null,
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        ...mapState(["current_folder"]),
        ...mapGetters(["is_item_a_wiki", "is_item_a_folder"]),
        close() {
            return this.$gettext("Close");
        },
        modal_description() {
            return sprintf(
                this.$gettext(
                    'You are about to delete "%s" permanently. Please confirm your action.'
                ),
                this.item.title
            );
        },
        is_confirm_button_disabled() {
            return this.has_modal_error || this.is_an_action_on_going;
        },
        is_an_action_on_going() {
            return this.is_item_being_deleted || this.wiki_page_referencers_loading;
        },
        can_wiki_checkbox_be_shown() {
            return (
                this.is_item_a_wiki(this.item) &&
                !this.wiki_page_referencers_loading &&
                this.wiki_page_referencers !== null
            );
        },
    },
    mounted() {
        this.modal = modal(this.$el);
        this.modal.addEventListener("tlp-modal-hidden", this.resetModal);

        this.modal.show();

        if (this.is_item_a_wiki(this.item) && this.item.wiki_properties.page_id !== null) {
            this.setWikiPageReferencers();
        }
    },
    methods: {
        async deleteItem() {
            const deleted_item = { ...this.item };
            this.is_item_being_deleted = true;

            await this.$store.dispatch("deleteItem", [this.item, this.additional_options]);

            if (!this.has_modal_error) {
                await this.$router.replace({
                    name: "folder",
                    params: { item_id: deleted_item.parent_id },
                });
                this.$store.commit("showPostDeletionNotification");

                this.modal.hide();
            }

            this.is_item_being_deleted = false;
        },
        async setWikiPageReferencers() {
            this.wiki_page_referencers_loading = true;

            const referencers = await this.$store.dispatch(
                "getWikisReferencingSameWikiPage",
                this.item
            );

            this.wiki_page_referencers_loading = false;
            this.wiki_page_referencers = referencers;
        },
        resetModal() {
            this.$store.commit("error/resetModalError");
            this.$emit("delete-modal-closed");
        },
    },
};
</script>
