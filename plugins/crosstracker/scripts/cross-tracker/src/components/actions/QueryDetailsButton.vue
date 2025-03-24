<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <input
        v-on:change="toggleQueryDetails"
        type="checkbox"
        v-bind:id="query_details_id"
        class="tlp-button-bar-checkbox"
        v-bind:checked="are_query_details_toggled"
        data-test="toggle-query-details-input"
    />
    <label
        v-bind:for="query_details_id"
        class="tlp-button-primary tlp-button-outline tlp-button-mini query-details-button"
        data-test="toggle-query-details-button"
    >
        {{ $gettext("Query details & tools") }}
    </label>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { EMITTER, WIDGET_ID } from "../../injection-symbols";
import { TOGGLE_QUERY_DETAILS_EVENT } from "../../helpers/widget-events";

defineProps<{
    are_query_details_toggled: boolean;
}>();

const emitter = strictInject(EMITTER);
const widget_id = strictInject(WIDGET_ID);
const query_details_id = "query-details-" + widget_id;

function toggleQueryDetails(event: Event): void {
    const event_target = event.currentTarget;
    if (event_target instanceof HTMLInputElement) {
        emitter.emit(TOGGLE_QUERY_DETAILS_EVENT, {
            display_query_details: event_target.checked,
        });
    }
}
</script>

<style scoped lang="scss">
.query-details-button {
    margin: 0 var(--tlp-small-spacing);
}
</style>
