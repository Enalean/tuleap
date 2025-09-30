<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <aside class="aside" v-bind:class="is_expanded ? 'is-aside-expanded' : 'is-aside-collapsed'">
        <div
            class="tlp-framed sidebar-contents"
            v-bind:class="{ 'sidebar-contents-with-tabs': are_versions_displayed }"
        >
            <button
                class="tlp-button-mini tlp-button-primary tlp-button-outline sidebar-button"
                v-on:click="toggle"
                v-bind:title="title"
            >
                <i v-bind:class="icon" role="img"></i>
            </button>
            <div class="sidebar-contents-container">
                <document-sidebar-header
                    v-on:switch-sidebar-tab="switchTab"
                    v-bind:current_tab="current_tab"
                />
                <table-of-contents v-if="current_tab === TOC_TAB" />
                <artidoc-versions v-if="current_tab === VERSIONS_TAB" />
            </div>
        </div>
    </aside>
</template>

<script setup lang="ts">
import TableOfContents from "./toc/TableOfContents.vue";
import { computed, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";
import DocumentSidebarHeader from "@/components/sidebar/DocumentSidebarHeader.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";
import type { SidebarTab } from "@/components/sidebar/document-sidebar";
import { VERSIONS_TAB, TOC_TAB } from "@/components/sidebar/document-sidebar";
import ArtidocVersions from "@/components/sidebar/versions/ArtidocVersions.vue";
import { REGISTER_VERSIONS_SHORTCUT_HANDLER } from "@/register-shortcut-handler-injection-keys";

const are_versions_displayed = strictInject(ARE_VERSIONS_DISPLAYED);

const { $gettext } = useGettext();

const current_tab = ref<SidebarTab>(TOC_TAB);

watch(are_versions_displayed, (are_versions_displayed) => {
    if (!are_versions_displayed) {
        current_tab.value = TOC_TAB;
    }
});

const is_expanded = ref(true);

const icon = computed(() =>
    is_expanded.value ? "fa-solid fa-chevron-right" : "fa-solid fa-chevron-left",
);
const title = computed(() =>
    is_expanded.value ? $gettext("Close sidebar") : $gettext("Open sidebar"),
);

function toggle(): void {
    is_expanded.value = !is_expanded.value;
}

function switchTab(tab: SidebarTab): void {
    current_tab.value = tab;
}

strictInject(REGISTER_VERSIONS_SHORTCUT_HANDLER)(() => {
    are_versions_displayed.value = true;
    switchTab(VERSIONS_TAB);
});
</script>

<style scoped lang="scss">
@use "@/themes/includes/viewport-breakpoint";
@use "@/themes/includes/zindex";

aside {
    --artidoc-sidebar-title-vertical-margin: var(--tlp-small-spacing);
    --artidoc-sidebar-title-height: var(--tlp-form-element-small-height);
    --artidoc-sidebar-title-total-height: calc(
        var(--artidoc-sidebar-title-height) + 2 * var(--artidoc-sidebar-title-vertical-margin)
    );
    --artidoc-sidebar-content-height: calc(
        var(--artidoc-container-height) - var(--artidoc-sidebar-title-total-height)
    );
    --artidoc-sidebar-background-color: var(--tlp-fade-background-color);

    position: relative;
    z-index: zindex.$toc;
    order: 1;
    background: var(--artidoc-sidebar-background-color);
}

$button-height: 65px;
$button-width: var(--artidoc-sidebar-button-width);

.sidebar-button {
    position: absolute;
    top: calc(
        var(--artidoc-sidebar-title-height) + var(--artidoc-sidebar-title-vertical-margin) +
            var(--artidoc-sidebar-content-height) / 2 - #{$button-height} / 2
    );
    left: calc(-1 * #{$button-width});
    width: $button-width;
    height: $button-height;
    overflow: visible;
    border: 1px solid var(--artidoc-sidebar-background-color);
    border-right: 0;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    background: var(--artidoc-sidebar-background-color);

    // This pseudo element is used to hide the right side of the button so that we don't see
    // the drop'shadow (there is no way to indicate that we want drop shadow only on one side
    // in css).
    &::before {
        content: "";
        position: absolute;
        top: 0;
        right: -1px;
        width: 1px;
        height: $button-height;
        background-color: var(--artidoc-sidebar-background-color);
    }
}

.sidebar-contents {
    position: sticky;
    top: calc(var(--artidoc-sticky-top-position) + var(--artidoc-sidebar-title-vertical-margin));
    padding: 0;
}

.sidebar-contents-with-tabs {
    top: var(--artidoc-sticky-top-position);
}

.is-aside-collapsed > .sidebar-contents {
    > .sidebar-contents-container,
    > .sidebar-button::before {
        display: none;
    }
}

@media screen and (max-width: #{viewport-breakpoint.$small-screen-size}) {
    .sidebar-contents {
        top: 0;
    }

    .is-aside-collapsed > .sidebar-contents > .sidebar-contents-container {
        display: block;
    }

    aside {
        order: -1;
        height: fit-content;
        border-bottom: 1px solid var(--tlp-neutral-normal-color);
    }

    .sidebar-button {
        display: none;
    }
}
</style>
