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
        data-test="document-status-metadata-for-item-create"
    />
</template>

<script>
import { mapState } from "vuex";
import { transformItemMetadataForCreation } from "../../../../helpers/metadata-helpers/data-transformatter-helper.js";
import StatusMetadata from "../StatusMetadata.vue";

export default {
    name: "StatusMetadataWithCustomBindingForDocumentCreate",
    components: {
        StatusMetadata,
    },
    props: {
        currentlyUpdatedItem: Object,
        parent: Object,
    },
    computed: {
        ...mapState(["is_item_status_metadata_used"]),
        status_value: {
            get() {
                transformItemMetadataForCreation(
                    this.currentlyUpdatedItem,
                    this.parent,
                    this.is_item_status_metadata_used
                );
                return this.currentlyUpdatedItem.status;
            },
            set(value) {
                this.currentlyUpdatedItem.status = value;
            },
        },
    },
};
</script>
