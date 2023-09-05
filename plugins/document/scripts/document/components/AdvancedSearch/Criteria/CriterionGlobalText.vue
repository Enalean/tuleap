<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element document-search-criterion document-search-criterion-text">
        <div class="document-search-criterion-with-popover">
            <label class="tlp-label" for="document-global-search">
                {{ $gettext("Global search") }}
            </label>

            <search-information-popover v-bind:description="popover_description" />
        </div>

        <input
            type="text"
            class="tlp-input"
            id="document-global-search"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
            data-test="global-search"
        />
    </div>
</template>

<script setup lang="ts">
import SearchInformationPopover from "./SearchInformationPopover.vue";
import { useGettext } from "vue3-gettext";
import { computed } from "vue";

const { $gettext } = useGettext();

defineProps<{ value: string }>();

const popover_description = computed((): string => {
    return $gettext(
        "Global search will search in all text properties of document (but does not look inside the document).",
    );
});
</script>
