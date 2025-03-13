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
<script setup lang="ts">
import SwitchToFullscreen from "./SwitchToFullscreen.vue";
import OpenClosedSwitcher from "./OpenClosedSwitcher.vue";

import { useNamespacedGetters, useNamespacedState, useState } from "vuex-composition-helpers";
import type { FullscreenState } from "../../../store/fullscreen/type";

const { milestone_title } = useState(["milestone_title"]);

const { is_taskboard_in_fullscreen_mode } = useNamespacedState<
    Pick<FullscreenState, "is_taskboard_in_fullscreen_mode">
>("fullscreen", ["is_taskboard_in_fullscreen_mode"]);

const { fullscreen_class } = useNamespacedGetters("fullscreen", ["fullscreen_class"]);
</script>
