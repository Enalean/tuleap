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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <form
        class="tlp-modal"
        role="dialog"
        data-test="document-new-folder-modal"
        aria-labelledby="document-new-folder-modal"
        v-on:submit="addFolder"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <folder-global-metadata-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
            />
            <creation-modal-permissions-section
                v-if="item.permissions_for_groups"
                v-model="item.permissions_for_groups"
                v-bind:project_ugroups="project_ugroups"
            />
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
import { TYPE_FOLDER } from "../../../constants.js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import EventBus from "../../../helpers/event-bus.js";
import FolderGlobalMetadataForCreate from "../Metadata/FolderMetadata/FolderGlobalMetadataForCreate.vue";
import { getCustomMetadata } from "../../../helpers/metadata-helpers/custom-metadata-helper.js";
import { transformCustomMetadataForItemCreation } from "../../../helpers/metadata-helpers/data-transformatter-helper.js";
import { handleErrors } from "../../../store/actions-helpers/handle-errors.js";
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";

export default {
    name: "NewFolderModal",
    components: {
        FolderGlobalMetadataForCreate,
        CreationModalPermissionsSection,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
    },
    data() {
        return {
            item: {},
            is_loading: false,
            is_displayed: false,
            modal: null,
            parent: {},
            metadata: null,
        };
    },
    computed: {
        ...mapState(["current_folder", "project_ugroups"]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Create folder");
        },
        modal_title() {
            return this.$gettext("New folder");
        },
        aria_labelled_by() {
            return "document-new-item-modal";
        },
    },
    mounted() {
        this.item = this.getDefaultItem();
        this.modal = createModal(this.$el);
        EventBus.$on("show-new-folder-modal", this.show);
        EventBus.$on("update-multiple-metadata-list-value", this.updateMultipleMetadataListValue);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
    },
    beforeDestroy() {
        EventBus.$off("show-new-folder-modal", this.show);
        EventBus.$off("update-multiple-metadata-list-value", this.updateMultipleMetadataListValue);
        this.modal.removeEventListener("tlp-modal-hidden", this.reset);
    },
    methods: {
        getDefaultItem() {
            return {
                title: "",
                description: "",
                type: TYPE_FOLDER,
                permissions_for_groups: {
                    can_read: [],
                    can_write: [],
                    can_manage: [],
                },
            };
        },
        async show(event) {
            this.item = this.getDefaultItem();
            this.parent = event.detail.parent;
            this.addParentMetadataToDefaultItem();
            this.item.permissions_for_groups = JSON.parse(
                JSON.stringify(this.parent.permissions_for_groups)
            );
            this.is_displayed = true;
            this.modal.show();
            try {
                await this.$store.dispatch("loadProjectUserGroupsIfNeeded");
            } catch (e) {
                await handleErrors(this.$store, e);
                this.modal.hide();
            }
        },
        reset() {
            this.$store.commit("error/resetModalError");
            this.is_displayed = false;
            this.is_loading = false;
        },
        async addFolder(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            await this.$store.dispatch("createNewItem", [
                this.item,
                this.parent,
                this.current_folder,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
        addParentMetadataToDefaultItem() {
            const parent_metadata = getCustomMetadata(this.parent.metadata);

            const formatted_metadata = transformCustomMetadataForItemCreation(parent_metadata);
            if (formatted_metadata.length > 0) {
                this.item.metadata = formatted_metadata;
            }
        },
        updateMultipleMetadataListValue(event) {
            if (!this.item.metadata) {
                return;
            }
            const item_metadata = this.item.metadata.find(
                (metadata) => metadata.short_name === event.detail.id
            );
            item_metadata.list_value = event.detail.value;
        },
    },
};
</script>
