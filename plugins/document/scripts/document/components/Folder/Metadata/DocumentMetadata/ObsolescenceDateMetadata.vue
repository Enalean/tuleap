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
                <slot></slot>
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
    </div>
</template>

<script>
import { datePicker } from "tlp";
import { mapState } from "vuex";
import moment from "moment";

export default {
    name: "ObsolescenceDateMetadata",
    props: {
        value: String
    },
    data() {
        return {
            selectDateValue: "permanent"
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
            this.selectDateValue = event.target.value;
            const current_date = moment();
            let date;
            switch (this.selectDateValue) {
                case "permanent":
                    date = null;
                    break;
                case "today":
                    date = current_date.format("YYYY-MM-DD");
                    break;
                default:
                    date = moment(current_date, "YYYY-MM-DD")
                        .add(this.selectDateValue, "M")
                        .format("YYYY-MM-DD");
            }
            this.$emit("input", date);
        },
        inputDate(event) {
            const input_date_value = event.target.value;
            if (input_date_value && this.value !== input_date_value) {
                this.setSelectDate("fixed");
            }
            this.$emit("input", input_date_value);
        },
        setSelectDate(value) {
            this.selectDateValue = value;
            this.$refs.selectDateValue.value = value;
        }
    }
};
</script>
