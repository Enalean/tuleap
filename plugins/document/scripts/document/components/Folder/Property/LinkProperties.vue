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
        <label class="tlp-label" for="document-new-item-link-url">
            <translate>URL</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa fa-globe"></i>
            </span>
            <input
                type="url"
                pattern="(https?|ftps?)://.+"
                class="tlp-input"
                id="document-new-item-link-url"
                name="link-url"
                placeholder="https://example.com"
                required
                v-bind:value="value.link_url"
                v-on:input="$emit('input', { link_url: $event.target.value })"
                data-test="document-new-item-link-url"
            />
        </div>
    </div>
</template>
<script>
import { TYPE_LINK } from "../../../constants.js";
export default {
    name: "LinkProperties",
    props: {
        value: Object,
        item: Object,
    },
    computed: {
        is_displayed() {
            return this.item.type === TYPE_LINK;
        },
    },
};
</script>
