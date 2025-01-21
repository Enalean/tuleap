<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="switch-to-expert">
        <div class="tlp-switch tlp-switch-mini">
            <input
                type="checkbox"
                v-bind:id="switch_to_expert_id"
                class="tlp-switch-checkbox"
                v-bind:checked="is_in_expert_mode"
                v-on:click="switchMode"
                disabled
                data-test="switch-to-expert-input"
            />
            <label
                v-bind:for="switch_to_expert_id"
                class="tlp-switch-button"
                data-test="switch-mode"
            ></label>
        </div>
        <label class="tlp-label switch-label" v-bind:for="switch_to_expert_id"
            >{{ $gettext("Expert mode") }}
            <a
                v-bind:href="`${doc_base_url}/user-guide/tql/cross-tracker-search.html#expert-mode`"
                v-bind:data-tlp-tooltip="
                    $gettext('Click to see the documentation for advanced mode')
                "
                class="tlp-tooltip tlp-tooltip-right"
                v-if="is_in_expert_mode"
                data-test="documentation-helper"
            >
                <i class="fa-solid fa-circle-info xts-link-helper" aria-hidden="true"></i
            ></a>
        </label>
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { DOCUMENTATION_BASE_URL, REPORT_ID } from "../injection-symbols";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
defineProps<{
    is_in_expert_mode: boolean;
}>();

export type SwitchModeEvent = { readonly is_expert_mode: boolean };

const report_id = strictInject(REPORT_ID);
const doc_base_url = strictInject(DOCUMENTATION_BASE_URL);

const { $gettext } = useGettext();

const switch_to_expert_id = computed((): string => {
    return "toggle-" + report_id;
});

function switchMode(): void {
    // There is only one mode: expert. This code should be removed in a future cleanup
    // emit("switch-to-query-mode", {
    //     is_expert_mode: !props.is_in_expert_mode,
    // });
}
</script>

<style scoped lang="scss">
.switch-to-expert {
    display: flex;
}

.switch-label {
    margin: 0 0 0 var(--tlp-small-spacing);
}

.xts-link-helper {
    color: var(--tlp-dimmed-color);
}
</style>
