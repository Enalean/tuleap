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
    <div class="tlp-form-element" v-if="is_obsolescence_date_metadata_used" data-test="obsolescence-date-metadata">
        <label class="tlp-label"
               for="document-new-obsolescence-date"
        >
            <translate> Obsolescence date</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend document-obsolescence-date-metadata-select">
            <select
                class="tlp-select document-obsolescence-date-metadata-select"
                id="document-obsolescence-date-select"
                name="obsolescence-date-select"
                v-on:input="obsolescenceDateValue"
                ref="selectDateValue"
                data-test="document-obsolescence-date-select"
            >
                <option name="permanent"
                        value="permanent"
                        v-translate
                >
                    Permanent
                </option>
                <option name="3months"
                        value="3"
                        v-translate
                >
                    3 months
                </option>
                <option name="6months"
                        value="6"
                        v-translate
                >
                    6 months
                </option>
                <option name="12months"
                        value="12"
                        v-translate
                >
                    12 months
                </option>
                <option name="fixedDate"
                        value="fixed"
                        v-translate
                >
                    Fixed date
                </option>
            </select>
            <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
            <input
                type="text"
                id="document-new-obsolescence-date"
                class="tlp-input tlp-input-date"
                size="12"
                v-on:click.prevent="inputDate"
                name="input"
                data-test="document-obsolescence-date-input"
                v-bind:value="value"
                ref="input"
            >
        </div>
        <p class="tlp-text-danger" v-if="error_message.length > 0" data-test="obsolescence-date-error-message">
            {{ error_message }}
        </p>
    </div>
</template>

<script>
import { datePicker } from "tlp";
import { mapState } from "vuex";
import moment from "moment/moment";
import { getObsolescenceDateValueInput } from "../../../../helpers/metadata-helpers/obsolescence-date-value.js";
import { isDateValid } from "../../../../helpers/date-formatter.js";

export default {
    name: "ObsolescenceDateMetadataForCreate",
    props: {
        value: String
    },
    data() {
        return {
            select_date_value: "permanent",
            error_message: ""
        };
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"])
    },
    mounted() {
        datePicker(this.$refs.input);
        if (this.value) {
            this.setSelectDate("fixed");
        }
    },
    methods: {
        obsolescenceDateValue(event) {
            this.select_date_value = event.target.value;
            const date = getObsolescenceDateValueInput(this.select_date_value);
            this.$emit("input", date);
        },
        inputDate(event) {
            const input_date_value = event.target.value;
            if (input_date_value && this.value !== input_date_value) {
                this.setSelectDate("fixed");

                let error = "";
                const current_date = moment();

                if (!isDateValid(input_date_value)) {
                    error = this.$gettext("Bad date format");
                }
                if (current_date.isSameOrAfter(input_date_value, "day")) {
                    error = this.$gettext(
                        "The current date is the same or before the obsolescence date"
                    );
                }

                this.$refs.input.setCustomValidity(error);
                this.error_message = error;
            }

            this.$emit("input", input_date_value);
        },
        setSelectDate(value) {
            this.select_date_value = value;
            this.$refs.selectDateValue.value = value;
        }
    }
};
</script>
