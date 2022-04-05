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
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit="updateProperties"
    >
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by" />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <folder-global-property-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:parent="current_folder"
                v-bind:item-property="formatted_item_properties"
                v-bind:status_value="item_to_update.status.value"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-pencil'"
        />
    </form>
</template>

<script>
import { createModal } from "tlp";
import { mapState } from "vuex";
import { sprintf } from "sprintf-js";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import FolderGlobalPropertyForUpdate from "./FolderGlobalPropertyForUpdate.vue";
import {
    transformCustomPropertiesForItemUpdate,
    transformFolderPropertiesForRecursionAtUpdate,
} from "../../../../helpers/properties-helpers/update-data-transformatter-helper";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import emitter from "../../../../helpers/emitter";

export default {
    name: "UpdateFolderPropertiesModal",
    components: {
        FolderGlobalPropertyForUpdate,
        ModalFeedback,
        ModalHeader,
        ModalFooter,
    },
    props: {
        item: Object,
    },
    data() {
        return {
            item_to_update: {},
            is_loading: false,
            modal: null,
            recursion_option: "none",
            properties_to_update: [],
            formatted_item_properties: [],
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("configuration", ["project_id"]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Update properties");
        },
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" properties'), this.item.title);
        },
        aria_labelled_by() {
            return "document-update-folder-properties-modal";
        },
    },
    beforeMount() {
        this.item_to_update = transformFolderPropertiesForRecursionAtUpdate(this.item);
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.formatted_item_properties = getCustomProperties(this.item.properties);

        transformCustomPropertiesForItemUpdate(this.formatted_item_properties);

        this.show();
    },
    created() {
        emitter.on("properties-recursion-list", this.setPropertiesListUpdate);
        emitter.on("properties-recursion-option", this.setRecursionOption);
        emitter.on("update-multiple-properties-list-value", this.updateMultiplePropertiesListValue);
        emitter.on("update-status-property", this.updateStatusValue);
        emitter.on("update-title-property", this.updateTitleValue);
        emitter.on("update-description-property", this.updateDescriptionValue);
    },
    beforeDestroy() {
        emitter.off("properties-recursion-list", this.show);
        emitter.off("properties-recursion-option", this.show);
        emitter.off(
            "update-multiple-properties-list-value",
            this.updateMultiplePropertiesListValue
        );
        emitter.off("update-status-property", this.updateStatusValue);
        emitter.off("update-title-property", this.updateTitleValue);
        emitter.off("update-description-property", this.updateDescriptionValue);
    },
    methods: {
        show() {
            this.modal.show();
        },
        async updateProperties(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");
            this.item_to_update.properties = this.formatted_item_properties;
            await this.$store.dispatch("properties/updateFolderProperties", {
                item: this.item,
                item_to_update: this.item_to_update,
                current_folder: this.current_folder,
                properties_to_update: this.properties_to_update,
                recursion_option: this.recursion_option,
            });
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
        setPropertiesListUpdate(event) {
            this.properties_to_update = event.detail.property_list;
        },
        setRecursionOption(event) {
            this.recursion_option = event.detail.recursion_option;
        },
        updateMultiplePropertiesListValue(event) {
            if (!this.formatted_item_properties) {
                return;
            }
            const item_properties = this.formatted_item_properties.find(
                (property) => property.short_name === event.detail.id
            );
            item_properties.list_value = event.detail.value;
        },
        updateStatusValue(status) {
            this.item_to_update.status.value = status;
        },
        updateTitleValue(title) {
            this.item_to_update.title = title;
        },
        updateDescriptionValue(description) {
            this.item_to_update.description = description;
        },
    },
};
</script>
