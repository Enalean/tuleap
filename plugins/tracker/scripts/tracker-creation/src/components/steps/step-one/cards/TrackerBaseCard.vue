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
    <div
        class="tracker-creation-template-card"
        v-bind:class="{
            'tracker-creation-template-card-active': is_option_active,
        }"
    >
        <label class="tlp-card tlp-card-selectable tracker-creation-template-card-label">
            <input
                type="radio"
                class="tracker-creation-template-card-radio-button"
                name="selected-option"
                v-bind:data-test="`selected-option-${optionName}`"
                v-on:change="setActiveOption(optionName)"
            />
            <slot name="content" v-bind:is-option-active="is_option_active"></slot>
        </label>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useState, useMutations } from "vuex-composition-helpers";
import type { CreationOptions } from "../../../../store/type";

const props = defineProps<{
    optionName: string | CreationOptions;
}>();

const { active_option } = useState(["active_option"]);
const { setActiveOption } = useMutations(["setActiveOption"]);

const is_option_active = computed((): boolean => {
    return active_option.value === props.optionName;
});
</script>
