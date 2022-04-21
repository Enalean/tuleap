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
        class="tlp-form-element"
        v-if="is_obsolescence_date_property_used"
        data-test="obsolescence-date-property"
    >
        <label class="tlp-label" for="document-obsolescence-date-select">
            <translate>Obsolescence date</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element document-obsolescence-date-properties-fields">
            <select
                class="tlp-select tlp-select-adjusted"
                id="document-obsolescence-date-select"
                ref="selectDateValue"
                data-test="document-obsolescence-date-select"
                v-model="selected_value"
                v-on:change="updateDatePickerValue"
            >
                <option name="permanent" value="permanent" v-translate>Permanent</option>
                <option name="3months" value="3" v-translate>3 months</option>
                <option name="6months" value="6" v-translate>6 months</option>
                <option name="12months" value="12" v-translate>12 months</option>
                <option name="fixedDate" value="fixed" v-translate>Fixed date</option>
                <option name="today" value="today" v-translate>Obsolete today</option>
            </select>
            <div class="tlp-form-element tlp-form-element-prepend">
                <span class="tlp-prepend"><i class="fas fa-calendar-alt"></i></span>
                <date-flat-picker
                    v-bind:id="'document-new-obsolescence-date'"
                    v-bind:required="selected_value === 'fixed'"
                    v-bind:value="date_value"
                    v-on:input="updateObsolescenceDate"
                    ref="input"
                    data-test="obsolescence-date-input"
                />
            </div>
        </div>
        <p
            class="tlp-text-danger"
            v-if="error_message && error_message.length > 0"
            data-test="obsolescence-date-error-message"
        >
            {{ error_message }}
        </p>
    </div>
</template>

<script setup lang="ts">
import DateFlatPicker from "../../PropertiesForCreateOrUpdate/DateFlatPicker.vue";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../../store/configuration";
import { getObsolescenceDateValueInput } from "../../../../../helpers/properties-helpers/obsolescence-date-value";
import emitter from "../../../../../helpers/emitter";
import moment from "moment/moment";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import { ref } from "@vue/composition-api";

const props = defineProps<{ value: string }>();

const { $gettext } = useGettext();
const error = ref($gettext("The current date is the same or before the obsolescence date"));

let date_value = ref(props.value);
let selected_value = ref("permanent");
let error_message = ref("");
let uses_helper_validity = ref(false);

const { is_obsolescence_date_property_used } = useNamespacedState<
    Pick<ConfigurationState, "is_obsolescence_date_property_used">
>("configuration", ["is_obsolescence_date_property_used"]);

function updateDatePickerValue(event: Event) {
    if (!(event.target instanceof HTMLSelectElement)) {
        return;
    }

    const input_date_value = getObsolescenceDateValueInput(event.target.value);

    error_message.value = "";
    uses_helper_validity.value = true;

    selected_value.value = event.target.value;
    date_value.value = input_date_value;
    emitter.emit("update-obsolescence-date-property", input_date_value);
}

function checkDateValidity(date: string) {
    const current_date = moment();
    error_message.value = "";
    if (current_date.isSameOrAfter(date, "day")) {
        error_message.value = error.value;
    }
}

function updateObsolescenceDate(new_date: string) {
    checkDateValidity(new_date);

    if (!uses_helper_validity.value) {
        selected_value.value = "fixed";
    }
    date_value.value = new_date;
    emitter.emit("update-obsolescence-date-property", new_date);

    uses_helper_validity.value = false;
}
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
