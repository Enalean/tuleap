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
        v-on:submit="updateMetadata"
    >
        <modal-header
            v-bind:modal-title="modal_title"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-pencil'"
        />
        <modal-feedback />
        <div class="tlp-modal-body document-item-modal-body">
            <document-global-metadata-for-update
                v-bind:parent="current_folder"
                v-bind:currently-updated-item="item_to_update"
            >
                <owner-metadata v-bind:currently-updated-item="item_to_update" />
            </document-global-metadata-for-update>

            <other-information-metadata-for-update
                v-bind:currently-updated-item="item_to_update"
                v-bind:metadata-to-update="formatted_item_metadata"
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
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import DocumentGlobalMetadataForUpdate from "../Metadata/DocumentMetadata/DocumentGlobalMetadataForUpdate.vue";
import OtherInformationMetadataForUpdate from "../Metadata/DocumentMetadata/OtherInformationMetadataForUpdate.vue";
import OwnerMetadata from "../Metadata/OwnerMetadata.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import EventBus from "../../../helpers/event-bus.js";
import { getCustomMetadata } from "../../../helpers/metadata-helpers/custom-metadata-helper.js";
import { transformCustomMetadataForItemUpdate } from "../../../helpers/metadata-helpers/data-transformatter-helper.js";

export default {
    components: {
        ModalFeedback,
        OwnerMetadata,
        OtherInformationMetadataForUpdate,
        DocumentGlobalMetadataForUpdate,
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
            formatted_item_metadata: [],
        };
    },
    computed: {
        ...mapState(["current_folder", "project_id"]),
        ...mapState("error", ["has_modal_error"]),
        submit_button_label() {
            return this.$gettext("Update properties");
        },
        modal_title() {
            return sprintf(this.$gettext('Edit "%s" properties'), this.item.title);
        },
        aria_labelled_by() {
            return "document-update-file-metadata-modal";
        },
        obsolescence_date_value: {
            get() {
                if (!this.item.metadata) {
                    return "";
                }

                const obsolescence_date = this.item.metadata.find(
                    (metadata) => metadata.short_name === "obsolescence_date"
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
        EventBus.$on("update-multiple-metadata-list-value", this.updateMultipleMetadataListValue);
    },
    beforeDestroy() {
        EventBus.$off("update-multiple-metadata-list-value", this.updateMultipleMetadataListValue);
    },
    beforeMount() {
        this.item_to_update = JSON.parse(JSON.stringify(this.item));
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.formatted_item_metadata = getCustomMetadata(this.item.metadata);
        transformCustomMetadataForItemUpdate(this.formatted_item_metadata);

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
            this.$emit("update-metadata-modal-closed");
        },
        async updateMetadata(event) {
            event.preventDefault();
            this.is_loading = true;
            this.$store.commit("error/resetModalError");

            this.item_to_update.metadata = this.formatted_item_metadata;
            await this.$store.dispatch("updateMetadata", [
                this.item,
                this.item_to_update,
                this.current_folder,
            ]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        },
        updateMultipleMetadataListValue(event) {
            if (!this.formatted_item_metadata) {
                return;
            }
            const item_metadata = this.formatted_item_metadata.find(
                (metadata) => metadata.short_name === event.detail.id
            );
            item_metadata.list_value = event.detail.value;
        },
    },
};
</script>
