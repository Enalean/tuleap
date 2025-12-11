<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <div class="tlp-alert-warning" data-test="missing-table-warning">
        {{
            sprintf(
                $gettext(
                    "This table is linked to an old version of the document (%d). The last document version is %d.",
                ),
                table.version_number,
                getItemVersion(),
            )
        }}
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label">
            {{ $gettext("You can either:") }}
        </label>
        <label class="tlp-label tlp-radio">
            <input
                type="radio"
                name="action"
                value="copy"
                v-model="table_action_value"
                data-test="missing-table-action-checkbox"
            />
            {{ $gettext("Copy the previous approval table (e.g. for small / typo updates).") }}
        </label>
        <p class="tlp-text-info">
            {{
                $gettext(
                    "A new approval table will be created from the previous one. Keeps reviewers, comments and commitments.",
                )
            }}
        </p>
        <label class="tlp-label tlp-radio">
            <input
                type="radio"
                name="action"
                value="reset"
                v-model="table_action_value"
                data-test="missing-table-action-checkbox"
            />
            {{ $gettext("Reset the approval cycle (e.g. for major rewrite).") }}
        </label>
        <p class="tlp-text-info">
            {{
                $gettext(
                    "Only the table structure will be kept (description, notification type, reviewer list). Actually, the new table will be identical to the previous one, but without reviewer comments and commitments.",
                )
            }}
        </p>
        <label class="tlp-label tlp-radio">
            <input
                type="radio"
                name="action"
                value="empty"
                v-model="table_action_value"
                data-test="missing-table-action-checkbox"
            />
            {{ $gettext("Create a new empty table.") }}
        </label>
        <p class="tlp-text-info">
            {{ $gettext("Start over with a completly new approval table.") }}
        </p>
    </div>
    <p class="tlp-text-info">
        {{ $gettext("In all cases, there is no automatic notification of the reviewers.") }}
    </p>
</template>

<script setup lang="ts">
import type { ApprovalTable, Item } from "../../../type";
import { sprintf } from "sprintf-js";
import { isEmbedded, isFile, isLink, isWiki } from "../../../helpers/type-check-helper";

const props = defineProps<{
    item: Item;
    table: ApprovalTable;
}>();

const table_action_value = defineModel<string>("table_action_value", { required: true });

function getItemVersion(): number {
    if (isEmbedded(props.item) && props.item.embedded_file_properties !== null) {
        return props.item.embedded_file_properties.version_number;
    }

    if (isFile(props.item) && props.item.file_properties !== null) {
        return props.item.file_properties.version_number;
    }

    if (isWiki(props.item)) {
        return props.item.wiki_properties.version_number;
    }

    if (isLink(props.item) && props.item.link_properties.version_number !== null) {
        return props.item.link_properties.version_number;
    }

    throw Error(`Unexpected item type '${props.item.type}'`);
}
</script>
