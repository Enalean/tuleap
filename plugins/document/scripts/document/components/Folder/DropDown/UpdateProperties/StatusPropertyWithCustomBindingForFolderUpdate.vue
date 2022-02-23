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
    <status-property
        v-model="status_value"
        v-if="is_status_property_used"
        data-test="document-status-property-for-folder-update"
    />
</template>

<!-- eslint-disable vue/no-mutating-props -->
<script>
import { mapState } from "vuex";
import StatusProperty from "../PropertiesForCreateOrUpdate/StatusProperty.vue";
import { getStatusIdFromName } from "../../../../helpers/properties-helpers/hardcoded-properties-mapping-helper";
import { transformFolderPropertiesForRecursionAtUpdate } from "../../../../helpers/properties-helpers/update-data-transformatter-helper";

export default {
    name: "StatusPropertyWithCustomBindingForFolderUpdate",
    components: {
        StatusProperty,
    },
    props: {
        currentlyUpdatedItem: Object,
    },
    computed: {
        ...mapState("configuration", ["is_status_property_used"]),
        status_value: {
            get() {
                transformFolderPropertiesForRecursionAtUpdate(
                    this.currentlyUpdatedItem,
                    this.parent,
                    this.is_status_property_used
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
