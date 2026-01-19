<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <section>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="11.365" fill="none">
            <title>{{ $gettext("One column layout") }}</title>
            <path
                d="M1 0a1 1 0 0 0-1 1v9.365a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1ZM.969 2.506H17v8.006H.969Z"
                class="layout-border"
                v-bind:class="{ current: is_one_column }"
            />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="11.365" fill="none">
            <title>{{ $gettext("Two columns layout") }}</title>
            <path
                d="M1 0a1 1 0 0 0-1 1v9.365a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zM.969 2.506H8.02v8.006H.97V2.506zm9.02 0H17v8.006H9.988V2.506z"
                class="layout-border"
                v-bind:class="{ current: is_two_columns }"
            />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="11.365" fill="none">
            <title>{{ $gettext("Three columns layout") }}</title>
            <path
                d="M1 0a1 1 0 0 0-1 1v9.365a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zM.969 2.506h4.039v8.006H.968V2.506zm6.004 0h4.039v8.006h-4.04V2.506zm6.006 0H17v8.006h-4.021V2.506z"
                class="layout-border"
                v-bind:class="{ current: is_three_columns }"
            />
        </svg>
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="11.365"
            fill="none"
            v-if="is_custom_layout"
        >
            <title>{{ $gettext("Custom layout") }}</title>
            <path
                d="M1 0a1 1 0 0 0-1 1v9.365a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1H1zM.969 2.506H17v8.006H.969V2.506zm7.379.99.279 2.56-2.533-.72-.188 1.226 2.428.202-1.56 2.105 1.187.627 1.025-2.332 1.135 2.332 1.147-.627L9.68 6.764l2.453-.202-.186-1.226-2.56.72.267-2.56H8.348z"
                class="layout-border"
                v-bind:class="{ current: is_custom_layout }"
            />
        </svg>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Fieldset } from "../../type";
import {
    CUSTOM_LAYOUT,
    getFieldsetLayout,
    ONE_COLUMN,
    THREE_COLUMNS,
    TWO_COLUMNS,
} from "../../helpers/get-fieldset-layout";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    fieldset: Fieldset;
}>();

const { $gettext } = useGettext();

const layout = computed(() => getFieldsetLayout(props.fieldset));
const is_one_column = computed(() => layout.value === ONE_COLUMN);
const is_two_columns = computed(() => layout.value === TWO_COLUMNS);
const is_three_columns = computed(() => layout.value === THREE_COLUMNS);
const is_custom_layout = computed(() => layout.value === CUSTOM_LAYOUT);
</script>

<style scoped lang="scss">
section {
    display: flex;
    gap: var(--tlp-small-spacing);
}

.layout-border {
    fill: var(--tlp-dimmed-color-lighter-70);

    &.current {
        fill: var(--tlp-main-color);
    }
}
</style>
