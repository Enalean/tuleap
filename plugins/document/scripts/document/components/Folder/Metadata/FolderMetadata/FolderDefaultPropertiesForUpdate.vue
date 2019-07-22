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
    <div>
        <hr class="tlp-modal-separator">
        <h2 class="tlp-modal-subtitle" v-translate>Other information</h2>
        <div class="document-status-section-container">
            <input type="checkbox" name="apply-recursive-status" class="document-recursion-checkbox" v-on:click="shouldApplyRecursion = true">
            <status-metadata-with-custom-binding-for-folder-update v-bind:currently-updated-item="currentlyUpdatedItem"/>
        </div>
        <recursion-options v-bind:should-apply-recursion="shouldApplyRecursion" v-model="recursion_option"/>
    </div>
</template>

<script>
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
        recursion_option: {
            get() {
                return "";
            },
            set(value) {
                this.currentlyUpdatedItem.status.recursion = value;
            }
        }
    }
};
</script>
