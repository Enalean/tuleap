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
    <div class="past-release closed-release-header-badge">
        <i class="release-remaining-icon fa fa-flag-checkered"></i>
        <span class="release-remaining-value" data-test="points-initial-value">
            {{ formatPoints }}
        </span>
        <span class="release-remaining-text">{{ pts_done }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MilestoneData } from "../../../type";
import { useGettext } from "vue3-gettext";

const { $ngettext } = useGettext();

const props = defineProps<{ release_data: MilestoneData }>();

const formatPoints = computed((): number => {
    return props.release_data.initial_effort ?? 0;
});

const pts_done = computed((): string => {
    return $ngettext("pt done", "pts done", formatPoints.value);
});
</script>
