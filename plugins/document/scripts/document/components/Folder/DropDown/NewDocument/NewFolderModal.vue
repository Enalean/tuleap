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
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body" v-if="is_displayed">
            <folder-global-properties-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
                v-bind:properties="item.properties"
                v-bind:status_value="item.status ? item.status : ''"
            />
            <creation-modal-permissions-section
                v-if="item.permissions_for_groups"
                v-model="item.permissions_for_groups"
                v-bind:value="item.permissions_for_groups"
                v-bind:project_ugroups="project_ugroups"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-plus'"
            data-test="document-modal-submit-button-create-folder"
        />
    </form>
</template>

<script>
import { mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import { TYPE_FOLDER } from "../../../../constants";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import FolderGlobalPropertiesForCreate from "./PropertiesForCreate/FolderGlobalPropertiesForCreate.vue";
import CreationModalPermissionsSection from "./CreationModalPermissionsSection.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import { handleErrors } from "../../../../store/actions-helpers/handle-errors";
import {
    transformCustomPropertiesForItemCreation,
    transformStatusPropertyForItemCreation,
} from "../../../../helpers/properties-helpers/creation-data-transformatter-helper";
import emitter from "../../../../helpers/emitter";

export default {
    name: "NewFolderModal",
    components: {
        FolderGlobalPropertiesForCreate,
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
            properties: null,
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("error", ["has_modal_error"]),
        ...mapState("permissions", ["project_ugroups"]),
        ...mapState("configuration", ["project_id", "is_status_property_used"]),
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
        this.modal = createModal(this.$el);
        emitter.on("show-new-folder-modal", this.show);
        emitter.on("update-multiple-properties-list-value", this.updateMultiplePropertiesListValue);
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        emitter.on("update-status-property", this.updateStatusValue);
        emitter.on("update-title-property", this.updateTitleValue);
        emitter.on("update-description-property", this.updateDescriptionValue);
        emitter.on("update-custom-property", this.updateCustomProperty);
    },
    beforeUnmount() {
        emitter.off("show-new-folder-modal", this.show);
        emitter.off(
            "update-multiple-properties-list-value",
            this.updateMultiplePropertiesListValue
        );
        this.modal.removeEventListener("tlp-modal-hidden", this.reset);
        emitter.off("update-status-property", this.updateStatusValue);
        emitter.off("update-title-property", this.updateTitleValue);
        emitter.off("update-description-property", this.updateDescriptionValue);
        emitter.off("update-custom-property", this.updateCustomProperty);
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
                properties: [],
            };
        },
        async show(event) {
            this.item = this.getDefaultItem();
            this.parent = event.detail.parent;
            this.addParentPropertiesToDefaultItem();
            this.item.permissions_for_groups = JSON.parse(
                JSON.stringify(this.parent.permissions_for_groups)
            );
            this.is_displayed = true;
            this.modal.show();
            try {
                await this.$store.dispatch(
                    "permissions/loadProjectUserGroupsIfNeeded",
                    this.project_id
                );
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
        addParentPropertiesToDefaultItem() {
            const parent_properties = getCustomProperties(this.parent);

            const formatted_properties =
                transformCustomPropertiesForItemCreation(parent_properties);
            if (formatted_properties.length > 0) {
                this.item.properties = formatted_properties;
            }

            transformStatusPropertyForItemCreation(
                this.item,
                this.parent,
                this.is_status_property_used
            );
        },
        updateMultiplePropertiesListValue(event) {
            if (!this.item.properties) {
                return;
            }
            const item_properties = this.item.properties.find(
                (property) => property.short_name === event.detail.id
            );
            item_properties.list_value = event.detail.value;
        },
        updateStatusValue(status) {
            this.item.status = status;
        },
        updateTitleValue(title) {
            this.item.title = title;
        },
        updateDescriptionValue(description) {
            this.item.description = description;
        },
        updateCustomProperty(event) {
            if (!this.item.properties) {
                return;
            }
            const item_properties = this.item.properties.find(
                (property) => property.short_name === event.property_short_name
            );

            if (!item_properties) {
                return;
            }
            item_properties.value = event.value;
        },
    },
};
</script>
