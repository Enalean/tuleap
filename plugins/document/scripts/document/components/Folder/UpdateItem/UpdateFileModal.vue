<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <form class="tlp-modal" role="dialog" aria-labelledby="document-item-modal" v-on:submit="updateFile">
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <modal-feedback/>
        <div class="tlp-modal-body">
            <file-update-properties v-bind:version="version" v-bind:item="item">
                <file-properties v-model="uploaded_item.file_properties" v-bind:item="uploaded_item"/>
            </file-update-properties>
        </div>
        <modal-footer v-bind:is-loading="is_loading" v-bind:submit-button-label="submit_button_label" v-bind:aria-labelled-by="aria_labelled_by"/>
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import FileProperties from "../NewItem/Property/FileProperties.vue";
import FileUpdateProperties from "./FileUpdateProperties.vue";

export default {
    name: "UpdateFileModal",
    components: {
        FileUpdateProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        FileProperties
    },
    data() {
        return {
            item: {},
            uploaded_item: {},
            version: {},
            is_loading: false,
            is_displayed: false,
            modal: null
        };
    },
    computed: {
        ...mapState(["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Update");
        },
        modal_title() {
            return this.$gettext("Update file");
        },
        aria_labelled_by() {
            return "document-update-item-modal";
        }
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();
    },
    methods: {
        registerEvents() {
            document.addEventListener("show-update-file-modal", this.show);
            this.$once("hook:beforeDestroy", () => {
                document.removeEventListener("show-update-file-modal", this.show);
            });
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show(event) {
            this.item = event.detail.current_item;
            this.version = {
                title: "",
                changelog: "",
                is_file_locked: this.item.lock_info !== null
            };
            this.uploaded_item = {
                type: this.item.type,
                file_properties: {}
            };
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.uploaded_item = {};
        },
        async updateFile(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("resetModalError");

            await this.$store.dispatch("updateFileFromModal", [
                this.item,
                this.uploaded_item.file_properties.file,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.uploaded_item = {};
                this.modal.hide();
            }
        }
    }
};
</script>
