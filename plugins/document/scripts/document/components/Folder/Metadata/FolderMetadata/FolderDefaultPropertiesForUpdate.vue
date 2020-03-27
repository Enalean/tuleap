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
            <p v-translate>
                All the properties values that you define here will be proposed as default values
                for the items that will be created within this folder.
            </p>
            <div class="document-default-metadata">
                <div class="document-metadata-container" v-if="is_item_status_metadata_used">
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fa fa-repeat"></i></label>
                        <input
                            id="status"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            value="status"
                            ref="status_input"
                        />
                    </div>
                    <status-metadata-with-custom-binding-for-folder-update
                        v-bind:currently-updated-item="currentlyUpdatedItem"
                    />
                </div>
                <div
                    v-for="custom in itemMetadata"
                    v-bind:key="custom.short_name"
                    class="document-metadata-container"
                >
                    <div class="document-recursion-checkbox-container">
                        <label class="tlp-label"><i class="fa fa-repeat"></i></label>
                        <input
                            v-bind:id="custom.short_name"
                            type="checkbox"
                            class="document-recursion-checkbox"
                            v-on:change="updateMetadataListWithRecursion"
                            v-model="metadata_list_to_update"
                            v-bind:value="custom.short_name"
                            data-test="document-custom-metadata-checkbox"
                        />
                    </div>
                    <custom-metadata-component-type-renderer v-bind:item-metadata="custom" />
                </div>
                <recursion-options
                    v-model="recursion_option"
                    v-on:input="updateRecursionOption"
                    data-test="document-custom-metadata-recursion-option"
                />
            </div>
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import StatusMetadataWithCustomBindingForFolderUpdate from "./StatusMetadataWithCustomBindingForFolderUpdate.vue";
import EventBus from "../../../../helpers/event-bus.js";
import CustomMetadataComponentTypeRenderer from "../CustomMetadata/CustomMetadataComponentTypeRenderer.vue";
import RecursionOptions from "./RecursionOptions.vue";

export default {
    name: "FolderDefaultPropertiesForUpdate",
    components: {
        RecursionOptions,
        CustomMetadataComponentTypeRenderer,
        StatusMetadataWithCustomBindingForFolderUpdate,
    },
    props: {
        currentlyUpdatedItem: Object,
        itemMetadata: Array,
    },
    data() {
        return {
            metadata_list_to_update: [],
            recursion: "none",
        };
    },
    computed: {
        ...mapState(["is_item_status_metadata_used"]),
        ...mapState("metadata", ["has_loaded_metadata"]),
        recursion_option: {
            get() {
                return "";
            },
            set(value) {
                this.currentlyUpdatedItem.status.recursion = value;
                this.recursion = value;
            },
        },
        has_recursion_metadata() {
            return this.is_item_status_metadata_used || this.itemMetadata.length > 0;
        },
    },
    mounted() {
        if (!this.has_loaded_metadata) {
            this.$store.dispatch("metadata/loadProjectMetadata", [this.$store]);
        }
    },
    methods: {
        updateMetadataListWithRecursion() {
            EventBus.$emit("metadata-recursion-metadata-list", {
                detail: { metadata_list: this.metadata_list_to_update },
            });
        },
        updateRecursionOption() {
            this.metadata_list_to_update = [];
            if (this.recursion !== "none") {
                this.itemMetadata.forEach((metadata) => {
                    this.metadata_list_to_update.push(metadata.short_name);
                });
                if (this.is_item_status_metadata_used) {
                    this.metadata_list_to_update.push("status");
                    this.$refs.status_input.checked = true;
                }
            } else {
                this.$refs.status_input.checked = false;
            }
            this.updateMetadataListWithRecursion();
            EventBus.$emit("metadata-recursion-option", {
                detail: { recursion_option: this.recursion },
            });
        },
    },
};
</script>
