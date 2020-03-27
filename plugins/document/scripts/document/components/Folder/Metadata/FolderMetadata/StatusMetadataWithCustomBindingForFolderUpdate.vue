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
    <status-metadata
        v-model="status_value"
        v-if="is_item_status_metadata_used"
        data-test="document-status-metadata-for-folder-update"
    />
</template>

<script>
import { mapState } from "vuex";
import StatusMetadata from "../StatusMetadata.vue";
import { getStatusIdFromName } from "../../../../helpers/metadata-helpers/hardcoded-metadata-mapping-helper.js";
import { transformFolderMetadataForRecursionAtUpdate } from "../../../../helpers/metadata-helpers/data-transformatter-helper.js";

export default {
    name: "StatusMetadataWithCustomBindingForFolderUpdate",
    components: {
        StatusMetadata,
    },
    props: {
        currentlyUpdatedItem: Object,
    },
    computed: {
        ...mapState(["is_item_status_metadata_used"]),
        status_value: {
            get() {
                transformFolderMetadataForRecursionAtUpdate(
                    this.currentlyUpdatedItem,
                    this.parent,
                    this.is_item_status_metadata_used
                );
                return this.currentlyUpdatedItem.status.value;
            },
            set(value) {
                this.currentlyUpdatedItem.status.id = getStatusIdFromName(value);
                this.currentlyUpdatedItem.status.value = value;
            },
        },
    },
};
</script>
