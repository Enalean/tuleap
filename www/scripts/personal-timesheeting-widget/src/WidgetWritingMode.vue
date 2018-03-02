/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(
<template>
    <form class="timesheeting-writing-mode">
        <div class="timesheeting-writing-mode-selected-dates">

            <div class="tlp-form-element timesheeting-writing-mode-selected-date">
                <label for="timesheeting-start-date" class="tlp-label">{{ start_date_label }}</label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
                    <input type="text"
                           class="tlp-input tlp-input-date"
                           id="timesheeting-start-date"
                           ref="start_date"
                           v-model="start_date"
                           size="11"
                    >
                </div>
            </div>

            <div class="tlp-form-element timesheeting-writing-mode-selected-date">
                <label for="timesheeting-end-date" class="tlp-label">{{ end_date_label }}</label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
                    <input type="text"
                           class="tlp-input tlp-input-date"
                           id="timesheeting-end-date"
                           ref="end_date"
                           v-model="end_date"
                           size="11"
                    >
                </div>
            </div>

        </div>
        <div class="timesheeting-writing-mode-actions">
            <button class="tlp-button-primary tlp-button-outline"
                type="button"
                v-on:click="cancel"
            >{{ cancel_label }}</button>
            <button class="tlp-button-primary timesheeting-writing-search"
                type="button"
                v-on:click="switchToReadingMode"
            >{{ search_label }}</button>
        </div>
    </form>
</template>
)(
<script>
    import { datePicker }       from 'tlp';
    import { gettext_provider } from './gettext-provider.js';

    export default {
        name: 'WidgetWritingMode',
        props: {
            readingStartDate: String,
            readingEndDate  : String
        },
        data() {
            return {
                start_date: this.readingStartDate,
                end_date  : this.readingEndDate
            };
        },
        computed: {
            start_date_label: () => gettext_provider.gettext('From'),
            end_date_label  : () => gettext_provider.gettext('To'),
            cancel_label    : () => gettext_provider.gettext('Cancel'),
            search_label    : () => gettext_provider.gettext('Search')
        },
        methods: {
            switchToReadingMode() {
                this.$emit('switchToReadingMode', {
                    start_date: this.$refs.start_date.value,
                    end_date  : this.$refs.end_date.value
                });
            },
            cancel() {
                this.$emit('switchToReadingMode');
            }
        },
        mounted() {
            [ this.$refs.start_date, this.$refs.end_date ].forEach(element => datePicker(element));
        }
    };
</script>
)
