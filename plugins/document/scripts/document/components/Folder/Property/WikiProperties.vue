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
  -
  -->

<template>
    <div class="tlp-form-element" v-if="is_displayed">
        <label class="tlp-label" for="document-new-item-wiki-page-name">
            <translate>Wiki page</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa fa-wikipedia-w"></i>
            </span>
            <input
                type="text"
                class="tlp-input"
                id="document-new-item-wiki-page-name"
                name="page-name"
                v-bind:placeholder="placeholder"
                required
                v-bind:value="value.page_name"
                v-on:input="$emit('input', { page_name: $event.target.value })"
                data-test="document-new-item-wiki-page-name"
            />
        </div>
    </div>
</template>
<script>
import { TYPE_WIKI } from "../../../constants.js";
export default {
    props: {
        value: Object,
        item: Object,
    },
    computed: {
        is_displayed() {
            return this.item.type === TYPE_WIKI;
        },
        placeholder() {
            return this.$gettext("My wiki page");
        },
    },
};
</script>
