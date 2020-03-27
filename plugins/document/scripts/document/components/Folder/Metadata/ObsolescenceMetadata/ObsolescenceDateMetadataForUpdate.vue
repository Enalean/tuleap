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
        v-if="is_obsolescence_date_metadata_used"
        data-test="obsolescence-date-metadata"
    >
        <label class="tlp-label" for="document-obsolescence-date-update">
            <translate>Obsolescence date</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element document-obsolescence-date-metadata-select">
            <select
                class="tlp-select document-obsolescence-date-metadata-select"
                id="document-obsolescence-date-select-update"
                v-model="selected_date_value"
                v-on:change="updateDatePickerValue"
                ref="selectDateValue"
                data-test="document-obsolescence-date-select-update"
            >
                <option name="permanent" value="permanent" v-translate>Permanent</option>
                <option name="3months" value="3" v-translate>3 months</option>
                <option name="6months" value="6" v-translate>6 months</option>
                <option name="12months" value="12" v-translate>12 months</option>
                <option name="fixedDate" value="fixed" v-translate>Fixed date</option>
                <option name="today" value="today" v-translate>Obsolete today</option>
            </select>
            <div class="tlp-form-element-prepend">
                <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
                <date-flat-picker
                    v-bind:id="'document-obsolescence-date-update'"
                    v-bind:required="true"
                    v-model="obsolescence_date"
                    ref="input"
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

<script>
import { mapState } from "vuex";
import { getObsolescenceDateValueInput } from "../../../../helpers/metadata-helpers/obsolescence-date-value.js";
import DateFlatPicker from "../DateFlatPicker.vue";

export default {
    name: "ObsolescenceDateMetadataForUpdate",
    components: { DateFlatPicker },
    props: {
        value: String,
    },
    data() {
        return {
            date_value: this.value,
            selected_value: "",
            error_message: "",
            uses_helper_validity: false,
        };
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"]),
        obsolescence_date: {
            get() {
                return this.date_value;
            },
            set(value) {
                if (!this.uses_helper_validity) {
                    this.selected_value = "fixed";
                }
                this.date_value = value;
                this.$emit("input", value);

                this.uses_helper_validity = false;
            },
        },
        selected_date_value: {
            get() {
                return this.selected_value;
            },
            set(value) {
                this.selected_value = value;
            },
        },
    },
    mounted() {
        if (this.value !== "") {
            this.selected_value = "fixed";
        } else {
            this.selected_value = "permanent";
        }
    },
    methods: {
        updateDatePickerValue(event) {
            const input_date_value = getObsolescenceDateValueInput(event.target.value);

            this.uses_helper_validity = true;

            this.selected_value = event.target.value;
            this.obsolescence_date = input_date_value;
        },
    },
};
</script>
