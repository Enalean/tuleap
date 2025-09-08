<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled': disabled }">
        <label class="tlp-label" v-bind:for="id">
            {{ $gettext("Link") }}
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            name="link"
            placeholder="https://example.com/my-service/"
            maxlength="255"
            pattern="(https?://|#|/|\?).+"
            v-bind:title="link_title"
            required
            v-bind:disabled="disabled"
            v-bind:value="link"
        />
    </div>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

defineProps<{
    id: string;
    link: string;
    disabled: boolean;
}>();

const link_title = $gettext("Please, enter a http:// or https:// link");
</script>
