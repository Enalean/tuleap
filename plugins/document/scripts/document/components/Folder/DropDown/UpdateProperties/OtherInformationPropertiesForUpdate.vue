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
    <div
        class="document-properties"
        v-if="should_display_other_information"
        data-test="document-other-information"
    >
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_properties"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-other-information-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Other information</h2>
        </div>
        <template v-if="has_loaded_properties">
            <obsolescence-date-property-for-update
                v-if="is_obsolescence_date_property_used"
                v-model="date_value"
            />
            <custom-property v-bind:item-property="propertyToUpdate" />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import ObsolescenceDatePropertyForUpdate from "./ObsolescenceDatePropertyForUpdate.vue";
import CustomProperty from "../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";

export default {
    name: "OtherInformationPropertiesForUpdate",
    components: {
        CustomProperty,
        ObsolescenceDatePropertyForUpdate,
    },
    props: {
        currentlyUpdatedItem: Object,
        propertyToUpdate: Array,
        value: {
            type: String,
            required: true,
        },
    },
    computed: {
        ...mapState("configuration", ["is_obsolescence_date_property_used"]),
        ...mapState("properties", ["has_loaded_properties"]),
        should_display_other_information() {
            return this.is_obsolescence_date_property_used || this.propertyToUpdate.length > 0;
        },
        date_value: {
            get() {
                return this.value;
            },
            set(value) {
                this.$emit("input", value);
            },
        },
    },
    mounted() {
        if (!this.has_loaded_properties) {
            this.$store.dispatch("properties/loadProjectProperties");
        }
    },
};
</script>
