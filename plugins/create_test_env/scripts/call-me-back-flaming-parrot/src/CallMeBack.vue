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
    <div class="call-me-back dropup">
        <button class="call-me-back-button dropdown-toggle" data-toggle="dropdown">
            <i class="icon-comments-alt"></i>
        </button>
        <form class="call-me-back-form dropdown-menu pull-right dropup" v-on:click.stop>
            <p class="call-me-back-form-intro">
                <translate>Fill this form and we'll call you back.</translate>
            </p>
            <label for="call-me-back-phone-number">
                <translate>Phone number</translate> <i class="icon-asterisk"></i>
            </label>
            <div class="input-prepend">
                <span class="add-on">
                    <i class="icon-phone"></i>
                </span>
                <input type="text" id="call-me-back-phone-number" name="phone" placeholder="+33 0 00 00 00 00">
            </div>
            <label for="call-me-back-date">
                <translate>Call me back on</translate> <i class="icon-asterisk"></i>
            </label>
            <div class="input-prepend">
                <span class="add-on">
                    <i class="icon-calendar"></i>
                </span>
                <input type="text" id="call-me-back-date" name="date" placeholder="yyyy-mm-dd">
            </div>
            <button type="button" class="btn btn-primary btn-large">
                <translate>Call me back</translate>
            </button>
        </form>
    </div>
</template>

<script>
export default {
    name: 'CallMeBack'
};
</script>
