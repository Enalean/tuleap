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
    <button
        v-if="is_fullscreen_enabled_in_browser"
        class="tlp-button-primary tlp-button-outline tlp-button-small switch-to-fullscreen"
        v-bind:title="button_title"
        v-on:click="toggleFullscreenMode()"
    >
        <i class="fa" v-bind:class="button_icon_class" aria-hidden="true"></i>
    </button>
</template>
<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useNamespacedState, useNamespacedMutations } from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import fscreen from "fscreen";
import type { FullscreenState } from "../../../store/fullscreen/type";

const { $gettext } = useGettext();

const { is_taskboard_in_fullscreen_mode } = useNamespacedState<FullscreenState>("fullscreen", [
    "is_taskboard_in_fullscreen_mode",
]);
const { setIsTaskboardInFullscreenMode } = useNamespacedMutations("fullscreen", [
    "setIsTaskboardInFullscreenMode",
]);

const button_title = $gettext("Toggle fullscreen mode");
const button_icon_class = computed((): string =>
    is_taskboard_in_fullscreen_mode.value ? "fa-compress" : "fa-expand",
);
const is_fullscreen_enabled_in_browser = computed((): boolean => fscreen.fullscreenEnabled);

onMounted(() => {
    fscreen.addEventListener(
        "fullscreenchange",
        () => {
            setIsTaskboardInFullscreenMode(fscreen.fullscreenElement !== null);
        },
        false,
    );
});

function toggleFullscreenMode(): void {
    const taskboard: HTMLElement | null = document.querySelector(".taskboard");

    if (!taskboard) {
        return;
    }

    if (fscreen.fullscreenElement !== null) {
        fscreen.exitFullscreen();
    } else {
        fscreen.requestFullscreen(taskboard);
    }
}
</script>
