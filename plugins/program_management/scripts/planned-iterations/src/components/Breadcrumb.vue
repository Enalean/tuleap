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
                <a v-bind:href="plugin_url" class="breadcrumb-link" v-translate>Program</a>
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
                                <span v-translate>Administration</span>
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

<script lang="ts">
import type { ProjectFlag } from "@tuleap/vue-breadcrumb-privacy";
import type { ProgramIncrement, Program } from "../store/configuration";

import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

const configuration = namespace("configuration");

@Component({
    components: {
        BreadcrumbPrivacy,
    },
})
export default class Breadcrumb extends Vue {
    @configuration.State
    readonly program!: Program;

    @configuration.State
    readonly program_privacy!: ProjectPrivacy;

    @configuration.State
    readonly program_flags!: Array<ProjectFlag>;

    @configuration.State
    readonly is_program_admin!: boolean;

    @configuration.State
    readonly program_increment!: ProgramIncrement;

    get program_url(): string {
        return `/projects/${encodeURIComponent(this.program.program_shortname)}`;
    }

    get plugin_url(): string {
        return `/program_management/${encodeURIComponent(this.program.program_shortname)}`;
    }

    get plan_iterations_url(): string {
        return `/program_management/${encodeURIComponent(
            this.program.program_shortname,
        )}/increments/${encodeURIComponent(this.program_increment.id)}/plan`;
    }

    get plugin_administration_url(): string {
        return `/program_management/admin/${encodeURIComponent(this.program.program_shortname)}`;
    }
}
</script>
