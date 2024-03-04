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
    >
        <div class="project-registration-content">
            <button
                type="button"
                class="tlp-button-primary tlp-button-large tlp-form-element-disabled project-registration-next-button"
                data-test="project-registration-next-button"
                v-bind:disabled="!root_store.is_template_selected"
                v-on:click.prevent="goToInformationPage"
            >
                <span v-translate>Next</span>
                <i class="fas fa-long-arrow-alt-right tlp-button-icon-right"></i>
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { isElementInViewport } from "../../helpers/is-element-in-viewport";
import { useStore } from "../../stores/root";

@Component({})
export default class TemplateFooter extends Vue {
    root_store = useStore();

    private is_footer_in_viewport = false;
    private ticking = false;

    mounted(): void {
        this.is_footer_in_viewport = isElementInViewport(this.$el);
        document.addEventListener("scroll", this.checkFooterIsInViewport);
        window.addEventListener("resize", this.checkFooterIsInViewport);
    }

    destroyed(): void {
        this.removeFooterListener();
    }

    removeFooterListener(): void {
        document.removeEventListener("scroll", this.checkFooterIsInViewport);
        window.removeEventListener("resize", this.checkFooterIsInViewport);
    }

    goToInformationPage(): void {
        this.$router.push({ name: "information" });
    }

    checkFooterIsInViewport(): void {
        if (!this.ticking) {
            requestAnimationFrame(() => {
                this.is_footer_in_viewport = isElementInViewport(this.$el);
                this.ticking = false;
            });

            this.ticking = true;
        }
    }

    get pinned_class(): string {
        if (!this.is_footer_in_viewport && this.root_store.is_template_selected) {
            this.removeFooterListener();

            return "pinned";
        }

        return "";
    }
}
</script>
