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
        <h2 class="tlp-modal-subtitle" v-translate>Default properties</h2>
        <div class="tlp-property" v-translate>
            All the properties values that you define here will be proposed as default values for the items that will be created within this folder.
        </div>
        <status-metadata-with-custom-binding v-bind:currently-updated-item="currentlyUpdatedItem"/>
    </div>
</template>

<script>
import StatusMetadataWithCustomBinding from "../StatusMetadataWithCustomBinding.vue";
export default {
    name: "FolderDefaultPropertiesForCreate",
    components: { StatusMetadataWithCustomBinding },
    props: {
        currentlyUpdatedItem: Object
    }
};
</script>
