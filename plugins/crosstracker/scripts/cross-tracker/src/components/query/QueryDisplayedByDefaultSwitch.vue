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
    <div class="switch-wrapper query-default-query-switch tlp-form-element">
        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                v-bind:id="is_default_switch_id"
                class="tlp-switch-checkbox"
                v-on:input="emit('update:is_default_query', !is_default_query)"
                data-test="query-checkbox"
                v-bind:checked="is_default_query"
            />
            <label v-bind:for="is_default_switch_id" class="tlp-switch-button"></label>
        </div>
        <label
            class="tlp-label switch-label query-default-query-switch-label"
            v-bind:for="is_default_switch_id"
            >{{ $gettext("Make this query displayed by default") }}</label
        >
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { WIDGET_ID } from "../../injection-symbols";
import { computed } from "vue";

defineProps<{
    is_default_query: boolean;
}>();

const emit = defineEmits<{
    (e: "update:is_default_query", value: boolean): void;
}>();

const widget_id = strictInject(WIDGET_ID);

const is_default_switch_id = computed((): string => {
    return "toggle-" + widget_id;
});
</script>

<style scoped lang="scss">
.query-default-query-switch {
    display: flex;
}

.query-default-query-switch-label {
    margin: 0 0 var(--tlp-small-spacing) var(--tlp-small-spacing);
}
</style>
