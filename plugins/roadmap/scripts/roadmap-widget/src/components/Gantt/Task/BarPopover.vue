<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <section
        class="tlp-popover crossref-tooltip roadmap-gantt-task-popover"
        v-bind:class="popover_class"
        ref="popover"
    >
        <div class="tlp-popover-arrow roadmap-gantt-task-popover-arrow"></div>
        <div
            class="tlp-popover-header roadmap-gantt-task-popover-header"
            v-bind:class="header_class"
        >
            <h1 class="tlp-popover-title roadmap-gantt-task-popover-title">
                <span class="roadmap-gantt-task-popover-xref">{{ task.xref }}</span>
                <span class="roadmap-gantt-task-popover-task">{{ task.title }}</span>
            </h1>
        </div>
        <div
            class="tlp-popover-body crossref-tooltip-body roadmap-gantt-task-popover-body"
            v-if="is_loading"
        >
            <p>
                <span class="tlp-skeleton-text"></span>
                <span class="tlp-skeleton-text"></span>
            </p>
        </div>
        <div
            class="tlp-popover-body crossref-tooltip-body roadmap-gantt-task-popover-body"
            v-else
            v-dompurify-html="body"
        ></div>
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { Task } from "../../../type";
import { retrieveTooltipData } from "@tuleap/tooltip";
import { useMutationObserver } from "@vueuse/core";

const props = defineProps<{ task: Task }>();

const header_class = computed(
    (): string => "roadmap-gantt-task-popover-header-" + props.task.color_name,
);

const popover_class = computed((): string =>
    props.task.is_milestone ? "roadmap-gantt-task-milestone-popover" : "",
);

const is_loading = ref(true);
const can_load = ref(false);
const body = ref("");
const popover = ref<HTMLElement | null>(null);

watch(can_load, async (can_load) => {
    if (!can_load) {
        return;
    }

    const url = new URL("/plugins/tracker/", window.location.href);
    url.searchParams.append("aid", String(props.task.id));
    const data = await retrieveTooltipData(url);
    is_loading.value = false;
    if (data) {
        body.value = typeof data === "string" ? data : data.body_as_html;
    }
});

const observer = useMutationObserver(
    popover,
    (mutations) => {
        for (const mutation of mutations) {
            const target = mutation.target;
            if (target instanceof HTMLElement && target.classList.contains("tlp-popover-shown")) {
                can_load.value = true;
                observer.stop();
            }
        }
    },
    {
        attributes: true,
        attributeFilter: ["class"],
    },
);
</script>
