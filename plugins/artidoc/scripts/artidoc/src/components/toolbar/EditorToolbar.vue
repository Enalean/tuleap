<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
    <div>
        <i
            class="icon fa-solid fa-bold"
            v-bind:class="{ activated: is_bold_activated }"
            v-bind:title="$gettext('Toggle bold style `Ctrl+b`')"
            v-on:click="applyBold"
            data-test="icon-bold"
        ></i>
    </div>
</template>

<script setup lang="ts">
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ref } from "vue";

const toolbar_bus = strictInject(TOOLBAR_BUS);

const is_bold_activated = ref(false);

toolbar_bus.setView({
    activateBold(is_activated: boolean) {
        is_bold_activated.value = is_activated;
    },
});

function applyBold(): void {
    toolbar_bus.bold();
}
</script>

<style scoped lang="scss">
@use "@/themes/includes/zindex";
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

div {
    display: flex;
    position: sticky;
    z-index: zindex.$toolbar;
    top: global-variables.$navbar-height;
    padding: var(--tlp-medium-spacing);
    background: var(--tlp-white-color);
    box-shadow: var(--tlp-sticky-header-shadow);
    gap: var(--tlp-medium-spacing);
}

.icon {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--tlp-small-spacing);
    vertical-align: middle;
    cursor: pointer;
}

.activated {
    background: var(--tlp-main-color-lighter-90);
}
</style>
