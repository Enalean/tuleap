<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="tlp-alert-warning">
        {{ $gettext("This section has been edited by someone else.") }}
        {{
            $gettext(
                "Do you want to continue to force save your modification, or discard it and load the new one?",
            )
        }}
        <div class="alert-warning-buttons">
            <button
                type="button"
                class="tlp-button-warning"
                data-test="force-save"
                v-on:click="save_section.forceSave"
            >
                {{ $gettext("Force save") }}
            </button>
            <button
                type="button"
                class="tlp-button-warning"
                data-test="refresh"
                v-on:click="refresh_section.refreshSection"
            >
                {{ $gettext("Load new section") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { RefreshSection } from "@/sections/update/SectionRefresher";
import type { SaveSection } from "@/sections/save/SectionSaver";

defineProps<{
    save_section: SaveSection;
    refresh_section: RefreshSection;
}>();

const { $gettext } = useGettext();
</script>

<style scoped lang="scss">
.alert-warning-buttons {
    display: flex;
    gap: var(--tlp-medium-spacing);
    margin: var(--tlp-small-spacing) 0 0;
}
</style>
