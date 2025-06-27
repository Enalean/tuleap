<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div>
        <nav class="tlp-wizard">
            <router-link v-bind:to="{ name: 'step-1' }" class="tlp-wizard-step-previous">
                {{ $gettext("Template") }}
            </router-link>
            <span class="tlp-wizard-step-current">{{ $gettext("Information") }}</span>
        </nav>

        <step-label v-bind:step_label="$gettext('Give your tracker some basic info')" />
    </div>
</template>
<script setup lang="ts">
import StepLabel from "../layout/StepLabel.vue";
</script>
