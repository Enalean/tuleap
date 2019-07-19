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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="document-metadata" v-if="should_display_other_information" data-test="document-other-information">
        <hr class="tlp-modal-separator">
        <h2 class="tlp-modal-subtitle" v-translate>Other information</h2>
        <obsolescence-date-metadata-for-create v-model="date_value"/>
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import ObsolescenceDateMetadataForCreate from "./ObsolescenceMetadata/ObsolescenceDateMetadataForCreate.vue";

export default {
    name: "OtherInformationMetadataForCreate",
    components: {
        ObsolescenceDateMetadataForCreate
    },
    props: {
        currentlyUpdatedItem: Object
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"]),
        ...mapGetters(["obsolescence_date_metadata"]),
        should_display_other_information() {
            return this.is_obsolescence_date_metadata_used;
        },
        date_value: {
            get() {
                const metadata = this.currentlyUpdatedItem.metadata.find(
                    metadata => metadata.short_name === "obsolescence_date"
                );
                return metadata.value;
            },
            set(value) {
                const metadata = this.currentlyUpdatedItem.metadata.find(
                    metadata => metadata.short_name === "obsolescence_date"
                );
                metadata.value = value;
                this.currentlyUpdatedItem.obsolescence_date = value;
            }
        }
    }
};
</script>
