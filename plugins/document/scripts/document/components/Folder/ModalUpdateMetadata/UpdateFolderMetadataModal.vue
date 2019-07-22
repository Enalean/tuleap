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
            <folder-global-metadata-for-update v-bind:currently-updated-item="item_to_update"
                                               v-bind:parent="current_folder"
            />
        </div>
        <modal-footer v-bind:is-loading="is_loading"
                      v-bind:submit-button-label="submit_button_label"
                      v-bind:aria-labelled-by="aria_labelled_by"
        />
    </form>
</template>

<script>
import { modal as createModal } from "tlp";
import { mapState } from "vuex";
import { sprintf } from "sprintf-js";
import ModalHeader from "../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../ModalCommon/ModalFooter.vue";
import InfoAccessOldPropertiesPage from "./InfoAccessOldPropertiesPage.vue";
import FolderGlobalMetadataForUpdate from "../Metadata/FolderMetadata/FolderGlobalMetadataForUpdate.vue";
import { transformFolderMetadataForRecursionAtUpdate } from "../../../helpers/metadata-helpers/data-transformatter-helper.js";

export default {
    name: "UpdateFolderMetadataModal",
    components: {
        FolderGlobalMetadataForUpdate,
        InfoAccessOldPropertiesPage,
        ModalFeedback,
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
            return "document-update-folder-metadata-modal";
        }
    },
    beforeMount() {
        this.item_to_update = transformFolderMetadataForRecursionAtUpdate(this.item);
    },
    mounted() {
        this.modal = createModal(this.$el);

        this.registerEvents();

        this.show();
    },
    methods: {
        show() {
            this.modal.show();
        },
        registerEvents() {
            this.modal.addEventListener("tlp-modal-hidden", this.reset);
        },
        reset() {
            this.modal.hide();
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
