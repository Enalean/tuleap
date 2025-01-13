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
  -->

<template>
    <div class="breadcrumb-container">
        <breadcrumb-privacy
            v-bind:project_flags="program_flags"
            v-bind:privacy="program_privacy"
            v-bind:project_public_name="program.program_label"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="program_url" class="breadcrumb-link">
                    <span aria-hidden="true">{{ program.program_icon }}</span>
                    {{ program.program_label }}
                </a>
            </div>
            <div
                data-test="breadcrumb-item-switchable"
                class="breadcrumb-item"
                v-bind:class="{ 'breadcrumb-switchable': is_program_admin }"
            >
                <a v-bind:href="plugin_url" class="breadcrumb-link">{{ $gettext("Program") }}</a>
                <div
                    class="breadcrumb-switch-menu-container"
                    v-if="is_program_admin"
                    data-test="breadcrumb-item-administration"
                >
                    <nav class="breadcrumb-switch-menu">
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="plugin_administration_url"
                                v-bind:title="$gettext('Administration')"
                            >
                                <i class="fa fa-cog fa-fw"></i>
                                {{ $gettext("Administration") }}
                            </a>
                        </span>
                    </nav>
                </div>
            </div>
            <span class="breadcrumb-item">
                <a
                    class="breadcrumb-link"
                    v-bind:href="plan_iterations_url"
                    v-bind:title="program_increment.title"
                >
                    {{ program_increment.title }}
                </a>
            </span>
        </nav>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ProjectFlag } from "@tuleap/vue3-breadcrumb-privacy";
import { BreadcrumbPrivacy } from "@tuleap/vue3-breadcrumb-privacy";
import { useNamespacedState, useStore } from "vuex-composition-helpers";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import type { Program, ProgramIncrement } from "../store/configuration";

const store = useStore();

const program_flags = computed((): ProjectFlag[] => store.state.configuration.program_flags);
const { program, program_privacy, is_program_admin, program_increment } = useNamespacedState<{
    program: Program;
    program_privacy: ProjectPrivacy;
    is_program_admin: boolean;
    program_increment: ProgramIncrement;
}>("configuration", ["program", "program_privacy", "is_program_admin", "program_increment"]);

const program_url = `/projects/${encodeURIComponent(program.value.program_shortname)}`;
const plugin_url = `/program_management/${encodeURIComponent(program.value.program_shortname)}`;
const plan_iterations_url = `/program_management/${encodeURIComponent(program.value.program_shortname)}/increments/${encodeURIComponent(program_increment.value.id)}/plan`;
const plugin_administration_url = `/program_management/admin/${encodeURIComponent(program.value.program_shortname)}`;
</script>
