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
                <quick-look-document-additional-properties
                    v-for="property in properties_right_column"
                    v-bind:property="property"
                    v-bind:key="property.name"
                    data-test="properties-right-list"
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
                >
                    {{ getFormattedDate(item.creation_date) }}
                </tlp-relative-date>
            </div>
            <div class="tlp-property">
                <label class="tlp-label" v-translate>Last update date</label>
                <tlp-relative-date
                    v-bind:date="item.last_update_date"
                    v-bind:absolute-date="getFormattedDate(item.last_update_date)"
                    v-bind:placement="relative_date_placement"
                    v-bind:preference="relative_date_preference"
                    v-bind:locale="user_locale"
                >
                    {{ getFormattedDate(item.last_update_date) }}
                </tlp-relative-date>
            </div>
            <div
                class="tlp-property"
                v-if="has_an_approval_table"
                data-test="docman-item-approval-table-status-badge"
            >
                <label for="document-approval-table-status" class="tlp-label" v-translate>
                    Approval table status
                </label>
                <approval-badge
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
                <quick-look-document-additional-properties
                    v-for="property in properties_left_column"
                    v-bind:property="property"
                    v-bind:key="property.name"
                    data-test="properties-left-list"
                />
            </template>
        </div>
    </section>
</template>
<script>
import prettyBytes from "pretty-kibibytes";
import { mapState } from "vuex";
import { formatDateUsingPreferredUserFormat } from "../../../helpers/date-formatter";
import UserBadge from "../../User/UserBadge.vue";
import QuickLookDocumentAdditionalProperties from "./QuickLookDocumentAdditionalProperties.vue";
import ApprovalBadge from "../ApprovalTables/ApprovalBadge.vue";
import {
    relativeDatePlacement,
    relativeDatePreference,
} from "@tuleap/core/scripts/tuleap/custom-elements/relative-date/relative-date-helper";
import { isFile, isFolder } from "../../../helpers/type-check-helper";

export default {
    components: { ApprovalBadge, QuickLookDocumentAdditionalProperties, UserBadge },
    props: {
        item: Object,
    },
    computed: {
        ...mapState("configuration", ["date_time_format", "relative_dates_display", "user_locale"]),
        properties_right_column() {
            const lenght = this.get_custom_properties.length;

            return this.get_custom_properties.slice(0, Math.ceil(lenght / 2));
        },
        properties_left_column() {
            const length = this.get_custom_properties.length;

            return this.get_custom_properties.slice(Math.ceil(length / 2), length);
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
        get_custom_properties() {
            const hardcoded_properties = [
                "title",
                "description",
                "owner",
                "create_date",
                "update_date",
            ];

            return this.item.metadata.filter(
                ({ short_name }) => !hardcoded_properties.includes(short_name)
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
        is_file() {
            return isFile(this.item);
        },
        is_document() {
            return !isFolder(this.item.type);
        },
    },
};
</script>
