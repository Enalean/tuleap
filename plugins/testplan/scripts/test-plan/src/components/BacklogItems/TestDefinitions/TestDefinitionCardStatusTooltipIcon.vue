<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <span
        class="tlp-tooltip test-plan-test-definition-test-status-tooltip-position"
        v-bind:data-tlp-tooltip="tooltip_content"
        data-test="test-status"
    >
        <i
            class="fa fa-fw"
            v-bind:class="icon_status"
            aria-hidden="true"
            data-test="test-status-icon"
        ></i>
    </span>
</template>
<script setup lang="ts">
import type { TestDefinition } from "../../../type";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../../store/type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    test_definition: TestDefinition;
}>();

const { milestone_title } = useState<Pick<State, "milestone_title">>(["milestone_title"]);

const { interpolate, $gettext } = useGettext();

const tooltip_content = computed((): string => {
    switch (props.test_definition.test_status) {
        case "passed":
            return $gettext("Passed");
        case "failed":
            return $gettext("Failed");
        case "blocked":
            return $gettext("Blocked");
        case "notrun":
            return $gettext("Not run");
        default:
            return interpolate($gettext("Not planned in release %{ release_name }"), {
                release_name: milestone_title.value,
            });
    }
});

const icon_status = computed((): string => {
    switch (props.test_definition.test_status) {
        case "passed":
            return "fa-check-circle test-plan-test-definition-icon-status-passed";
        case "failed":
            return "fa-times-circle test-plan-test-definition-icon-status-failed";
        case "blocked":
            return "fa-exclamation-circle test-plan-test-definition-icon-status-blocked";
        case "notrun":
            return "fa-question-circle test-plan-test-definition-icon-status-notrun";
        default:
            return "fa-circle-thin test-plan-test-definition-icon-status-notplanned";
    }
});
</script>
<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
