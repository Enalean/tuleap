<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <section class="tlp-pane-section document-quick-look-properties">
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-id" class="tlp-label" v-translate>Id</label>
                <p id="document-id">#{{ item.id }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-owner" class="tlp-label" v-translate>Owner</label>
                <p id="document-owner">
                    <user-badge v-bind:user="item.owner"/>
                </p>
            </div>
            <quick-look-document-additional-metadata-list v-for="metadata in metadata_right_column" v-bind:metadata="metadata" v-bind:key="metadata.name"/>
        </div>
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-creation-date" class="tlp-label" v-translate>Creation date</label>
                <p id="document-creation-date" class="tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="getFormattedDate(item.creation_date)">{{ getFormattedDateForDisplay(item.creation_date) }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-last-update-date" class="tlp-label" v-translate>Last updated date</label>
                <p id="document-last-update-date" class="tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="getFormattedDate(item.last_update_date)">{{ getFormattedDateForDisplay(item.last_update_date) }}</p>
            </div>
            <quick-look-document-additional-metadata-list v-for="metadata in metadata_left_column" v-bind:metadata="metadata" v-bind:key="metadata.name"/>
        </div>
    </section>
</template>
<script>
import { mapState } from "vuex";
import {
    formatDateUsingPreferredUserFormat,
    getElapsedTimeFromNow
} from "../../../helpers/date-formatter.js";
import UserBadge from "../../User/UserBadge.vue";
import QuickLookDocumentAdditionalMetadataList from "./QuickLookDocumentAdditionalMetadataList.vue";

export default {
    components: { QuickLookDocumentAdditionalMetadataList, UserBadge },
    props: {
        item: Object
    },
    computed: {
        ...mapState(["date_time_format"]),
        metadata_right_column() {
            const metadata_length = this.item.metadata.length;

            return this.item.metadata.slice(0, Math.ceil(metadata_length / 2));
        },
        metadata_left_column() {
            const metadata_length = this.item.metadata.length;

            return this.item.metadata.slice(Math.ceil(metadata_length / 2), metadata_length);
        }
    },
    methods: {
        getFormattedDate(date) {
            return formatDateUsingPreferredUserFormat(date, this.date_time_format);
        },
        getFormattedDateForDisplay(date) {
            return getElapsedTimeFromNow(date);
        }
    }
};
</script>
