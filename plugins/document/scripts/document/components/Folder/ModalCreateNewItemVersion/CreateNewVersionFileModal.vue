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
    <form
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit="createNewFileVersion"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <item-update-properties
                v-bind:version="version"
                v-bind:item="item"
                v-on:approvalTableActionChange="setApprovalUpdateAction"
            >
                <file-properties
                    v-model="uploaded_item.file_properties"
                    v-bind:item="uploaded_item"
                />
            </item-update-properties>
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-plus'"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import FileProperties from "../Property/FileProperties.vue";
import ItemUpdateProperties from "../Property/ItemUpdateProperties.vue";

export default {
    name: "CreateNewVersionFileModal",
    components: {
        ItemUpdateProperties,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
        FileProperties,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            uploaded_item: {},
            version: {},
            is_loading: false,
            is_displayed: false,
            modal: null,
        };
    },
    computed: {
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create new version");
        },
        modal_title() {
            return sprintf(this.$gettext('New version for "%s"'), this.item.title);
        },
        aria_labelled_by() {
            return "document-new-item-version-modal";
        },
    },
    mounted() {
        this.modal = createModal(this.$el);
        this.registerEvents();

        this.show();
    },
    methods: {
        setApprovalUpdateAction(value) {
            this.approval_table_action = value;
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        show() {
            this.version = {
                title: "",
                changelog: "",
                is_file_locked: this.item.lock_info !== null,
            };
            this.uploaded_item = {
                type: this.item.type,
                file_properties: {},
            };
            this.is_displayed = true;
            this.modal.show();
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
            this.uploaded_item = {};
        },
        async createNewFileVersion(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewFileVersionFromModal", [
                this.item,
                this.uploaded_item.file_properties.file,
                this.version.title,
                this.version.changelog,
                this.version.is_file_locked,
                this.item.approval_table_action,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.uploaded_item = {};
                this.modal.hide();
            }
        },
    },
};
</script>
