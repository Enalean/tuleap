<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
  -
  -->

<template>
    <form class="tlp-modal" role="dialog" aria-labelledby="document-new-item-modal" v-on:submit="addDocument">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-new-item-modal">
                <i class="fa fa-plus tlp-modal-title-icon"></i>
                <translate>New document</translate>
            </h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">
                &times;
            </div>
        </div>
        <div class="tlp-modal-body document-new-item-modal-body">

            <new-item-modal-error v-if="has_modal_error"/>

            <div class="document-new-item-type-selector">
                <div class="document-new-item-type document-new-item-type-checked">
                    <i class="document-new-item-type-icon fa fa-file-o"></i>
                    <translate class="document-new-item-type-label">Empty</translate>
                </div>
            </div>

            <div class="document-new-item-metadata">
                <div class="tlp-form-element">
                    <label
                        class="tlp-label"
                        for="document-new-item-title"
                    >
                        <translate>Title</translate>
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <input
                        type="text"
                        class="tlp-input"
                        id="document-new-item-title"
                        name="title"
                        v-bind:placeholder="title_placeholder"
                        required
                        v-model="item_title"
                    >
                </div>

                <div class="tlp-form-element">
                    <label
                        class="tlp-label"
                        for="document-new-item-description"
                        v-translate
                    >
                        Description
                    </label>
                    <textarea
                        type="text"
                        class="tlp-textarea"
                        id="document-new-item-description"
                        name="description"
                        v-bind:placeholder="describe_placeholder"
                        v-model="item_description"
                    ></textarea>
                </div>
            </div>

        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >
                Cancel
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-modal-action"
            >
                <i class="fa fa-plus tlp-button-icon"
                   v-bind:class="{'fa-spin fa-spinner': is_loading}"
                ></i>
                <translate>Create document</translate>
            </button>
        </div>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { TYPE_EMPTY } from "../../../constants.js";
import NewItemModalError from "./NewItemModalError.vue";
import { selfClosingInfo } from "../../../../../../../src/www/scripts/tuleap/feedback.js";

export default {
    components: { NewItemModalError },
    data() {
        return {
            item_title: "",
            item_description: "",
            is_loading: false,
            modal: null
        };
    },
    computed: {
        ...mapState(["current_folder", "has_modal_error"]),
        title_placeholder() {
            return this.$gettext("My document");
        },
        describe_placeholder() {
            return this.$gettext("My useful document description");
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        const show = () => {
            this.modal.show();
        };
        document.addEventListener("show-new-document-modal", show);
        this.$once("hook:beforeDestroy", () => {
            document.removeEventListener("show-new-document-modal", show);
        });
    },
    methods: {
        async addDocument(event) {
            event.preventDefault();
            this.is_loading = true;

            await this.$store.dispatch("createNewDocument", [
                this.item_title,
                this.item_description,
                TYPE_EMPTY,
                this.current_folder
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
                selfClosingInfo(this.$gettext("Document has been successfully created."));
            }
        }
    }
};
</script>
