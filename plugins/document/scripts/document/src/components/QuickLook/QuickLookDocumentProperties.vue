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
                <label for="document-id" class="tlp-label">{{ $gettext("Id") }}</label>
                <p id="document-id">#{{ item.id }}</p>
            </div>
            <div class="tlp-property">
                <label for="document-owner" class="tlp-label">{{ $gettext("Owner") }}</label>
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
                <label class="tlp-label">{{ $gettext("Creation") }}</label>
                <document-relative-date
                    v-bind:date="item.creation_date"
                    v-bind:relative_placement="'right'"
                />
            </div>
            <div class="tlp-property">
                <label class="tlp-label">{{ $gettext("Last") }} update date</label>
                <document-relative-date
                    v-bind:date="item.last_update_date"
                    v-bind:relative_placement="'right'"
                />
            </div>
            <div
                class="tlp-property"
                v-if="has_an_approval_table"
                data-test="docman-item-approval-table-status-badge"
            >
                <label for="document-approval-table-status" class="tlp-label">
                    {{ $gettext("Approval table status") }}
                </label>
                <approval-badge
                    id="document-approval-table-status"
                    v-bind:item="item"
                    v-bind:is-in-folder-content-row="false"
                />
            </div>
            <div v-if="is_file" class="tlp-property">
                <label for="document-file-size" class="tlp-label">
                    {{ $gettext("File size") }}
                </label>
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

<script setup lang="ts">
import prettyBytes from "pretty-kibibytes";
import UserBadge from "../User/UserBadge.vue";
import QuickLookDocumentAdditionalProperties from "./QuickLookDocumentAdditionalProperties.vue";
import ApprovalBadge from "../Folder/ApprovalTables/ApprovalBadge.vue";
import { isFile, isFolder } from "../../helpers/type-check-helper";
import type { Item, Property } from "../../type";
import { computed } from "vue";
import { hasAnApprovalTable } from "../../helpers/approval-table-helper";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";

const props = defineProps<{ item: Item }>();

const get_custom_properties = computed((): Array<Property> => {
    const hardcoded_properties = ["title", "description", "owner", "create_date", "update_date"];

    return props.item.properties.filter(
        ({ short_name }) => !hardcoded_properties.includes(short_name),
    );
});

const properties_right_column = computed((): Array<Property> => {
    const length = get_custom_properties.value.length;

    return get_custom_properties.value.slice(0, Math.ceil(length / 2));
});
const properties_left_column = computed((): Array<Property> => {
    const length = get_custom_properties.value.length;

    return get_custom_properties.value.slice(Math.ceil(length / 2), length);
});
const file_size_in_mega_bytes = computed((): string => {
    const item = props.item;
    if (!isFile(item) || !item.file_properties) {
        return prettyBytes(0);
    }
    return prettyBytes(item.file_properties.file_size);
});
const has_an_approval_table = computed((): boolean => {
    return hasAnApprovalTable(props.item);
});

const is_file = computed((): boolean => {
    return isFile(props.item);
});

const is_document = computed((): boolean => {
    return !isFolder(props.item);
});
</script>
