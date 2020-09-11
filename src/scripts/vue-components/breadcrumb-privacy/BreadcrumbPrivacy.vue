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
    <div class="breadcrumb-privacy-icon-container">
        <span
            v-bind:class="{ 'breadcrumb-project-privacy-icon-with-flags': has_project_flags }"
            v-bind:data-privacy-icon="project_privacy_icon"
            ref="popover_icon"
        >
            <i class="fa breadcrumb-project-privacy-icon" v-bind:class="project_privacy_icon"></i>

            <span class="current-project-nav-flag-labels" v-if="has_project_flags">
                <span
                    class="current-project-nav-flag-label"
                    v-for="flag of project_flags"
                    v-bind:key="flag.label"
                >
                    {{ flag.label }}
                </span>
            </span>
        </span>

        <section class="tlp-popover" id="current-project-nav-title-popover" ref="popover_content">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">{{ project_public_name }}</h1>
            </div>
            <div class="tlp-popover-body">
                <template v-if="has_project_flags">
                    <div class="current-project-nav-flag-popover-flag">
                        <i class="fa" v-bind:class="project_privacy_icon"></i>
                        <h2 class="current-project-nav-flag-popover-content-title">
                            {{ privacy.privacy_title }}
                        </h2>
                        <p class="current-project-nav-title-popover-description">
                            {{ privacy.explanation_text }}
                        </p>
                    </div>
                    <hr class="current-project-nav-flag-popover-separator" />
                    <div
                        class="current-project-nav-flag-popover-flag"
                        v-for="flag of project_flags"
                        v-bind:key="flag.label"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="tuleap-svg tuleap-svg-project-shield"
                            width="16"
                            height="18"
                            viewBox="0 0 16 18"
                        >
                            <path
                                fill="#283E45"
                                d="M7.11616328,2.18186418 C7.68414571,1.93937861 8.32652235,1.93937861 8.89450477,2.18186418 L11.9271885,3.47659098 L15.0996093,4.83097494 C15.5816696,5.03677827 15.9110974,5.49147052 15.9564706,6.01365671 L15.9565022,6.01365395 C16.1818882,8.60755055 15.5313873,11.2346362 14.0049995,13.8949108 C12.4001774,16.6918852 10.5214925,18.6824965 8.36894465,19.8667446 C8.13889521,19.9933089 7.86050368,19.9952005 7.62875554,19.8717742 L7.62876743,19.8717519 C5.40633678,18.6881115 3.50784562,16.6958311 1.93329396,13.8949108 C0.436552873,11.2324052 -0.191114268,8.60314433 0.0502925379,6.00712815 C0.0985397101,5.48829257 0.427159743,5.03759169 0.90638766,4.83299758 L4.08347953,3.47659098 L7.11616328,2.18186418 Z M8.14534465,4.28634334 L8.14534465,17.3539843 C9.75854566,16.3823709 11.1078209,14.8842518 12.1931704,12.859627 C13.1416028,11.090409 13.7197177,9.3452093 13.9275149,7.62402786 L13.9275498,7.62403207 C13.9802257,7.18771829 13.7418721,6.76814784 13.3401208,6.5899895 L8.14534465,4.28634334 Z"
                                transform="translate(0 -2)"
                            />
                        </svg>
                        <h2 class="current-project-nav-flag-popover-content-title">
                            {{ flag.label }}
                        </h2>
                        <p
                            class="current-project-nav-flag-popover-content-description"
                            v-if="flag.description"
                        >
                            {{ flag.description }}
                        </p>
                    </div>
                </template>
                <p v-else class="current-project-nav-title-popover-description">
                    {{ privacy.explanation_text }}
                </p>
            </div>
        </section>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import {
    getProjectPrivacyIcon,
    ProjectPrivacy,
} from "../../project/privacy/project-privacy-helper";
import { createPopover } from "tlp";

@Component
export default class BreadcrumbPrivacy extends Vue {
    @Prop({ required: true })
    readonly project_flags!: { label: string }[];

    @Prop({ required: true })
    readonly privacy!: ProjectPrivacy;

    @Prop({ required: true })
    readonly project_public_name!: string;

    mounted(): void {
        const trigger = this.$refs.popover_icon;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const content = this.$refs.popover_content;
        if (!(content instanceof HTMLElement)) {
            return;
        }

        createPopover(trigger, content, {
            anchor: trigger,
            placement: "bottom-start",
        });
    }

    get has_project_flags(): boolean {
        return this.project_flags.length > 0;
    }

    get project_privacy_icon(): string {
        return getProjectPrivacyIcon(this.privacy);
    }
}
</script>
