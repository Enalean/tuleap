<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <tr>
        <td class="tlp-form-element">
            <span class="tlp-prepend"></span>
            <input
                type="text"
                class="tlp-input tlp-input-date"
                data-test="timetracking-date"
                v-model="date"
                ref="date_field"
                size="11"
            />
        </td>
        <td>
            <input
                type="text"
                class="tlp-input"
                id="timetracking-details-modal-add-step-field"
                size="11"
                placeholder="preparation"
                v-on:keyup.enter="validateNewTime()"
                v-model="step"
            />
        </td>
        <td class="timetracking-details-modal-buttons">
            <div
                class="tlp-form-element timetracking-details-form-element"
                v-bind:class="{ 'tlp-form-element-error': error_message }"
            >
                <input
                    type="text"
                    class="tlp-input"
                    size="11"
                    v-model="time"
                    data-test="timetracking-time"
                    v-on:keyup.enter="validateNewTime()"
                    placeholder="hh:mm"
                    required
                />
            </div>
            <button
                class="tlp-button-primary"
                type="submit"
                data-test="timetracking-submit-time"
                v-bind:disabled="is_loading"
                v-bind:class="{
                    'tlp-tooltip tlp-tooltip-bottom timetracking-tooltip': error_message,
                }"
                v-bind:data-tlp-tooltip="error_message"
                v-on:click="validateNewTime()"
            >
                <i v-bind:class="getButtonIconClass"></i>
            </button>
            <button
                class="tlp-button-primary tlp-button-outline"
                type="button"
                v-on:click="swapMode()"
            >
                <i class="fa fa-times"></i>
            </button>
        </td>
    </tr>
</template>
<script>
import {
    formatMinutes,
    formatDatetimeToYearMonthDay,
} from "@tuleap/plugin-timetracking-time-formatters";
import { TIME_REGEX } from "@tuleap/plugin-timetracking-constants";
import { datePicker } from "tlp";
import { usePersonalTimetrackingWidgetStore } from "../../store";

export default {
    name: "WidgetModalEditTime",
    props: {
        timeData: {
            type: Object,
            default: () => {
                return {};
            },
        },
    },
    setup() {
        const personal_store = usePersonalTimetrackingWidgetStore();

        return { personal_store };
    },
    data() {
        const data = this.timeData || {};
        const { date = formatDatetimeToYearMonthDay(new Date().toISOString()), step = "" } = data;
        return {
            date,
            step,
            time:
                this.timeData && this.timeData.minutes ? formatMinutes(this.timeData.minutes) : "",
            error_message: null,
            is_loading: false,
        };
    },
    computed: {
        getButtonIconClass() {
            if (this.is_loading) {
                return "fa fa-spinner";
            }
            return "fa fa-check";
        },
    },
    mounted() {
        datePicker(this.$refs.date_field, {
            static: true,
            onValueUpdate: (date, string_value) => {
                this.date = string_value;
            },
        });
    },
    methods: {
        swapMode() {
            this.$emit("swap-mode");
        },
        validateNewTime() {
            if (TIME_REGEX.test(this.time)) {
                if (this.is_loading) {
                    return;
                }
                this.is_loading = true;

                const id =
                    this.timeData && this.timeData.id
                        ? this.timeData.id
                        : this.personal_store.current_artifact.id;
                this.$emit("validate-time", this.date, id, this.time, this.step);
            } else {
                this.error_message = this.$gettext("Please check time's format (hh:mm)");
                if (!this.time) {
                    this.error_message = this.$gettext("Time is required");
                }
            }
        },
    },
};
</script>
