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
    <form class="tlp-modal" role="dialog" v-bind:aria-labelled-by="aria_labelled_by" v-on:submit="updateMetadata">
        <modal-header v-bind:modal-title="modal_title" v-bind:aria-labelled-by="aria_labelled_by"/>
        <modal-feedback/>
        <div class="tlp-modal-body document-item-modal-body">
            <info-access-old-properties-page v-bind:project-id="project_id" v-bind:item-id="item_to_update.id"/>
            <document-global-metadata-for-update v-bind:parent="current_folder" v-bind:currently-updated-item="item_to_update">
                <owner-metadata v-bind:currently-updated-item="item_to_update"/>
            </document-global-metadata-for-update>

            <other-information-metadata v-bind:currently-updated-item="item_to_update">
                <obsolescence-date-metadata-obsolete-today-option/>
            </other-information-metadata>

            <modal-footer v-bind:is-loading="is_loading"
                          v-bind:submit-button-label="submit_button_label"
                          v-bind:aria-labelled-by="aria_labelled_by"
            />
        </div>
    </form>
</template>

<script>
import { modal as createModal } from "tlp";
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import DocumentGlobalMetadataForUpdate from "../Metadata/DocumentMetadata/DocumentGlobalMetadataForUpdate.vue";
import OtherInformationMetadata from "../Metadata/DocumentMetadata/OtherInformationMetadata.vue";
import OwnerMetadata from "../Metadata/OwnerMetadata.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import InfoAccessOldPropertiesPage from "./InfoAccessOldPropertiesPage.vue";
import ObsolescenceDateMetadataObsoleteTodayOption from "../Metadata/DocumentMetadata/ObsolescenceDateMetadataObsoleteTodayOption.vue";

export default {
    components: {
        ObsolescenceDateMetadataObsoleteTodayOption,
        InfoAccessOldPropertiesPage,
        ModalFeedback,
        OwnerMetadata,
        OtherInformationMetadata,
        DocumentGlobalMetadataForUpdate,
        ModalHeader,
        ModalFooter
    },
    props: {
        item: Object
    },
    data() {
        return {
            item_to_update: {},
            is_loading: false,
            modal: null
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
        }
    },
    beforeMount() {
        this.item_to_update = JSON.parse(JSON.stringify(this.item));
    },
    mounted() {
        this.modal = createModal(this.$el);

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
            await this.$store.dispatch("updateMetadata", [this.item, this.item_to_update]);
            this.is_loading = false;
            if (this.has_modal_error === false) {
                this.modal.hide();
            }
        }
    }
};
</script>
