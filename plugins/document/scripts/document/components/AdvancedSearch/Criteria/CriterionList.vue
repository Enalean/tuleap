<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="tlp-form-element document-search-criterion">
        <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
        <select class="tlp-select" v-bind:id="id" v-on:change="updateCriteria">
            <option v-bind:selected="isSelected('')" value="">
                {{ $gettext("Any") }}
            </option>
            <option
                v-for="option in criterion.options"
                v-bind:key="id + '_' + option.value"
                v-bind:value="option.value"
                v-bind:selected="isSelected(option)"
                v-bind:data-test="`option-${option.value}`"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup lang="ts">
import type { SearchCriterionList, SearchListOption } from "../../../type";
import { computed } from "vue";
import emitter from "../../../helpers/emitter";

const props = defineProps<{ criterion: SearchCriterionList; value: string }>();

const id = computed((): string => {
    return "document-criterion-list-" + props.criterion.name;
});

function updateCriteria($event: Event): void {
    if ($event.target instanceof HTMLSelectElement) {
        emitter.emit("update-criteria", {
            criteria: props.criterion.name,
            value: $event.target.value,
        });
    }
}

function isSelected(option: SearchListOption): boolean {
    return option.value === props.value;
}
</script>
