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
        v-if="has_properties_to_create"
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
            <obsolescence-date-property-for-create
                v-if="is_obsolescence_date_property_used"
                v-model="obsolescence_date_value"
            />
            <custom-property v-bind:item-property="currentlyUpdatedItem.properties" />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import ObsolescenceDatePropertyForCreate from "./ObsolescenceDatePropertyForCreate.vue";
import CustomProperty from "../../PropertiesForCreateOrUpdate/CustomProperties/CustomProperty.vue";

export default {
    name: "OtherInformationPropertiesForCreate",
    components: {
        CustomProperty,
        ObsolescenceDatePropertyForCreate,
    },
    props: {
        currentlyUpdatedItem: Object,
        value: {
            required: true,
            type: String,
        },
    },
    computed: {
        ...mapState("configuration", ["is_obsolescence_date_property_used"]),
        ...mapState("properties", ["has_loaded_properties"]),
        has_properties_to_create() {
            return (
                this.is_obsolescence_date_property_used ||
                this.currentlyUpdatedItem.properties !== null
            );
        },
        obsolescence_date_value: {
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
