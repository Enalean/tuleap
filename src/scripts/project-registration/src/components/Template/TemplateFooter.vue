<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div
        class="project-registration-button-container"
        data-test="project-template-footer"
        v-bind:class="pinned_class"
        ref="element"
    >
        <div class="project-registration-content">
            <button
                type="button"
                class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                data-test="project-registration-next-button"
                v-bind:disabled="!root_store.is_template_selected"
                v-on:click.prevent="goToInformationPage"
            >
                {{ $gettext("Next")
                }}<i
                    class="fa-solid fa-long-arrow-alt-right tlp-button-icon-right"
                    aria-hidden="true"
                ></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { isElementInViewport } from "../../helpers/is-element-in-viewport";
import { useStore } from "../../stores/root";
import { useRouter } from "../../helpers/use-router";

const { $gettext } = useGettext();
const root_store = useStore();
const element = ref<InstanceType<typeof Element>>();
const router = useRouter();

const is_footer_in_viewport = ref(false);
const ticking = ref(false);

onMounted(() => {
    if (element.value) {
        is_footer_in_viewport.value = isElementInViewport(element.value);
    }
    document.addEventListener("scroll", checkFooterIsInViewport);
    window.addEventListener("resize", checkFooterIsInViewport);
});

onBeforeUnmount(() => {
    removeFooterListener();
});

function removeFooterListener(): void {
    document.removeEventListener("scroll", checkFooterIsInViewport);
    window.removeEventListener("resize", checkFooterIsInViewport);
}

function goToInformationPage(): void {
    router.push({ name: "information" });
}

function checkFooterIsInViewport(): void {
    if (!ticking.value) {
        requestAnimationFrame(() => {
            if (element.value) {
                is_footer_in_viewport.value = isElementInViewport(element.value);
            }
            ticking.value = false;
        });

        ticking.value = true;
    }
}

const pinned_class = computed((): string => {
    if (!is_footer_in_viewport.value && root_store.is_template_selected) {
        removeFooterListener();

        return "pinned";
    }

    return "";
});
</script>
