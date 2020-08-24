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
    <section class="tlp-pane-section document-quick-look-properties">
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label for="document-id" class="tlp-label" v-translate>Id</label>
                <p id="document-id">#{{ item.id }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-owner" class="tlp-label" v-translate>Owner</label>
                <p id="document-owner">
                    <user-badge v-bind:user="item.owner" />
                </p>
            </div>
            <template v-if="is_document">
                <quick-look-document-additional-metadata-list
                    v-for="metadata in metadata_right_column"
                    v-bind:metadata="metadata"
                    v-bind:key="metadata.name"
                    data-test="additional-metadata-right-list"
                />
            </template>
        </div>
        <div class="document-quick-look-properties-column">
            <div class="tlp-property">
                <label class="tlp-label" v-translate>Creation</label>
                <tlp-relative-date
                    v-bind:date="item.creation_date"
                    v-bind:absolute-date="getFormattedDate(item.creation_date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                />
            </div>
            <div class="tlp-property">
                <label class="tlp-label" v-translate>Last update date</label>
                <tlp-relative-date
                    v-bind:date="item.last_update_date"
                    v-bind:absolute-date="getFormattedDate(item.last_update_date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                />
            </div>
            <div
                class="tlp-property"
                v-if="has_an_approval_table"
                data-test="docman-item-approval-table-status-badge"
            >
                <label for="document-approval-table-status" class="tlp-label" v-translate>
                    Approval table status
                </label>
                <approval-table-badge
                    id="document-approval-table-status"
                    v-bind:item="item"
                    v-bind:is-in-folder-content-row="false"
                />
            </div>
            <div v-if="is_file" class="tlp-property">
                <label for="document-file-size" class="tlp-label" v-translate>File size</label>
                <p id="document-file-size" data-test="docman-file-size">
                    {{ file_size_in_mega_bytes }}
                </p>
            </div>
            <template v-if="is_document">
                <quick-look-document-additional-metadata-list
                    v-for="metadata in metadata_left_column"
                    v-bind:metadata="metadata"
                    v-bind:key="metadata.name"
                    data-test="additional-metadata-left-list"
                />
            </template>
        </div>
    </section>
</template>
<script>
import prettyBytes from "pretty-bytes-es5";
import { mapState } from "vuex";
import { formatDateUsingPreferredUserFormat } from "../../../helpers/date-formatter.js";
import UserBadge from "../../User/UserBadge.vue";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";
import QuickLookDocumentAdditionalMetadataList from "./QuickLookDocumentAdditionalMetadataList.vue";
import ApprovalTableBadge from "../ApprovalTables/ApprovalTableBadge.vue";
import {
    relativeDatePlacement,
    relativeDatePreference,
} from "../../../../../../../src/themes/tlp/src/js/custom-elements/relative-date/relative-date-helper";

export default {
    components: { ApprovalTableBadge, QuickLookDocumentAdditionalMetadataList, UserBadge },
    props: {
        item: Object,
    },
    computed: {
        ...mapState(["date_time_format", "relative_dates_display", "user_locale"]),
        metadata_right_column() {
            const metadata_length = this.get_custom_metadata.length;

            return this.get_custom_metadata.slice(0, Math.ceil(metadata_length / 2));
        },
        metadata_left_column() {
            const metadata_length = this.get_custom_metadata.length;

            return this.get_custom_metadata.slice(Math.ceil(metadata_length / 2), metadata_length);
        },
        is_file() {
            return this.item.type === TYPE_FILE;
        },
        is_document() {
            return this.item.type !== TYPE_FOLDER;
        },
        file_size_in_mega_bytes() {
            if (!this.item.file_properties) {
                return prettyBytes(0);
            }
            return prettyBytes(parseInt(this.item.file_properties.file_size, 10));
        },
        has_an_approval_table() {
            return this.item.approval_table;
        },
        get_custom_metadata() {
            const hardcoded_metadata = [
                "title",
                "description",
                "owner",
                "create_date",
                "update_date",
            ];

            return this.item.metadata.filter(
                ({ short_name }) => !hardcoded_metadata.includes(short_name)
            );
        },
        relative_date_preference() {
            return relativeDatePreference(this.relative_dates_display);
        },
        relative_date_placement() {
            return relativeDatePlacement(this.relative_dates_display, "right");
        },
    },
    methods: {
        getFormattedDate(date) {
            return formatDateUsingPreferredUserFormat(date, this.date_time_format);
        },
    },
};
</script>
