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
        v-if="config.project.linked_projects !== null"
        id="project-sidebar-linked-projects"
        ref="popover_anchor"
        class="project-sidebar-linked-projects"
    >
        <span class="project-sidebar-linked-projects-title">
            <span
                v-if="config.project.linked_projects.is_in_children_projects_context"
                class="project-sidebar-linked-projects-icon"
            >
                <i class="fa-solid fa-fw fa-tlp-project-boxes" aria-hidden="true"></i>
            </span>
            <span v-else class="project-sidebar-linked-projects-icon">
                <i
                    class="fa-solid fa-fw fa-turn-up fa-flip-horizontal project-sidebar-linked-projects-icon-parent"
                    aria-hidden="true"
                ></i>
            </span>
            <span class="project-sidebar-linked-projects-label">
                {{ config.project.linked_projects.label }}
            </span>
        </span>
        <ul
            class="project-sidebar-linked-projects-list"
            data-test="nav-bar-linked-projects"
            v-if="can_display_linked_projects_in_sidebar"
        >
            <li
                v-for="project in config.project.linked_projects.projects"
                v-bind:key="project.href"
                class="project-sidebar-linked-projects-item"
            >
                <a
                    v-bind:href="sanitizeURL(project.href)"
                    class="project-sidebar-linked-projects-item-link"
                >
                    <span
                        v-if="project.icon !== ''"
                        class="project-sidebar-linked-projects-item-icon"
                        aria-hidden="true"
                    >
                        {{ project.icon }}
                    </span>
                    <i
                        v-else
                        class="project-sidebar-linked-projects-item-icon fa-solid fa-box-archive"
                        aria-hidden="true"
                    ></i>
                    <span class="project-sidebar-linked-projects-item-name">
                        {{ project.name }}
                    </span>
                </a>
            </li>
        </ul>
    </div>
    <div
        v-if="config.project.linked_projects !== null"
        id="project-sidebar-linked-projects-popover"
        ref="popover_content"
        class="tlp-popover project-sidebar-linked-projects-popover"
        v-bind:class="{
            'project-sidebar-linked-projects-popover-nb-max-exceeded': is_nb_max_exceeded,
        }"
        data-test="popover"
    >
        <div class="tlp-popover-arrow project-sidebar-linked-projects-popover-arrow"></div>
        <div class="tlp-popover-header">
            <i
                v-if="config.project.linked_projects.is_in_children_projects_context"
                class="fa-solid fa-fw fa-tlp-project-boxes"
                aria-hidden="true"
            ></i>
            <i
                v-else
                class="fa-solid fa-fw fa-turn-up fa-flip-horizontal project-sidebar-linked-projects-icon-parent"
                aria-hidden="true"
            ></i>
            <h1 class="tlp-popover-title">
                {{ config.project.linked_projects.label }}
            </h1>
        </div>
        <div class="tlp-popover-body project-sidebar-linked-projects-popover-content">
            <ul class="project-sidebar-linked-projects-list-popover">
                <li
                    v-for="project in config.project.linked_projects.projects"
                    v-bind:key="project.href"
                    class="project-sidebar-linked-projects-item"
                >
                    <div class="project-sidebar-linked-projects-item-link">
                        <span
                            v-if="project.icon !== ''"
                            class="project-sidebar-linked-projects-item-icon"
                            aria-hidden="true"
                        >
                            {{ project.icon }}
                        </span>
                        <i
                            v-else
                            class="project-sidebar-linked-projects-item-icon fa-solid fa-box-archive"
                            aria-hidden="true"
                        ></i>
                        <span class="project-sidebar-linked-projects-item-name">
                            {{ project.name }}
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { SIDEBAR_CONFIGURATION } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { createPopover } from "@tuleap/tlp-popovers";
import { sanitizeURL } from "../url-sanitizer";

const config = strictInject(SIDEBAR_CONFIGURATION);

const popover_content = ref<InstanceType<typeof HTMLElement>>();
const popover_anchor = ref<InstanceType<typeof HTMLElement>>();

const is_nb_max_exceeded = ref<boolean>(
    config.value.project.linked_projects !== null &&
        config.value.project.linked_projects.projects.length >
            (config.value.project.linked_projects.nb_max_projects_before_popover ?? 5),
);
const can_display_linked_projects_in_sidebar = ref<boolean>(!is_nb_max_exceeded.value);

onMounted(() => {
    if (popover_anchor.value !== undefined && popover_content.value !== undefined) {
        createPopover(popover_anchor.value, popover_content.value, {
            placement: "right-start",
        });
    }
});
</script>
