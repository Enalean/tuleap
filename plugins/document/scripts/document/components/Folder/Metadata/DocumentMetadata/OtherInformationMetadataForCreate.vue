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
        class="document-metadata"
        v-if="has_metadata_to_create"
        data-test="document-other-information"
    >
        <hr class="tlp-modal-separator" />
        <div class="document-modal-other-information-title-container">
            <div
                v-if="!has_loaded_metadata"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-other-information-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Other information</h2>
        </div>
        <template v-if="has_loaded_metadata">
            <obsolescence-date-metadata-for-create
                v-if="is_obsolescence_date_metadata_used"
                v-model="obsolescence_date_value"
            />
            <custom-metadata v-bind:item-metadata="currentlyUpdatedItem.metadata" />
        </template>
    </div>
</template>

<script>
import { mapState } from "vuex";
import ObsolescenceDateMetadataForCreate from "../ObsolescenceMetadata/ObsolescenceDateMetadataForCreate.vue";
import CustomMetadata from "../CustomMetadata/CustomMetadata.vue";

export default {
    name: "OtherInformationMetadataForCreate",
    components: {
        CustomMetadata,
        ObsolescenceDateMetadataForCreate,
    },
    props: {
        currentlyUpdatedItem: Object,
        value: {
            required: true,
            type: String,
        },
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"]),
        ...mapState("metadata", ["has_loaded_metadata"]),
        has_metadata_to_create() {
            return (
                this.is_obsolescence_date_metadata_used ||
                this.currentlyUpdatedItem.metadata !== null
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
        if (!this.has_loaded_metadata) {
            this.$store.dispatch("metadata/loadProjectMetadata", [this.$store]);
        }
    },
};
</script>
