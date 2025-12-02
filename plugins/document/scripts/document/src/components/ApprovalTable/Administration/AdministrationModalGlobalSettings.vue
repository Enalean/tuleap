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
    <h2 class="tlp-modal-subtitle">{{ $gettext("Approval table global settings") }}</h2>
    <div class="tlp-property">
        <label class="tlp-label">{{ $gettext("Attached to document version") }}</label>
        <p>{{ table.version_number }}</p>
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label" for="owner-lazybox">
            {{ $gettext("Approval requester") }}
        </label>
        <tuleap-lazybox id="owner-lazybox" ref="owner_lazybox" v-bind:is_multiple="false" />
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label" for="table-status">
            {{ $gettext("Table status") }}
        </label>
        <select
            id="table-status"
            name="table-status"
            class="tlp-select tlp-select-adjusted"
            v-model="table_status_value"
            data-test="table-status-select"
        >
            <option value="closed">{{ $gettext("Closed") }}</option>
            <option value="disabled">{{ $gettext("Disabled") }}</option>
            <option value="enabled">{{ $gettext("Available") }}</option>
        </select>
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label" for="table-comment">
            {{ $gettext("Comment") }}
        </label>
        <textarea
            id="table-comment"
            name="table-comment"
            class="tlp-textarea comment-input"
            v-model="table_comment_value"
            data-test="table-comment-input"
        ></textarea>
    </div>
</template>

<script setup lang="ts">
import type { ApprovalTable } from "../../../type";
import { onMounted, ref } from "vue";
import "@tuleap/lazybox";
import type { Lazybox } from "@tuleap/lazybox";
import type { User } from "@tuleap/core-rest-api-types";
import { fetchMatchingUsers, initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";
import { USER_LOCALE } from "../../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";

defineProps<{ table: ApprovalTable }>();

const table_status_value = defineModel<string>("table_status_value", { required: true });
const table_comment_value = defineModel<string>("table_comment_value", { required: true });
const table_owner_value = defineModel<User | null>("table_owner_value", { required: true });

const owner_lazybox = ref<Lazybox>();

const user_locale = strictInject(USER_LOCALE);

onMounted(() => {
    if (owner_lazybox.value === undefined) {
        throw Error("Failed to create global settings section");
    }

    initUsersAutocompleter(
        owner_lazybox.value,
        table_owner_value.value ? [table_owner_value.value] : [],
        (selected_users: ReadonlyArray<User>): void => {
            table_owner_value.value = selected_users.length === 0 ? null : selected_users[0];
        },
        user_locale,
        fetchMatchingUsers,
        false,
    );
});
</script>

<style scoped lang="scss">
.comment-input {
    resize: vertical;
}
</style>
