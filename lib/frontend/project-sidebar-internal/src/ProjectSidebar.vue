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
    <aside
        v-if="sidebar_configuration !== undefined"
        class="sidebar"
        v-bind:class="{ 'sidebar-collapsed': is_sidebar_collapsed }"
    >
        <sidebar-logo />
        <div class="sidebar-content-vertical-scroll">
            <sidebar-header
                v-if="!is_sidebar_collapsed"
                v-bind:is_sidebar_collapsed="is_sidebar_collapsed"
            />
            <tool-list v-if="!is_sidebar_collapsed" />
            <sidebar-collapse-button
                v-model:is_sidebar_collapsed="is_sidebar_collapsed"
                v-bind:can_sidebar_be_collapsed="can_sidebar_be_collapsed"
            />
        </div>
        <div class="sidebar-spacer"></div>
        <sidebar-footer />
    </aside>
</template>
<script setup lang="ts">
import { unserializeConfiguration } from "./configuration";
import { provide, readonly, computed, watch, ref } from "vue";
import SidebarHeader from "./Header/SidebarHeader.vue";
import SidebarFooter from "./SidebarFooter.vue";
import { SIDEBAR_CONFIGURATION, TRIGGER_SHOW_PROJECT_ANNOUNCEMENT } from "./injection-symbols";
import ToolList from "./Tools/ToolList.vue";
import SidebarLogo from "./SidebarLogo.vue";
import SidebarCollapseButton from "./SidebarCollapseButton.vue";

const props = defineProps<{
    config: string | undefined;
    collapsed?: boolean | undefined;
    // eslint-disable-next-line vue/prop-name-casing -- Vue transforms properties with dashes in camelCase
    noCollapseButton?: boolean | undefined;
}>();

const sidebar_configuration = readonly(computed(() => unserializeConfiguration(props.config)));

const can_sidebar_be_collapsed = readonly(
    computed(() => {
        if (props.noCollapseButton) {
            return false;
        }

        if (sidebar_configuration.value === undefined) {
            return false;
        }

        if (sidebar_configuration.value.is_collapsible === undefined) {
            // If `is_collapsible` is not given then we keep backward compatibility
            // and consider that the sidebar can be collapsed.
            return true;
        }

        return sidebar_configuration.value.is_collapsible;
    }),
);

const is_sidebar_collapsed = ref(can_sidebar_be_collapsed.value && (props.collapsed ?? false));

provide(SIDEBAR_CONFIGURATION, sidebar_configuration);

const emit = defineEmits<{
    (e: "show-project-announcement"): void;
    (e: "sidebar-collapsed"): void;
}>();

provide(TRIGGER_SHOW_PROJECT_ANNOUNCEMENT, () => {
    emit("show-project-announcement");
});

watch(is_sidebar_collapsed, (): void => {
    emit("sidebar-collapsed");
});
</script>
<style lang="scss">
@use "@tuleap/tlp-styles/components/typography";
@use "@tuleap/burningparrot-theme/css/includes/global-variables";
@use "./Styles/sidebar-generic";
@use "./Styles/sidebar-collapsed";
@use "./Styles/sidebar-project";
@use "@tuleap/burningparrot-theme/css/includes/logo";
@use "@fortawesome/fontawesome-free/scss/fontawesome";
@use "@fortawesome/fontawesome-free/scss/brands";
@use "./Styles/fontawesome-classes";
@use "@tuleap/tlp/src/fonts/tlp-font/icons";
@use "@tuleap/tlp-popovers";
@use "@tuleap/burningparrot-theme/css/includes/project-privacy-popover";

:host {
    display: block;
    contain: layout;
    width: global-variables.$sidebar-expanded-width;
    height: 100vh;
}

:host([collapsed]) {
    width: global-variables.$sidebar-collapsed-width;
}
</style>
<style lang="scss" scoped>
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

.sidebar {
    height: 100%;
    font-family: var(--tlp-font-family);
    font-size: 100%;
}

.sidebar-collapsed {
    width: global-variables.$sidebar-collapsed-width;
    padding: 0;
}

:deep(.sidebar-popover-anchor) {
    margin-right: calc(-1 * var(--tlp-medium-spacing));
}

:deep(:focus) {
    outline: 0;
}
</style>
