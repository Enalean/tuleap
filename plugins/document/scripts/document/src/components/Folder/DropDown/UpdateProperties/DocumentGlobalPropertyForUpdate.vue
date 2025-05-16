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
    <div class="document-properties">
        <div class="document-properties-title-and-status-container">
            <title-property
                v-bind:value="currentlyUpdatedItem.title"
                v-bind:currently-updated-item="currentlyUpdatedItem"
                v-bind:parent="parent"
                v-bind:is-in-update-context="true"
            />
            <status-property-with-custom-binding-for-document-update
                v-bind:status_value="status_value"
            />
        </div>
        <description-property v-bind:value="currentlyUpdatedItem.description" />
        <owner-property v-bind:value="currentlyUpdatedItem.owner" />
    </div>
</template>

<script setup lang="ts">
import StatusPropertyWithCustomBindingForDocumentUpdate from "./StatusPropertyWithCustomBindingForDocumentUpdate.vue";
import TitleProperty from "../PropertiesForCreateOrUpdate/AlwaysThereProperties/TitleProperty.vue";
import DescriptionProperty from "../PropertiesForCreateOrUpdate/AlwaysThereProperties/DescriptionProperty.vue";
import OwnerProperty from "../PropertiesForCreateOrUpdate/AlwaysThereProperties/OwnerProperty.vue";
import type { Folder, Item } from "../../../../type";

defineProps<{ currentlyUpdatedItem: Item; parent: Folder; status_value: string }>();
</script>
