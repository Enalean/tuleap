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
        v-if="can_sidebar_be_collapsed && config && config.user.is_logged_in"
        type="button"
        v-bind:title="
            props.is_sidebar_collapsed
                ? config.internationalization.open_sidebar
                : config.internationalization.close_sidebar
        "
        class="sidebar-collapse-button"
        v-on:click="changeSidebarCollapse()"
    >
        <i class="fa-solid sidebar-collapser-icon" aria-hidden="true"></i>
    </button>
</template>
<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";

const props = defineProps<{ is_sidebar_collapsed: boolean; can_sidebar_be_collapsed: boolean }>();

const config = strictInject(SIDEBAR_CONFIGURATION);

const emit = defineEmits<{
    (e: "update:is_sidebar_collapsed", value: boolean): void;
}>();

function changeSidebarCollapse(): void {
    const new_expected_value = !props.is_sidebar_collapsed;
    emit("update:is_sidebar_collapsed", new_expected_value);
}
</script>
