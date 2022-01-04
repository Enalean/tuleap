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
    <aside v-if="sidebar_configuration !== undefined" class="sidebar">
        <sidebar-logo />
        <div class="sidebar-content-vertical-scroll">
            <sidebar-header />
            <tools />
        </div>
        <div class="sidebar-spacer"></div>
        <sidebar-footer />
    </aside>
</template>
<script setup lang="ts">
import { unserializeConfiguration } from "./configuration";
import { provide, readonly, computed } from "vue";
import SidebarHeader from "./Header/SidebarHeader.vue";
import SidebarFooter from "./SidebarFooter.vue";
import { SIDEBAR_CONFIGURATION, TRIGGER_SHOW_PROJECT_ANNOUNCEMENT } from "./injection-symbols";
import Tools from "./Tools/Tools.vue";
import SidebarLogo from "./SidebarLogo.vue";

const props = defineProps<{ config: string | undefined }>();
const sidebar_configuration = readonly(computed(() => unserializeConfiguration(props.config)));

provide(SIDEBAR_CONFIGURATION, sidebar_configuration);
const emit = defineEmits<{
    (e: "show-project-announcement"): void;
}>();

provide(TRIGGER_SHOW_PROJECT_ANNOUNCEMENT, () => {
    emit("show-project-announcement");
});
</script>
<style lang="scss">
@use "../../../themes/tlp/src/scss/components/typography";
@use "../../../themes/BurningParrot/css/includes/sidebar/sidebar-generic";
@use "../../../themes/BurningParrot/css/includes/sidebar/sidebar-project";
@use "../../../themes/BurningParrot/css/includes/logo";
@use "@fortawesome/fontawesome-free/scss/fontawesome";
@use "./fontawesome-classes";
@use "../../../themes/tlp/src/fonts/tlp-font/icons";
@use "@tuleap/tlp-popovers";
@use "../../../themes/BurningParrot/css/includes/project-privacy-popover";

.sidebar {
    font-family: var(--tlp-font-family);
    font-size: 100%;
}

.sidebar-popover-anchor {
    margin-right: calc(-1 * var(--tlp-medium-spacing));
}
</style>
