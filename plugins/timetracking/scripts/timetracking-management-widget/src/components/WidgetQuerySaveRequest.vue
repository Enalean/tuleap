<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <div class="timetracking-management-query-save-request-actions">
        <button
            class="tlp-button-primary tlp-button-outline"
            type="button"
            v-on:click="cancel()"
            data-test="cancel-button"
        >
            {{ $gettext("Cancel") }}
        </button>
        <button
            class="tlp-button-primary"
            type="button"
            v-on:click="save()"
            data-test="save-button"
        >
            {{ $gettext("Save query") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { RETRIEVE_QUERY, WIDGET_ID } from "../injection-symbols";

const { $gettext } = useGettext();
const query = strictInject(RETRIEVE_QUERY);
const widget_id = strictInject(WIDGET_ID);

const cancel = (): void => {
    query.has_the_query_been_modified.value = false;
};

const save = (): void => {
    query.saveQuery(widget_id);
    query.has_the_query_been_modified.value = false;
};
</script>

<style scoped lang="scss">
.timetracking-management-query-save-request-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--tlp-medium-spacing);
    gap: var(--tlp-medium-spacing);
}
</style>
