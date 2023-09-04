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
<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import fscreen from "fscreen";

const fullscreen = namespace("fullscreen");

@Component
export default class SwitchToFullscreen extends Vue {
    @fullscreen.State
    readonly is_taskboard_in_fullscreen_mode!: boolean;

    @fullscreen.Mutation
    setIsTaskboardInFullscreenMode!: (is_in_fullscreen_mode: boolean) => void;

    get button_title(): string {
        return this.$gettext("Toggle fullscreen mode");
    }

    get button_icon_class(): string {
        return this.is_taskboard_in_fullscreen_mode ? "fa-compress" : "fa-expand";
    }

    get is_fullscreen_enabled_in_browser(): boolean {
        return fscreen.fullscreenEnabled;
    }

    mounted(): void {
        fscreen.addEventListener(
            "fullscreenchange",
            () => {
                this.setIsTaskboardInFullscreenMode(fscreen.fullscreenElement !== null);
            },
            false,
        );
    }

    toggleFullscreenMode(): void {
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
}
</script>
