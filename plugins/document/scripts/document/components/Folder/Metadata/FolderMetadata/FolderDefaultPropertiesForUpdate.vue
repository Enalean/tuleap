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
        <hr class="tlp-modal-separator">
        <h2 class="tlp-modal-subtitle" v-translate>Default properties</h2>
        <p v-translate>All the properties values that you define here will be proposed as default values for the items that will be created within this folder.</p>
        <div class="document-default-metadata">
            <div class="document-metadata-container">
                <div class="document-recursion-checkbox-container">
                    <label class="tlp-label"><i class="fa fa-repeat"></i></label>
                    <input type="checkbox" name="apply-recursive-status" class="document-recursion-checkbox" v-on:click="shouldApplyRecursion = true">
                </div>
                <status-metadata-with-custom-binding-for-folder-update v-bind:currently-updated-item="currentlyUpdatedItem"/>
            </div>
            <recursion-options v-bind:should-apply-recursion="shouldApplyRecursion" v-model="recursion_option"/>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";
import RecursionOptions from "./RecursionOptions.vue";
import StatusMetadataWithCustomBindingForFolderUpdate from "./StatusMetadataWithCustomBindingForFolderUpdate.vue";

export default {
    name: "FolderDefaultPropertiesForUpdate",
    components: {
        StatusMetadataWithCustomBindingForFolderUpdate,
        RecursionOptions
    },
    props: {
        currentlyUpdatedItem: Object
    },
    data() {
        return {
            shouldApplyRecursion: false
        };
    },
    computed: {
        ...mapState(["is_item_status_metadata_used"]),
        recursion_option: {
            get() {
                return "";
            },
            set(value) {
                this.currentlyUpdatedItem.status.recursion = value;
            }
        },
        has_recursion_metadata() {
            return this.is_item_status_metadata_used;
        }
    }
};
</script>
