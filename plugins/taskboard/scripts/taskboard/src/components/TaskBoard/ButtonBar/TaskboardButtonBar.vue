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
    <div class="taskboard-button-bar">
        <div class="taskboard-button-bar-left" v-bind:class="fullscreen_class">
            <span
                class="taskboard-button-bar-milestone-title"
                v-if="is_taskboard_in_fullscreen_mode"
            >
                {{ milestone_title }}
            </span>
        </div>
        <div class="taskboard-button-bar-right">
            <open-closed-switcher />
            <switch-to-fullscreen />
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { namespace, State } from "vuex-class";
import { Component } from "vue-property-decorator";
import SwitchToFullscreen from "./SwitchToFullscreen.vue";
import OpenClosedSwitcher from "./OpenClosedSwitcher.vue";

const fullscreen = namespace("fullscreen");

@Component({
    components: { SwitchToFullscreen, OpenClosedSwitcher },
})
export default class TaskboardButtonBar extends Vue {
    @State
    readonly milestone_title!: string;

    @fullscreen.State
    readonly is_taskboard_in_fullscreen_mode!: boolean;

    @fullscreen.Getter
    readonly fullscreen_class!: string;
}
</script>
