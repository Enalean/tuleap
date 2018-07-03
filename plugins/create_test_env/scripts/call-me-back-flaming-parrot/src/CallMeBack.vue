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
    <div class="call-me-back dropup" ref="dropdown">
        <div class="call-me-back-message" v-if="! dropdown_open && message" v-html="sanitized_message"></div>
        <button class="call-me-back-button dropdown-toggle" data-toggle="dropdown">
            <i class="icon-comments-alt"></i>
        </button>
        <div class="call-me-back-form dropdown-menu pull-right dropup" v-on:click.stop>
            <div class="call-me-back-save-the-date" v-if="save_the_date">
                <i class="icon-thumbs-up-alt"></i>
                <p class="call-me-back-save-the-date-text" v-translate>
                    We will call you back on
                </p>
                <span class="badge badge-success">{{ call_me_back_formatted_date }}</span>
            </div>

            <form v-else>
                <p class="call-me-back-form-intro" v-translate>
                    Fill this form and we'll call you back.
                </p>
                <div class="alert alert-error" v-if="error" v-translate>
                    Ooops something went wrong. Please try again later.
                </div>
                <label for="call-me-back-phone-number">
                    <translate>Phone number</translate> <i class="icon-asterisk"></i>
                </label>
                <div class="input-prepend">
                    <span class="add-on">
                        <i class="icon-phone"></i>
                    </span>
                    <input type="text"
                        id="call-me-back-phone-number"
                        name="call_me_back_phone"
                        placeholder="+33000000000"
                        v-model="call_me_back_phone"
                    >
                </div>
                <label for="call-me-back-date">
                    <translate>Call me back on</translate> <i class="icon-asterisk"></i>
                </label>
                <div class="input-prepend" ref="form_element_date">
                    <span class="add-on">
                        <i class="icon-calendar"></i>
                    </span>
                    <input type="text"
                        id="call-me-back-date"
                        name="call_me_back_date"
                        data-format="yyyy-MM-dd"
                        placeholder="yyyy-mm-dd"
                        v-model="call_me_back_date"
                    >
                </div>
                <button type="button"
                    class="btn btn-primary btn-large"
                    v-on:click="callMeBack"
                    v-bind:disabled="loading"
                >
                    <i class="icon-spinner icon-spin" v-if="loading"></i>
                    <translate>Call me back</translate>
                </button>
            </form>
        </div>
    </div>
</template>

<script>
import { getCallMeBackMessage, askToBeCalledBack } from '../../call-me-back-rest-querier.js';
import { DateTime }                                from 'luxon';
import { sanitize }                                from 'dompurify';

export default {
    name: 'CallMeBack',
    props: {
        locale: String
    },
    data() {
        return {
            loading           : false,
            error             : false,
            dropdown_open     : false,
            save_the_date     : false,
            message           : '',
            call_me_back_phone: '',
            call_me_back_date : ''
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
        this.observeDropdown();
        this.bindCalendar();
    },
    methods: {
        async getMessage() {
            this.message = await getCallMeBackMessage();
        },
        observeDropdown() {
            const observer = new MutationObserver((mutations) => {
                for(const mutation of mutations) {
                    if (mutation.target.classList.contains('open')) {
                        this.dropdown_open = true;
                    } else {
                        this.dropdown_open = false;
                    }
                }
            });

            observer.observe(this.$refs.dropdown, { attributes: true });
        },
        bindCalendar() {
            jQuery(this.$refs.form_element_date).datetimepicker({
                 language: this.locale,
                 pickTime: false
            })
        },
        async callMeBack() {
            this.loading = true;

            try {
                await askToBeCalledBack(
                    this.call_me_back_phone,
                    this.call_me_back_date
                );

                this.save_the_date = true;
            } catch (e) {
                const { error } = await e.response.json();
                this.error      = error.code + ' ' + error.message;
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
