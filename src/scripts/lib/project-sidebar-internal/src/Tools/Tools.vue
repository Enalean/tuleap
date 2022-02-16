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
        {{ config.internationalization.tools }}
    </h2>
    <nav ref="tools_element" class="project-sidebar-nav">
        <tool
            v-for="tool in config.tools"
            v-bind="tool"
            v-bind:key="tool.href + tool.label + tool.description"
        />
    </nav>
</template>
<script setup lang="ts">
import { and, useActiveElement, useMagicKeys } from "@vueuse/core";
import Tool from "./Tool.vue";
import { strictInject } from "../strict-inject";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { computed, nextTick, onMounted, onUpdated, ref, watch } from "vue";
import { getServicesShortcutsGroup } from "../../../../global-shortcuts/src/plugin-access-shortcuts";

const config = strictInject(SIDEBAR_CONFIGURATION);

const tools_element = ref<InstanceType<typeof HTMLElement>>();

function setupShortcutsInteraction(): void {
    // We want to wait that everything has been rendered including child components
    nextTick((): void => {
        if (tools_element.value === undefined) {
            return;
        }
        const shortcuts_group = getServicesShortcutsGroup(tools_element.value, {
            gettext(msgid: string): string {
                return msgid;
            },
        });

        if (shortcuts_group === null) {
            return;
        }

        const keys = useMagicKeys();
        const active_element = useActiveElement();
        const not_using_input_element = computed((): boolean => {
            return (
                !active_element.value?.isContentEditable &&
                active_element.value?.tagName !== "INPUT" &&
                active_element.value?.tagName !== "TEXTAREA" &&
                active_element.value?.tagName !== "SELECT"
            );
        });

        shortcuts_group.shortcuts.forEach((shortcut): void => {
            watch(and(keys[shortcut.keyboard_inputs], not_using_input_element), (pressed): void => {
                if (pressed) {
                    const fake_event = new KeyboardEvent("keypress");
                    shortcut.handle(fake_event);
                }
            });
        });
    });
}

onMounted(setupShortcutsInteraction);
onUpdated(setupShortcutsInteraction);
</script>
