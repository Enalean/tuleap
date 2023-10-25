<!--
  - Copyright (c) 2022-Present Enalean
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
    <div
        v-if="config.project.flags.length > 0"
        ref="popover_anchor"
        class="project-sidebar-project-flags"
    >
        <div class="project-sidebar-project-flags-icon">
            <project-shield-icon />
        </div>
        <span class="project-sidebar-project-flags-labels">
            <span
                v-for="flag in config.project.flags"
                v-bind:key="flag.label"
                class="project-sidebar-project-flags-label"
            >
                {{ flag.label }}
            </span>
        </span>
    </div>
    <div
        v-if="config.project.flags.length > 0"
        id="project-sidebar-project-flags-popover"
        ref="popover_content"
        class="tlp-popover"
    >
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-body">
            <div
                v-for="flag in config.project.flags"
                v-bind:key="flag.label"
                class="current-project-nav-flag-popover-flag"
            >
                <project-shield-icon />
                <h2 class="current-project-nav-flag-popover-content-title">
                    {{ flag.label }}
                </h2>
                <p
                    v-if="flag.description !== ''"
                    class="current-project-nav-flag-popover-content-description"
                >
                    {{ flag.description }}
                </p>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { createPopover } from "@tuleap/tlp-popovers";
import ProjectShieldIcon from "./ProjectShieldIcon.vue";

const config = strictInject(SIDEBAR_CONFIGURATION);
const popover_content = ref<InstanceType<typeof HTMLElement>>();
const popover_anchor = ref<InstanceType<typeof HTMLElement>>();
onMounted(() => {
    if (popover_anchor.value !== undefined && popover_content.value !== undefined) {
        createPopover(popover_anchor.value, popover_content.value, {
            placement: "right-start",
            trigger: "click",
        });
    }
});
</script>
