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
    <div
        class="tlp-form-element column-wrapper"
        v-bind:class="classes"
        draggable="false"
        v-bind:data-element-id="column_wrapper.identifier"
    >
        <container-column
            v-for="column of column_wrapper.columns"
            v-bind:key="column.field.field_id"
            v-bind:data-element-id="column.field.field_id"
            v-bind:column="column"
        />
    </div>
</template>

<script setup lang="ts">
import type { Column, ColumnWrapper, Fieldset } from "../../type";
import ContainerColumn from "./ContainerColumn.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IS_LAYOUT_WARNING_DISPLAYED } from "../../injection-symbols";
import { isFieldset } from "../../helpers/is-fieldset";
import { computed } from "vue";

const is_layout_warning_displayed = strictInject(IS_LAYOUT_WARNING_DISPLAYED);

const props = defineProps<{
    column_wrapper: ColumnWrapper;
    parent: Column | Fieldset | null;
}>();

const classes = computed(() => {
    if (!is_layout_warning_displayed.value) {
        return "";
    }

    if (props.parent === null) {
        return "highlight-layout-issue";
    }

    if (isFieldset(props.parent)) {
        return "";
    }

    return "highlight-layout-issue";
});
</script>

<style lang="scss" scoped>
.column-wrapper {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>
