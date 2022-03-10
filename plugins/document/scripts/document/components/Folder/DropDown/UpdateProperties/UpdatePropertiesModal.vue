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
            />
            <other-information-properties-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:property-to-update="formatted_item_properties"
                v-model="obsolescence_date_value"
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
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import DocumentGlobalPropertyForUpdate from "./DocumentGlobalPropertyForUpdate.vue";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { getCustomProperties } from "../../../../helpers/properties-helpers/custom-properties-helper";
import { transformCustomPropertiesForItemUpdate } from "../../../../helpers/properties-helpers/update-data-transformatter-helper";
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
        ...mapState("configuration", ["project_id"]),
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
        obsolescence_date_value: {
            get() {
                if (!this.item.metadata) {
                    return "";
                }

                const obsolescence_date = this.item.metadata.find(
                    (property) => property.short_name === "obsolescence_date"
                );

                if (!obsolescence_date) {
                    return "";
                }

                if (!obsolescence_date.value) {
                    return "";
                }
                return obsolescence_date.value;
            },
            set(value) {
                this.item_to_update.obsolescence_date = value;
            },
        },
    },
    created() {
        emitter.on("update-multiple-properties-list-value", this.updateMultiplePropertiesListValue);
    },
    beforeDestroy() {
        emitter.off(
            "update-multiple-properties-list-value",
            this.updateMultiplePropertiesListValue
        );
    },
    beforeMount() {
        this.item_to_update = JSON.parse(JSON.stringify(this.item));
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.formatted_item_properties = getCustomProperties(this.item.metadata);
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

            this.item_to_update.metadata = this.formatted_item_properties;
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
    },
};
</script>
