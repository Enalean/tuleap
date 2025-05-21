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
    <div class="project-registration-button-container">
        <div class="project-registration-content">
            <div>
                <router-link
                    to="/new"
                    v-on:click="resetProjectCreationError"
                    class="project-registration-back-button"
                    data-test="project-registration-back-button"
                    ><i
                        class="fa-solid fa-long-arrow-alt-left project-registration-back-icon"
                        aria-hidden="true"
                    ></i
                    >{{ $gettext("Back") }}</router-link
                >
                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                    data-test="project-registration-next-button"
                    v-bind:disabled="root_store.is_creating_project && !root_store.has_error"
                >
                    {{ $gettext("Start my project")
                    }}<i
                        class="tlp-button-icon-right"
                        v-bind:class="get_icon"
                        data-test="project-submission-icon"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { useStore } from "../../stores/root";

const { $gettext } = useGettext();
const root_store = useStore();

const get_icon = computed((): string => {
    if (!root_store.is_creating_project) {
        return "fa-regular fa-circle-right";
    }
    return "fa-solid fa-spin fa-circle-notch";
});

function resetProjectCreationError(): void {
    root_store.resetProjectCreationError();
}
</script>
