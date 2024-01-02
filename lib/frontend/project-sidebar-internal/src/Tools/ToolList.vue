<!--
  - Copyright (c) 2021-Present Enalean
  -
  - Permission is hereby granted, free of charge, to any person obtaining a copy
  - of this software and associated documentation files (the "Software"), to deal
  - in the Software without restriction, including without limitation the rights
  - to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  - copies of the Software, and to permit persons to whom the Software is
  - furnished to do so, subject to the following conditions:
  -
  - The above copyright notice and this permission notice shall be included in all
  - copies or substantial portions of the Software.
  -
  - THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  - IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  - FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  - AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  - LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  - OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  - SOFTWARE.
  -->

<template>
    <h2 class="project-sidebar-tools-section-label">
        {{ config?.internationalization.tools }}
    </h2>
    <nav ref="tools_element" class="project-sidebar-nav">
        <tool-presenter
            v-for="tool in config?.tools"
            v-bind="tool"
            v-bind:key="tool.href + tool.label + tool.description"
        />
    </nav>
</template>
<script setup lang="ts">
import { useActiveElement, useMagicKeys } from "@vueuse/core";
import ToolPresenter from "./ToolPresenter.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { nextTick, onMounted, onUpdated, ref, watch } from "vue";
import { getAvailableShortcutsFromToolsConfiguration } from "../shortcuts";

const config = strictInject(SIDEBAR_CONFIGURATION);

const tools_element = ref<InstanceType<typeof HTMLElement>>();

function setupShortcutsInteraction(): void {
    // We want to wait that everything has been rendered including child components
    nextTick((): void => {
        if (config.value === undefined) {
            return;
        }

        const element_to_check_for_shortcut = tools_element.value;
        if (element_to_check_for_shortcut === undefined) {
            return;
        }

        const shortcuts = getAvailableShortcutsFromToolsConfiguration(config.value.tools);

        if (shortcuts === null) {
            return;
        }

        const keys = useMagicKeys();
        const active_element = useActiveElement();
        function notUsingInputElement(element: HTMLElement): boolean {
            return (
                !element.isContentEditable &&
                element.tagName !== "INPUT" &&
                element.tagName !== "TEXTAREA" &&
                element.tagName !== "SELECT"
            );
        }

        shortcuts.forEach((shortcut): void => {
            watch(keys[shortcut.keyboard_inputs], (pressed): void => {
                const active_element_value = active_element.value;
                if (pressed && active_element_value && notUsingInputElement(active_element_value)) {
                    shortcut.execute(element_to_check_for_shortcut);
                }
            });
        });
    });
}

onMounted(setupShortcutsInteraction);
onUpdated(setupShortcutsInteraction);
</script>
