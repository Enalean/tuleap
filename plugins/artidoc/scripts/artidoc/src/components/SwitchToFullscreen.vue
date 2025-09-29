<!--
- Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <button
        type="button"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        v-on:click="fullscreen"
        v-bind:title="title"
    >
        <i class="fa-solid fa-expand fa-fw" aria-hidden="true"></i>
        <span class="button-label">{{ $gettext("Fullscreen") }}</span>
        <span class="shortcut-hint" v-bind:title="hint">[f]</span>
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import screenfull from "screenfull";
import { strictInject } from "@tuleap/vue-strict-inject";
import { REGISTER_FULLSCREEN_SHORTCUT_HANDLER } from "@/register-shortcut-handler-injection-keys";

const { $gettext } = useGettext();

const title = $gettext(
    "Toggle fullscreen to remove header and sidebar in order to maximize the writing space.",
);
const hint = $gettext("Toggle fullscreen");

function fullscreen(): void {
    screenfull.toggle();
}

strictInject(REGISTER_FULLSCREEN_SHORTCUT_HANDLER)(fullscreen);
</script>

<style lang="scss">
// stylelint-disable selector-no-qualifying-type
:fullscreen {
    header,
    header.pinned,
    tuleap-project-sidebar,
    .project-banner,
    .platform-banner,
    .help-dropdown,
    #feedback,
    .breadcrumb-container {
        display: none;
    }

    body {
        padding: 0;
    }

    .artidoc-app {
        --artidoc-app-height: 100vh;
        --artidoc-container-height: 100vh;
    }
}
</style>

<style lang="scss" scoped>
button {
    display: flex;
    align-items: center;
    gap: 3px;

    &:hover > .shortcut-hint {
        opacity: 1;
    }
}

.button-label {
    flex-grow: 1;
}

.shortcut-hint {
    opacity: 0.75;
    color: var(--tlp-dimmed-color);
}
</style>
