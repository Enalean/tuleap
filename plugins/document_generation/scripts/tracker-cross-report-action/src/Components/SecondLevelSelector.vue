<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <div>
        <h2 class="tlp-modal-subtitle">
            {{ $gettext("Second level") }}
        </h2>
        <div class="tlp-form-element">
            <project-selector v-model:project_id="project_id" />
        </div>
        <div class="tlp-form-element">
            <tracker-selector v-model:tracker="tracker" v-bind:project_id="project_id" />
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ref } from "vue";
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";
import type { SelectedTracker } from "../type";

const project_id = ref<number | null>(null);
const tracker = ref<SelectedTracker | null>(null);
</script>
