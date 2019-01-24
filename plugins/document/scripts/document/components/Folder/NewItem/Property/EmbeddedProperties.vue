<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <div class="tlp-form-element" v-if="is_displayed">
        <label
            class="tlp-label"
            for="document-new-item-embedded"
        >
            <translate>Content</translate>
        </label>
        <div class="tlp-form-element">
            <textarea type="text" class="tlp-textarea" id="document-new-item-embedded" name="embedded-content"
                      v-bind:placeholder="placeholder"
                      v-bind:value="value.content"
                      v-on:input="$emit('input', {content : $event.target.value})"
            >
            </textarea>
        </div>
    </div>
</template>

<script>
import { TYPE_EMBEDDED } from "../../../../constants.js";

export default {
    name: "EmbeddedProperties",
    props: {
        value: Object,
        item: Object
    },
    computed: {
        is_displayed() {
            return this.item.type === TYPE_EMBEDDED;
        },
        placeholder() {
            return this.$gettext("My content...");
        }
    }
};
</script>
