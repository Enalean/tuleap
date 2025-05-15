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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div
        class="tlp-form-element document-obsolescence-date-section"
        v-if="is_obsolescence_date_property_used"
        data-test="obsolescence-date-property"
    >
        <label class="tlp-label" for="document-obsolescence-date-update">
            {{ $gettext("Obsolescence date") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <div class="tlp-form-element document-obsolescence-date-properties-fields">
            <select
                class="tlp-select tlp-select-adjusted"
                id="document-obsolescence-date-select-update"
                v-bind:value="selected_value"
                v-on:change="updateDatePickerValue"
                ref="selectDateValue"
                data-test="document-obsolescence-date-select-update"
            >
                <option name="permanent" value="permanent">{{ $gettext("Permanent") }}</option>
                <option name="3months" value="3">{{ $gettext("3 months") }}</option>
                <option name="6months" value="6">{{ $gettext("6 months") }}</option>
                <option name="12months" value="12">{{ $gettext("12 months") }}</option>
                <option name="fixedDate" value="fixed">{{ $gettext("Fixed date") }}</option>
                <option name="today" value="today">{{ $gettext("Obsolete today") }}</option>
            </select>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend"><i class="fa-regular fa-calendar"></i></span>
                <date-flat-picker
                    v-bind:id="'document-obsolescence-date-update'"
                    v-bind:required="selected_value === 'fixed'"
                    v-on:input="updateObsolescenceDateValue"
                    v-bind:value="date_value"
                />
            </div>
        </div>
        <p
            class="tlp-text-danger"
            v-if="error_message.length > 0"
            data-test="obsolescence-date-error-message"
        >
            {{ error_message }}
        </p>
    </div>
</template>

<script setup lang="ts">
import {
    formatObsolescenceDateValue,
    getObsolescenceDateValueInput,
} from "../../../../helpers/properties-helpers/obsolescence-date-value";
import DateFlatPicker from "../PropertiesForCreateOrUpdate/DateFlatPicker.vue";
import emitter from "../../../../helpers/emitter";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { onMounted, onBeforeMount, ref } from "vue";

const props = defineProps<{
    value: string;
}>();

let date_value = ref(props.value);
let selected_value = ref("");
let error_message = ref("");
let uses_helper_validity = ref(false);

const { is_obsolescence_date_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_obsolescence_date_property_used">
>("configuration", ["is_obsolescence_date_property_used"]);

onBeforeMount((): void => {
    date_value.value = formatObsolescenceDateValue(date_value.value);
    emitter.emit("update-obsolescence-date-property", date_value.value);
});

onMounted((): void => {
    if (props.value !== "") {
        selected_value.value = "fixed";
    } else {
        selected_value.value = "permanent";
    }
});

function updateDatePickerValue(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }

    const input_date_value = getObsolescenceDateValueInput(event.target.value);

    uses_helper_validity.value = true;

    selected_value.value = event.target.value;
    date_value.value = input_date_value;
    emitter.emit("update-obsolescence-date-property", input_date_value);
}

function updateObsolescenceDateValue(event: string): void {
    if (!uses_helper_validity.value) {
        selected_value.value = "fixed";
    }

    emitter.emit("update-obsolescence-date-property", event);

    uses_helper_validity.value = false;
}
</script>
