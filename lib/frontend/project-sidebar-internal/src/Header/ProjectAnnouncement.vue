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
    <button
        v-if="config && config.project.has_project_announcement"
        type="button"
        aria-labelledby="current-project-banner-message"
        class="project-sidebar-banner-button"
        v-bind:title="config.internationalization.show_project_announcement"
        v-on:click="triggerShowProjectAnnouncement()"
    >
        <span class="project-sidebar-banner-button-icon">
            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
        </span>
        <span class="project-sidebar-banner-button-label">
            {{ config.internationalization.project_announcement }}
        </span>
    </button>
</template>
<script setup lang="ts">
import { SIDEBAR_CONFIGURATION, TRIGGER_SHOW_PROJECT_ANNOUNCEMENT } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";

const config = strictInject(SIDEBAR_CONFIGURATION);

const triggerShowProjectAnnouncement = strictInject(TRIGGER_SHOW_PROJECT_ANNOUNCEMENT);
</script>
