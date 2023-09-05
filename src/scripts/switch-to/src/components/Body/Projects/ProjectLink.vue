<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div
        class="switch-to-projects-project"
        data-test="switch-to-projects-project"
        v-on:click="onClick"
        v-bind:data-target-id="target_id"
    >
        <a
            v-bind:href="project.project_uri"
            class="switch-to-projects-project-link"
            ref="project_link"
            v-on:keydown="changeFocus"
            data-test="project-link"
        >
            <i
                class="fas fa-fw switch-to-projects-project-icon"
                v-bind:class="project_privacy_icon"
            ></i>
            <span class="switch-to-projects-project-label">
                <span v-if="project.icon" class="switch-to-projects-project-label-icon">
                    {{ project.icon }}
                </span>
                <highlight-matching-text v-bind:text="project.project_name" />
            </span>
        </a>
        <quick-link
            v-for="link of project.quick_links"
            v-bind:key="link.html_url"
            v-bind:link="link"
            v-bind:project="project"
            v-bind:item="null"
            class="switch-to-projects-project-admin-icon"
            data-test="switch-to-projects-project-admin-icon"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, inject, ref, watch } from "vue";
import type { Project } from "../../../type";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import { getProjectPrivacyIcon } from "@tuleap/project-privacy-helper";
import HighlightMatchingText from "../HighlightMatchingText.vue";
import QuickLink from "../QuickLink.vue";
import { storeToRefs } from "pinia";
import { useKeyboardNavigationStore } from "../../../stores/keyboard-navigation";
import { ARE_RESTRICTED_USERS_ALLOWED } from "../../../injection-keys";

const props = defineProps<{ project: Project; location: Location }>();

const project_link = ref<HTMLAnchorElement | undefined>(undefined);

const navigation_store = useKeyboardNavigationStore();

const { programmatically_focused_element } = storeToRefs(navigation_store);
watch(programmatically_focused_element, () => {
    if (programmatically_focused_element.value !== props.project) {
        return;
    }

    if (project_link.value instanceof HTMLAnchorElement) {
        project_link.value.focus();
    }
});

function changeFocus(event: KeyboardEvent): void {
    switch (event.key) {
        case "ArrowUp":
        case "ArrowRight":
        case "ArrowDown":
        case "ArrowLeft":
            event.preventDefault();
            navigation_store.changeFocusFromProject({
                project: props.project,
                key: event.key,
            });
            break;
        default:
    }
}

const are_restricted_users_allowed = inject<boolean>(ARE_RESTRICTED_USERS_ALLOWED, false);

const project_privacy_icon = computed((): string => {
    const privacy: ProjectPrivacy = {
        project_is_public: props.project.is_public,
        project_is_private: props.project.is_private,
        project_is_private_incl_restricted: props.project.is_private_incl_restricted,
        project_is_public_incl_restricted: props.project.is_public_incl_restricted,
        are_restricted_users_allowed,
        explanation_text: "",
        privacy_title: "",
    };

    return getProjectPrivacyIcon(privacy);
});

const target_id = computed(
    (): string => "switch-to-project-" + encodeURI(props.project.project_uri),
);
function onClick(event: MouseEvent): void {
    if (!(event.target instanceof HTMLElement)) {
        return;
    }

    const closest = event.target.closest(`a, [data-target-id="${target_id.value}"]`);
    if (closest instanceof HTMLElement && closest.dataset.targetId === target_id.value) {
        props.location.assign(props.project.project_uri);
    }
}
</script>
