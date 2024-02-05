<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <section class="tlp-pane-section-for-cards">
                <div
                    v-for="index in [1, 2, 3]"
                    v-bind:key="index"
                    class="tlp-card pull-request-homepage-card-skeleton"
                >
                    <div class="pull-request-summary-skeleton">
                        <span class="tlp-skeleton-text pull-request-card-title-skeleton"></span>
                        <span class="tlp-skeleton-text"></span>
                    </div>
                    <div class="pull-request-card-skeleton-container">
                        <div class="pull-request-reviewer-skeleton"></div>
                    </div>
                    <div class="pull-request-card-skeleton-container">
                        <span class="pull-request-label-skeleton"></span>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts"></script>

<style scoped lang="scss">
.pull-request-homepage-card-skeleton {
    display: flex;
    justify-content: space-between;
}

.pull-request-summary-skeleton {
    display: flex;
    flex-direction: column;
    gap: var(--tlp-medium-spacing);
    align-items: flex-start;
}

.pull-request-card-title-skeleton {
    width: 400px;
}

.pull-request-card-skeleton-container {
    display: flex;
}

$reviewer-skeleton-width: 26px;

.pull-request-reviewer-skeleton {
    width: $reviewer-skeleton-width;
    height: $reviewer-skeleton-width;
    animation: skeleton-blink 1.3s linear infinite alternate;
    border-radius: 50%;
    background-color: var(--tlp-skeleton-color);
}

.pull-request-label-skeleton {
    width: $reviewer-skeleton-width * 2;
    height: $reviewer-skeleton-width;
    animation: skeleton-blink 0.75s linear infinite alternate;
    background-color: #e3e6e9;
}

@keyframes skeleton-blink {
    0% {
        opacity: 1;
    }

    100% {
        opacity: 0.3;
    }
}
</style>
