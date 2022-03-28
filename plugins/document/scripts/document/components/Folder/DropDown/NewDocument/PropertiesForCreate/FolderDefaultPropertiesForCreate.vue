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
    <div v-if="has_recursion_property" data-test="document-folder-default-properties-container">
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_properties"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-folder-default-properties-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Default properties</h2>
        </div>
        <template v-if="has_loaded_properties">
            <p v-translate data-test="document-folder-default-properties">
                All the properties values that you define here will be proposed as default values
                for the items that will be created within this folder.
            </p>
            <status-property-with-custom-binding-for-folder-create
                v-bind:status_value="status_value"
            />
            <custom-property v-bind:item-property="properties" />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import StatusPropertyWithCustomBindingForFolderCreate from "./StatusPropertyWithCustomBindingForFolderCreate.vue";
import CustomProperty from "../../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";

export default {
    name: "FolderDefaultPropertiesForCreate",
    components: { CustomProperty, StatusPropertyWithCustomBindingForFolderCreate },
    props: {
        status_value: String,
        properties: Array,
    },
    computed: {
        ...mapState("configuration", ["is_status_property_used"]),
        ...mapState("properties", ["has_loaded_properties"]),
        has_recursion_property() {
            return (
                this.is_status_property_used === true ||
                (this.properties && this.properties.length > 0)
            );
        },
    },
    mounted() {
        if (!this.has_loaded_properties) {
            this.$store.dispatch("properties/loadProjectProperties");
        }
    },
};
</script>
