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
    <div class="document-metadata">
        <div class="document-metadata-properties-margin">
            <title-metadata
                v-model="currentlyUpdatedItem.title"
                v-bind:currently-updated-item="currentlyUpdatedItem"
                v-bind:parent="parent"
                v-bind:is-in-update-context="true"
            />
        </div>
        <description-metadata v-model="currentlyUpdatedItem.description" />
        <folder-default-properties-for-update
            v-bind:currently-updated-item="currentlyUpdatedItem"
            v-bind:item-metadata="itemMetadata"
        />
    </div>
</template>

<script>
import TitleMetadata from "../TitleMetadata.vue";
import DescriptionMetadata from "../DescriptionMetadata.vue";
import FolderDefaultPropertiesForUpdate from "./FolderDefaultPropertiesForUpdate.vue";

export default {
    name: "FolderGlobalMetadataForUpdate",
    components: {
        FolderDefaultPropertiesForUpdate,
        DescriptionMetadata,
        TitleMetadata,
    },
    props: {
        currentlyUpdatedItem: Object,
        parent: Object,
        itemMetadata: Array,
    },
};
</script>
