<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <button
        class="tlp-button-primary tlp-button-mini tlp-button-outline ask-ai-button"
        v-on:click="askAIInWidget()"
    >
        <a-i-icon height="14" />

        {{ $gettext("Toggle Tuleap AI Assistant") }}
    </button>
</template>

<script setup lang="ts">
import { EMITTER } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { useGettext } from "vue3-gettext";
import AIIcon from "./AIIcon.vue";
import { TOGGLE_AI_ASSISTANT_EVENT } from "../helpers/widget-events";

const emitter = strictInject(EMITTER);

const { $gettext } = useGettext();

function askAIInWidget(): void {
    emitter.emit(TOGGLE_AI_ASSISTANT_EVENT);
}
</script>

<style scoped lang="scss">
.ask-ai-button {
    position: absolute;
    z-index: 1;
    right: var(--tlp-medium-spacing);
}
</style>
