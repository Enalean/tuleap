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
    <div class="tlp-button-bar">
        <div class="tlp-button-bar-item">
            <input
                type="checkbox"
                id="side-by-side-diff-button"
                class="tlp-button-bar-checkbox"
                v-bind:checked="current_diff_mode === SIDE_BY_SIDE_DIFF"
                v-on:click="onClick(SIDE_BY_SIDE_DIFF)"
            />
            <label
                for="side-by-side-diff-button"
                class="tlp-button-primary tlp-button-outline tlp-tooltip tlp-tooltip-bottom"
                v-bind:data-tlp-tooltip="$gettext('Side by side diff')"
                data-test="side-by-side-diff-button"
            >
                <i
                    class="fa-solid fa-table-columns tlp-button-icon"
                    role="img"
                    v-bind:aria-label="$gettext('Side by side diff')"
                ></i>
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input
                type="checkbox"
                id="unified-diff-button"
                class="tlp-button-bar-checkbox"
                v-bind:checked="current_diff_mode === UNIFIED_DIFF"
                v-on:click="onClick(UNIFIED_DIFF)"
            />
            <label
                for="unified-diff-button"
                class="tlp-button-primary tlp-button-outline tlp-tooltip tlp-tooltip-bottom"
                v-bind:data-tlp-tooltip="$gettext('Unified diff')"
                data-test="unified-diff-button"
            >
                <i
                    class="fa-solid fa-align-left tlp-button-icon"
                    role="img"
                    v-bind:aria-label="$gettext('Unified diff')"
                ></i>
            </label>
        </div>
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { CURRENT_USER_ID_KEY } from "../../constants";
import type { PullRequestDiffMode } from "./diff-modes";
import { SIDE_BY_SIDE_DIFF, UNIFIED_DIFF } from "./diff-modes";
import { setUserPreferenceForDiffDisplayMode } from "../../api/rest-querier";

const user_id = strictInject(CURRENT_USER_ID_KEY);

const props = defineProps<{
    current_diff_mode: PullRequestDiffMode;
}>();

const emit = defineEmits<{
    (e: "diff-mode-changed", file: PullRequestDiffMode): void;
}>();

const onClick = (mode: PullRequestDiffMode): void => {
    if (mode === props.current_diff_mode) {
        return;
    }

    setUserPreferenceForDiffDisplayMode(user_id, mode);
    emit("diff-mode-changed", mode);
};
</script>
