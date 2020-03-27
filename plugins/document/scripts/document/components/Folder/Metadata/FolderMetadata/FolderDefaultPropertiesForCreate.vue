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
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div v-if="has_recursion_metadata" data-test="document-folder-default-properties-container">
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_metadata"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-folder-default-properties-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Default properties</h2>
        </div>
        <template v-if="has_loaded_metadata">
            <p v-translate data-test="document-folder-default-properties">
                All the properties values that you define here will be proposed as default values
                for the items that will be created within this folder.
            </p>
            <status-metadata-with-custom-binding-for-folder-create
                v-bind:currently-updated-item="currentlyUpdatedItem"
                v-bind:parent="parent"
            />
            <custom-metadata v-bind:item-metadata="currentlyUpdatedItem.metadata" />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import StatusMetadataWithCustomBindingForFolderCreate from "./StatusMetadataWithCustomBindingForFolderCreate.vue";
import CustomMetadata from "../CustomMetadata/CustomMetadata.vue";

export default {
    name: "FolderDefaultPropertiesForCreate",
    components: { CustomMetadata, StatusMetadataWithCustomBindingForFolderCreate },
    props: {
        currentlyUpdatedItem: Object,
        parent: Object,
    },
    computed: {
        ...mapState(["is_item_status_metadata_used"]),
        ...mapState("metadata", ["has_loaded_metadata"]),
        has_recursion_metadata() {
            return (
                this.is_item_status_metadata_used === true ||
                (this.currentlyUpdatedItem.metadata &&
                    this.currentlyUpdatedItem.metadata.length > 0)
            );
        },
    },
    mounted() {
        if (!this.has_loaded_metadata) {
            this.$store.dispatch("metadata/loadProjectMetadata", [this.$store]);
        }
    },
};
</script>
