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

<!-- eslint-disable vue/no-mutating-props -->
<template>
    <div class="document-properties">
        <div class="document-properties-margin">
            <title-property
                v-model="currentlyUpdatedItem.title"
                v-bind:currently-updated-item="currentlyUpdatedItem"
                v-bind:parent="parent"
                v-bind:is-in-update-context="true"
            />
        </div>
        <description-property v-model="currentlyUpdatedItem.description" />
        <folder-default-properties-for-update
            v-bind:currently-updated-item="currentlyUpdatedItem"
            v-bind:item-property="itemProperty"
        />
    </div>
</template>

<script>
import FolderDefaultPropertiesForUpdate from "./FolderDefaultPropertiesForUpdate.vue";
import DescriptionProperty from "../PropertiesForCreateOrUpdate/AlwaysThereProperties/DescriptionProperty.vue";
import TitleProperty from "../PropertiesForCreateOrUpdate/AlwaysThereProperties/TitleProperty.vue";

export default {
    name: "FolderGlobalPropertyForUpdate",
    components: {
        TitleProperty,
        DescriptionProperty,
        FolderDefaultPropertiesForUpdate,
    },
    props: {
        currentlyUpdatedItem: Object,
        parent: Object,
        itemProperty: Array,
    },
};
</script>
