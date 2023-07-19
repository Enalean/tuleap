<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
            {{ $gettext("Wiki page") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa-brands fa-wikipedia-w"></i>
            </span>
            <input
                type="text"
                class="tlp-input"
                id="document-new-item-wiki-page-name"
                name="page-name"
                v-bind:placeholder="`${$gettext('My wiki page')}`"
                required
                v-bind:value="page_name"
                v-on:input="onInput"
                data-test="document-new-item-wiki-page-name"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { isWiki } from "../../../../helpers/type-check-helper";
import type { Item, WikiProperties } from "../../../../type";
import { computed } from "vue";
import emitter from "../../../../helpers/emitter";

const props = defineProps<{ value: WikiProperties; item: Item }>();

const is_displayed = computed((): boolean => {
    return isWiki(props.item);
});

function onInput($event: Event): void {
    if ($event.target instanceof HTMLInputElement) {
        emitter.emit("update-wiki-properties", $event.target.value);
    }
}
</script>
