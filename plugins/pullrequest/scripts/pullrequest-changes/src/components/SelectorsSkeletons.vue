<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div class="selectors">
        <select type="text" class="tlp-input tlp-skeleton-field" disabled></select>

        <div class="diff-mode-select-skeleton">
            <button type="button" class="tlp-skeleton-field" disabled>
                <i
                    class="fa-solid fa-table-columns tlp-button-icon tlp-skeleton-icon"
                    role="img"
                    v-bind:aria-label="$gettext('Side by side diff')"
                ></i>
            </button>
            <button type="button" class="tlp-skeleton-field" disabled>
                <i
                    class="fa-solid fa-align-left tlp-button-icon tlp-skeleton-icon"
                    role="img"
                    v-bind:aria-label="$gettext('Unified diff')"
                ></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
</script>

<style lang="scss" scoped>
.diff-mode-select-skeleton {
    display: flex;

    > .tlp-skeleton-field {
        padding: 0 var(--tlp-medium-spacing);
        border-radius: 1px;

        &:first-child {
            border-right: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        &:last-child {
            border-left: 0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
    }
}
</style>
