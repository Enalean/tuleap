<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <div class="call-me-back tlp-dropdown">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="call-me-back-message" v-if="! dropdown_open && message" v-html="sanitized_message"></div>
        <button class="call-me-back-button tlp-button-primary tlp-button-large" ref="call_me_back_button">
            <i class="fa fa-phone"></i>
        </button>
        <div class="call-me-back-form tlp-dropdown-menu tlp-dropdown-menu-top tlp-dropdown-menu-right">
            <div class="call-me-back-save-the-date" v-if="save_the_date">
                <i class="fa fa-thumbs-o-up"></i>
                <p v-translate>We will call you back on</p>
                <span class="tlp-badge-success tlp-badge-outline">{{ call_me_back_formatted_date }}</span>
            </div>

            <form v-else>
                <p class="call-me-back-form-intro" v-translate>
                    Fill this form and we'll call you back.
                </p>
                <div class="tlp-alert-danger" v-if="error" v-translate>
                    Ooops something went wrong. Please try again later.
                </div>
                <div class="tlp-form-element">
                    <label class="tlp-label" for="call-me-back-phone-number">
                        <translate>Phone number</translate> <i class="fa fa-asterisk"></i>
                    </label>
                    <div class="tlp-form-element tlp-form-element-prepend">
                        <span class="tlp-prepend"><i class="fa fa-phone"></i></span>
                        <input type="text"
                               class="tlp-input"
                               id="call-me-back-phone-number"
                               name="call_me_back_phone"
                               placeholder="+33000000000"
                               v-model="call_me_back_phone"
                        >
                    </div>
                </div>
                <div class="tlp-form-element">
                    <label class="tlp-label" for="call-me-back-date">
                        <translate>Call me back on</translate> <i class="fa fa-asterisk"></i>
                    </label>
                    <div class="tlp-form-element tlp-form-element-prepend">
                        <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
                        <input type="text"
                               ref="call_me_back_date"
                               class="tlp-input tlp-input-date"
                               id="call-me-back-date"
                               name="call_me_back_date"
                               placeholder="yyyy-mm-dd"
                               v-model="call_me_back_date"
                        >
                    </div>
                </div>
                <button type="button"
                        class="tlp-button-primary tlp-button-wide"
                        v-on:click="callMeBack"
                        v-bind:disabled="loading"
                >
                    <i class="tlp-button-icon fa fa-spinner fa-spin" v-if="loading"></i>
                    <translate>Call me back</translate>
                </button>
            </form>
        </div>
    </div>
</template>

<script>
import { dropdown, datePicker } from "tlp";
import { getCallMeBackMessage, askToBeCalledBack } from "../../call-me-back-rest-querier.js";
import { DateTime } from "luxon";
import { sanitize } from "dompurify";

export default {
    name: "CallMeBack",
    data() {
        return {
            loading: false,
            error: false,
            dropdown_open: false,
            save_the_date: false,
            message: "",
            call_me_back_phone: "",
            call_me_back_date: ""
        };
    },
    computed: {
        call_me_back_formatted_date() {
            return DateTime.fromISO(this.call_me_back_date).toLocaleString(DateTime.DATE_FULL);
        },
        sanitized_message() {
            return sanitize(this.message);
        }
    },
    mounted() {
        this.getMessage();
        this.bindDropdown();
        this.bindCalendar();
    },
    methods: {
        async getMessage() {
            this.message = await getCallMeBackMessage();
        },
        bindDropdown() {
            const call_me_back_dropdown = dropdown(this.$refs.call_me_back_button);

            call_me_back_dropdown.addEventListener("tlp-dropdown-shown", () => {
                this.dropdown_open = true;
            });
            call_me_back_dropdown.addEventListener("tlp-dropdown-hidden", () => {
                this.dropdown_open = false;
            });
        },
        bindCalendar() {
            datePicker(this.$refs.call_me_back_date, {
                static: true,
                onValueUpdate: (date, string_value) => {
                    this.call_me_back_date = string_value;
                }
            });
        },
        async callMeBack() {
            this.loading = true;

            try {
                await askToBeCalledBack(this.call_me_back_phone, this.call_me_back_date);

                this.save_the_date = true;
            } catch (e) {
                const { error } = await e.response.json();
                this.error = error.code + " " + error.message;
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
