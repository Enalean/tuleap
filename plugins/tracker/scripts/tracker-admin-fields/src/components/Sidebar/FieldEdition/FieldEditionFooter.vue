<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <div class="tlp-pane-section-submit tlp-pane-section">
        <button
            type="button"
            disabled
            class="tlp-button-danger tlp-button-outline"
            v-bind:title="$gettext('Not implemented yet')"
        >
            <i class="fa-regular fa-trash-alt" role="img" v-bind:aria-label="$gettext('Delete')" />
        </button>
        <span class="spacer"></span>
        <button
            v-on:click="router.push({ name: 'fields-usage' })"
            class="tlp-button-primary tlp-button-outline"
            type="button"
        >
            {{ $gettext("Cancel") }}
        </button>
        <button
            type="button"
            disabled
            class="tlp-button-primary"
            v-bind:title="$gettext('Not implemented yet')"
        >
            {{ $gettext("Save") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";
import { useGettext } from "vue3-gettext";

const router = useRouter();
const { $gettext } = useGettext();
</script>

<style lang="scss" scoped>
.spacer {
    flex: 1 0 auto;
}
</style>
