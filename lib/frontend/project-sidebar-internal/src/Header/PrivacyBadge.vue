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
    <div ref="popover_anchor" class="sidebar-popover-anchor">
        <span ref="badge" class="project-sidebar-privacy-badge">
            <i
                class="sidebar-dashboard-privacy-icon fa-solid"
                v-bind:class="project_privacy_icon"
                data-test="project-icon"
                aria-hidden="true"
            ></i>
            {{ config?.project.privacy.privacy_title }}
        </span>
    </div>
    <div id="project-sidebar-nav-title-popover" ref="popover_content" class="tlp-popover">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">
            <h1 class="tlp-popover-title">
                {{ config?.project.privacy.privacy_title }}
            </h1>
        </div>
        <div class="tlp-popover-body">
            <p class="current-project-nav-title-popover-description">
                {{ config?.project.privacy.explanation_text }}
            </p>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { getProjectPrivacyIcon } from "@tuleap/project-privacy-helper";
import { createPopover } from "@tuleap/tlp-popovers";

const config = strictInject(SIDEBAR_CONFIGURATION);
const project_privacy_icon = computed(() =>
    config.value ? getProjectPrivacyIcon(config.value.project.privacy) : "",
);

const badge = ref<InstanceType<typeof HTMLElement>>();
const popover_content = ref<InstanceType<typeof HTMLElement>>();
const popover_anchor = ref<InstanceType<typeof HTMLElement>>();

onMounted(() => {
    if (
        badge.value !== undefined &&
        popover_content.value !== undefined &&
        popover_anchor.value !== undefined
    ) {
        createPopover(badge.value, popover_content.value, {
            placement: "right-start",
            anchor: popover_anchor.value,
            trigger: "click",
        });
    }
});
</script>
