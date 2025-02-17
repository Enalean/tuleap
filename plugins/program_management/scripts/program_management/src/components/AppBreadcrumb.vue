<!--
  - Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
            v-bind:project_flags="Array.from(project_flags)"
            v-bind:privacy="project_privacy"
            v-bind:project_public_name="project_public_name"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="program_url" class="breadcrumb-link">
                    <span aria-hidden="true">{{ project_icon }}</span>
                    {{ project_public_name }}
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
        </nav>
    </div>
</template>
<script setup lang="ts">
import type { ProjectFlag } from "@tuleap/vue3-breadcrumb-privacy";
import { BreadcrumbPrivacy } from "@tuleap/vue3-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

const props = defineProps<{
    project_public_name: string;
    project_short_name: string;
    project_icon: string;
    project_privacy: ProjectPrivacy;
    project_flags: ReadonlyArray<ProjectFlag>;
    is_program_admin: boolean;
}>();

const program_url = `/projects/${encodeURIComponent(props.project_short_name)}`;
const plugin_url = `/program_management/${encodeURIComponent(props.project_short_name)}`;
const plugin_administration_url = `/program_management/admin/${encodeURIComponent(props.project_short_name)}`;
</script>
