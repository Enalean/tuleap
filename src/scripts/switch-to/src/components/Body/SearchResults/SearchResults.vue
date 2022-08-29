<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <div class="switch-to-search-results" v-if="should_be_displayed">
        <h2 class="tlp-modal-subtitle switch-to-modal-body-title">
            {{ $gettext("Search results") }}
        </h2>
        <p class="tlp-text-muted">{{ $gettext("No results") }}</p>
    </div>
</template>

<script setup lang="ts">
import { useGetters } from "vuex-composition-helpers";
import type { RootGetters } from "../../../store/getters";
import { computed } from "vue";

const { filtered_projects, filtered_history } = useGetters<
    Pick<RootGetters, "filtered_projects" | "filtered_history">
>(["filtered_projects", "filtered_history"]);

const should_be_displayed = computed(
    (): boolean =>
        filtered_history.value.entries.length === 0 && filtered_projects.value.length === 0
);
</script>
