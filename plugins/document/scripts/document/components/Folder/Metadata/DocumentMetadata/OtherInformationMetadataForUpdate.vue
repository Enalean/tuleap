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
    <div class="document-metadata" v-if="should_display_other_information" data-test="document-other-information">
        <hr class="tlp-modal-separator">
        <div class="document-modal-other-information-title-container">
            <div
                v-if="! has_loaded_metadata"
                class="document-modal-other-information-title-container-spinner"
                data-test="document-other-information-spinner"
            >
                <i class="fa fa-spin fa-circle-o-notch"></i>
            </div>
            <h2 class="tlp-modal-subtitle" v-translate>Other information</h2>
        </div>
        <template v-if="has_loaded_metadata">
            <obsolescence-date-metadata-for-update v-if="is_obsolescence_date_metadata_used" v-model="date_value"/>
            <custom-metadata v-bind:item-metadata="custom_metadata"/>
        </template>
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import ObsolescenceDateMetadataForUpdate from "../ObsolescenceMetadata/ObsolescenceDateMetadataForUpdate.vue";
import CustomMetadata from "../CustomMetadata/CustomMetadata.vue";
import { getCustomMetadata } from "../../../../helpers/metadata-helpers/custom-metadata-helper.js";
import { transformCustomMetadataForItemUpdate } from "../../../../helpers/metadata-helpers/data-transformatter-helper.js";

export default {
    name: "OtherInformationMetadataForUpdate",
    components: {
        CustomMetadata,
        ObsolescenceDateMetadataForUpdate
    },
    props: {
        currentlyUpdatedItem: Object
    },
    data() {
        return {
            item_metadata: getCustomMetadata(this.currentlyUpdatedItem.metadata)
        };
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"]),
        ...mapState("metadata", ["has_loaded_metadata"]),
        ...mapGetters(["obsolescence_date_metadata"]),
        should_display_other_information() {
            return this.is_obsolescence_date_metadata_used || this.item_metadata.length > 0;
        },
        date_value: {
            get() {
                if (!this.is_obsolescence_date_metadata_used) {
                    return "";
                }
                const metadata = this.currentlyUpdatedItem.metadata.find(
                    metadata => metadata.short_name === "obsolescence_date"
                );
                return metadata.value;
            },
            set(value) {
                if (!this.is_obsolescence_date_metadata_used) {
                    return;
                }
                const metadata = this.currentlyUpdatedItem.metadata.find(
                    metadata => metadata.short_name === "obsolescence_date"
                );
                metadata.value = value;
                this.currentlyUpdatedItem.obsolescence_date = value;
            }
        },
        custom_metadata: {
            get() {
                transformCustomMetadataForItemUpdate(this.item_metadata);

                return this.item_metadata;
            }
        }
    },
    mounted() {
        if (!this.has_loaded_metadata) {
            this.$store.dispatch("metadata/loadProjectMetadata", [this.$store]);
        }
    }
};
</script>
