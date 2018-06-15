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
        <button class="call-me-back-button tlp-button-primary tlp-button-large" ref="call_me_back_button">
            <i class="fa fa-coffee"></i>
        </button>
        <form class="call-me-back-form tlp-dropdown-menu tlp-dropdown-menu-top tlp-dropdown-menu-right">
            <p class="call-me-back-form-intro">
                <translate>Fill this form and we'll call you back.</translate>
            </p>
            <div class="tlp-form-element">
                <label class="tlp-label" for="call-me-back-phone-number">
                    <translate>Phone number</translate> <i class="fa fa-asterisk"></i>
                </label>
                <div class="tlp-form-element tlp-form-element-prepend">
                    <span class="tlp-prepend"><i class="fa fa-phone"></i></span>
                    <input type="text"
                        class="tlp-input"
                        id="call-me-back-phone-number"
                        name="phone"
                        placeholder="+33 0 00 00 00 00"
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
                        name="date"
                        placeholder="yyyy-mm-dd"
                    >
                </div>
            </div>
            <button type="button" class="tlp-button-primary tlp-button-wide">
                <translate>Call me back</translate>
            </button>
        </form>
    </div>
</template>

<script>
import { dropdown, datePicker } from 'tlp';

export default {
    name: 'CallMeBack',
    mounted() {
        this.bindDropdown();
        this.bindCalendar();
    },
    methods: {
        bindDropdown() {
            dropdown(this.$refs.call_me_back_button);
        },
        bindCalendar() {
            datePicker(
                this.$refs.call_me_back_date,
                {
                    static: true
                }
            );
        }
    }
};
</script>
