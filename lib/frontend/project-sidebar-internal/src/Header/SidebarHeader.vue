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
    <div class="project-sidebar-header-name">
        <div class="project-title-container">
            <a
                v-bind:href="config.project.href"
                class="project-sidebar-title"
                data-test="project-sidebar-title"
            >
                <span class="project-sidebar-title-icon" aria-hidden="true">
                    {{ config.project.icon }}
                </span>
                {{ config.project.name }}
            </a>
            <span class="project-title-spacer"></span>
            <a
                v-if="config.user.is_project_administrator"
                v-bind:href="sanitized_admin_link"
                class="project-administration-link"
                data-test="project-administration-link"
            >
                <i
                    class="fa-solid fa-gear project-administration-link-icon"
                    v-bind:title="config.internationalization.project_administration"
                ></i>
            </a>
        </div>
        <privacy-badge />
    </div>
    <project-announcement />
    <project-flags />
    <linked-projects v-bind:is_sidebar_collapsed="is_sidebar_collapsed" />
</template>
<script setup lang="ts">
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import PrivacyBadge from "./PrivacyBadge.vue";
import { computed } from "vue";
import { sanitizeURL } from "../url-sanitizer";
import ProjectFlags from "./ProjectFlags.vue";
import ProjectAnnouncement from "./ProjectAnnouncement.vue";
import LinkedProjects from "./LinkedProjects.vue";

defineProps<{ is_sidebar_collapsed: boolean }>();

const config = strictInject(SIDEBAR_CONFIGURATION);

const sanitized_admin_link = computed(() => sanitizeURL(config.value.project.administration_href));
</script>
