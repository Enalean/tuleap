<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-property">
        <label v-bind:for="metadata_label" class="tlp-label" data-test="metadata-list-label">
            {{ metadata_name }}
        </label>
        <p v-bind:id="metadata_label">
            <quicklook-metadata-date
                v-if="metadata.type === METADATA_DATE_TYPE"
                v-bind:metadata="metadata"
            />
            <template v-else-if="metadata.type === METADATA_LIST_TYPE && !is_list_empty">
                <ul v-if="metadata.list_value.length > 1">
                    <li v-for="value in metadata.list_value" v-bind:key="value.id">
                        {{ value.name }}
                    </li>
                </ul>
                <template data-test="metadata-unique-list-value" v-else>
                    {{ metadata.list_value[0].name }}
                </template>
            </template>

            <span
                class="document-quick-look-property-empty"
                v-else-if="!has_metadata_a_value"
                v-translate
            >
                Empty
            </span>
            <template v-else>
                <div v-dompurify-html="metadata.post_processed_value"></div>
            </template>
        </p>
    </div>
</template>
<script>
import { METADATA_OBSOLESCENCE_DATE_SHORT_NAME } from "../../../constants.js";
import QuicklookMetadataDate from "./QuickLookMetadataDate.vue";

export default {
    name: "QuickLookDocumentAdditionalMetadataList",
    components: { QuicklookMetadataDate },
    props: {
        metadata: Object,
    },
    data() {
        return {
            METADATA_LIST_TYPE: "list",
            METADATA_DATE_TYPE: "date",
        };
    },
    computed: {
        metadata_label() {
            return `document-${this.metadata.short_name}`;
        },
        metadata_name() {
            if (this.isMetadataObsolescenceDate()) {
                return this.$gettext("Validity");
            }
            return this.metadata.name;
        },
        is_list_empty() {
            return !this.metadata.list_value || !this.metadata.list_value.length;
        },
        has_metadata_a_value() {
            if (this.metadata.type === this.METADATA_LIST_TYPE) {
                return !this.is_list_empty;
            }

            return this.metadata.value;
        },
    },
    methods: {
        isMetadataObsolescenceDate() {
            return this.metadata.short_name === METADATA_OBSOLESCENCE_DATE_SHORT_NAME;
        },
    },
};
</script>
