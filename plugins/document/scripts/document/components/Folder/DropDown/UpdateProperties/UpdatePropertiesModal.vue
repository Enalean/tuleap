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
            <document-global-property-for-update
                v-bind:parent="current_folder"
                v-bind:currently-updated-item="item_to_update"
                v-bind:status_value="item_to_update.status"
            />
            <other-information-properties-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:property-to-update="formatted_item_properties"
                v-bind:value="obsolescence_date_value"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="submit_button_label"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-submit-button-class="'fa-solid fa-pencil'"
            data-test="document-modal-submit-update-properties"
        />
    </form>
</template>

<script>
import { createModal } from "@tuleap/tlp-modal";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import DocumentGlobalPropertyForUpdate from "./DocumentGlobalPropertyForUpdate.vue";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import {
    transformCustomPropertiesForItemUpdate,
    transformDocumentPropertiesForUpdate,
} from "../../../../helpers/properties-helpers/update-data-transformatter-helper";
import emitter from "../../../../helpers/emitter";

export default {
    components: {
        ModalFeedback,
        OtherInformationPropertiesForUpdate,
        DocumentGlobalPropertyForUpdate,
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
            formatted_item_properties: [],
        };
    },
    computed: {
        ...mapState(["current_folder"]),
        ...mapState("configuration", ["project_id", "is_status_property_used"]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Update properties");
        },
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" properties'), this.item.title);
        },
        aria_labelled_by() {
            return "document-update-file-properties-modal";
        },
        obsolescence_date_value() {
            if (!this.item.properties) {
                return "";
            }

            const obsolescence_date = this.item.properties.find(
                (property) => property.short_name === "obsolescence_date"
            );

            if (!obsolescence_date || !obsolescence_date.value) {
                return "";
            }
            return obsolescence_date.value;
        },
    },
    created() {
        emitter.on("update-multiple-properties-list-value", this.updateMultiplePropertiesListValue);
        emitter.on("update-status-property", this.updateStatusValue);
        emitter.on("update-title-property", this.updateTitleValue);
        emitter.on("update-description-property", this.updateDescriptionValue);
        emitter.on("update-owner-property", this.updateOwnerValue);
        emitter.on("update-custom-property", this.updateCustomProperty);
        emitter.on("update-obsolescence-date-property", this.updateObsolescenceDateProperty);
    },
    beforeUnmount() {
        emitter.off(
            "update-multiple-properties-list-value",
            this.updateMultiplePropertiesListValue
        );
        emitter.off("update-status-property", this.updateStatusValue);
        emitter.off("update-title-property", this.updateTitleValue);
        emitter.off("update-description-property", this.updateDescriptionValue);
        emitter.off("update-owner-property", this.updateOwnerValue);
        emitter.off("update-custom-property", this.updateCustomProperty);
        emitter.off("update-obsolescence-date-property", this.updateObsolescenceDateProperty);
    },
    beforeMount() {
        this.item_to_update = JSON.parse(JSON.stringify(this.item));
        transformDocumentPropertiesForUpdate(this.item_to_update, this.is_status_property_used);
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.formatted_item_properties = getCustomProperties(this.item_to_update.properties);
        transformCustomPropertiesForItemUpdate(this.formatted_item_properties);

        this.registerEvents();

        this.show();
    },
    methods: {
        show() {
            this.is_displayed = true;
            this.modal.show();
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        reset() {
            this.is_displayed = false;
            this.$emit("update-properties-modal-closed");
        },
        async updateProperties(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            this.item_to_update.properties = this.formatted_item_properties;
            await this.$store.dispatch("properties/updateProperties", {
                item: this.item,
                item_to_update: this.item_to_update,
                current_folder: this.current_folder,
            });
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
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
            this.item_to_update.status = status;
        },
        updateTitleValue(title) {
            this.item_to_update.title = title;
        },
        updateDescriptionValue(description) {
            this.item_to_update.description = description;
        },
        updateOwnerValue(owner) {
            this.item_to_update.owner.id = owner;
        },
        updateCustomProperty(event) {
            if (!this.formatted_item_properties) {
                return;
            }
            const item_properties = this.formatted_item_properties.find(
                (property) => property.short_name === event.property_short_name
            );
            item_properties.value = event.value;
        },
        updateObsolescenceDateProperty(event) {
            this.item_to_update.obsolescence_date = event;
        },
    },
};
</script>
