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
        <div class="tlp-form-element tlp-form-element-prepend">
            <select class="tlp-prepend" v-on:change="onChangeOperator($event.target.value)">
                <option value=">" v-bind:selected="'>' === operator">
                    {{ $gettext("After") }}
                </option>
                <option value="<" v-bind:selected="'<' === operator">
                    {{ $gettext("Before") }}
                </option>
                <option value="=" v-bind:selected="'=' === operator" data-test="equal">
                    {{ $gettext("On") }}
                </option>
            </select>
            <date-flat-picker
                v-bind:id="id"
                v-bind:required="false"
                v-bind:value="date"
                v-on:input="onChangeDate"
                v-bind:data-test="id"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AllowedSearchDateOperator, SearchCriterionDate, SearchDate } from "../../../type";
import DateFlatPicker from "../../Folder/DropDown/PropertiesForCreateOrUpdate/DateFlatPicker.vue";
import { computed, ref, watch } from "vue";
import emitter from "../../../helpers/emitter";

const props = defineProps<{ criterion: SearchCriterionDate; value: SearchDate | null }>();

const date = ref("");
const operator = ref<AllowedSearchDateOperator>(">");

watch(
    () => props.value,
    (value: SearchDate | null): void => {
        date.value = value?.date ?? "";
        operator.value = value?.operator ?? ">";
    },
    { immediate: true },
);

const id = computed((): string => {
    return "document-criterion-date-" + props.criterion.name;
});

function onChangeOperator(new_operator: AllowedSearchDateOperator): void {
    onChange(new_operator, date.value);
}

function onChangeDate(new_date: string): void {
    onChange(operator.value, new_date);
}

function onChange(operator: AllowedSearchDateOperator, date: string): void {
    const new_value: SearchDate = { operator, date };

    emitter.emit("update-criteria-date", {
        criteria: props.criterion.name,
        value: new_value,
    });
}
</script>
