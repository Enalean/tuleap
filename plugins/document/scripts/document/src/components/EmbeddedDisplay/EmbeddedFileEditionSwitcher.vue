<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-button-bar document-view-switcher">
        <div class="tlp-button-bar-item" v-bind:title="large_view_title">
            <input
                type="radio"
                name="view-switcher"
                id="view-switcher-large"
                class="tlp-button-bar-checkbox"
                value="large"
                v-bind:checked="is_embedded_in_large_view"
                v-on:click="switchToLargeView()"
                data-test="view-switcher-large"
            />
            <label
                for="view-switcher-large"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="large_view_title"
            >
                <i class="fa-solid fa-tlp-text-large"></i>
            </label>
        </div>
        <div class="tlp-button-bar-item" v-bind:title="narrow_view_title">
            <input
                type="radio"
                name="view-switcher"
                id="view-switcher-narrow"
                class="tlp-button-bar-checkbox"
                value="narrow"
                v-bind:checked="!is_embedded_in_large_view"
                data-test="view-switcher-narrow"
                v-on:click="switchToNarrowView()"
            />
            <label
                for="view-switcher-narrow"
                class="tlp-button-primary tlp-button-outline tlp-button-small"
                v-bind:title="narrow_view_title"
            >
                <i class="fa-solid fa-tlp-text-narrow"></i>
            </label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useState, useStore } from "vuex-composition-helpers";
import type { EmbeddedFileDisplayPreference, RootState } from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT, USER_ID } from "../../configuration-keys";
import {
    displayEmbeddedInLargeMode,
    displayEmbeddedInNarrowMode,
} from "../../helpers/preferences/embedded-file-display-preferences";

const $store = useStore();

const props = defineProps<{
    embedded_file_display_preference: EmbeddedFileDisplayPreference;
}>();

const emit = defineEmits<{
    (e: "update_display_preference", value: EmbeddedFileDisplayPreference): void;
}>();

const { $gettext } = useGettext();
const narrow_view_title = ref($gettext("Narrow view"));
const large_view_title = ref($gettext("Large view"));

const user_id = strictInject(USER_ID);
const project = strictInject(PROJECT);

const { currently_previewed_item } = useState<RootState>(["currently_previewed_item"]);

const is_embedded_in_large_view = computed(
    () => props.embedded_file_display_preference === EMBEDDED_FILE_DISPLAY_LARGE,
);

async function switchToLargeView(): Promise<void> {
    if (currently_previewed_item.value) {
        (
            await displayEmbeddedInLargeMode(
                $store,
                currently_previewed_item.value,
                user_id,
                project.id,
            )
        ).apply((value: EmbeddedFileDisplayPreference) => emit("update_display_preference", value));
    }
}

async function switchToNarrowView(): Promise<void> {
    if (currently_previewed_item.value) {
        (
            await displayEmbeddedInNarrowMode(
                $store,
                currently_previewed_item.value,
                user_id,
                project.id,
            )
        ).apply((value: EmbeddedFileDisplayPreference) => emit("update_display_preference", value));
    }
}
</script>
