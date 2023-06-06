/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it, vi } from "vitest";

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(): void {
            //do nothing
        },
    };
});

import { mount, shallowMount } from "@vue/test-utils";
import App from "./App.vue";
import ListOfServers from "./Servers/ListOfServers.vue";
import RestrictServer from "./Servers/Restrict/RestrictServer.vue";
import { NAVIGATION } from "../injection-keys";
import type { Config, Server, Navigation } from "../type";
import { defineComponent, inject } from "vue";
import * as strict_inject from "@tuleap/vue-strict-inject";

vi.mock("@tuleap/vue-strict-inject");

describe("App", () => {
    it("should display list of servers by default", () => {
        const server_a: Server = {
            id: 1,
            server_url: "https://example.com/a",
            restrict_url: "/restrict/1",
        } as Server;

        const server_b: Server = {
            id: 2,
            server_url: "https://example.com/b",
            restrict_url: "/restrict/2",
        } as Server;

        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server_a, server_b],
            base_url: "/",
        } as unknown as Config);

        const wrapper = shallowMount(App, {
            props: {
                location: { pathname: "/" } as Location,
                history: window.history,
            },
        });

        expect(wrapper.findComponent(ListOfServers).exists()).toBe(true);
        expect(wrapper.findComponent(RestrictServer).exists()).toBe(false);
    });

    it("should display restrict server according to url (page refresh)", () => {
        const server_a: Server = {
            id: 1,
            server_url: "https://example.com/a",
            restrict_url: "/restrict/1",
        } as Server;

        const server_b: Server = {
            id: 2,
            server_url: "https://example.com/b",
            restrict_url: "/restrict/2",
        } as Server;

        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server_a, server_b],
            base_url: "/",
        } as unknown as Config);

        const wrapper = shallowMount(App, {
            props: {
                location: { pathname: "/restrict/2" } as Location,
                history: window.history,
            },
        });

        expect(wrapper.findComponent(ListOfServers).exists()).toBe(false);
        expect(wrapper.findComponent(RestrictServer).exists()).toBe(true);
        expect(wrapper.findComponent(RestrictServer).props().server).toStrictEqual(server_b);
    });

    it("should display list of servers whenever user wants to (internal navigation)", async () => {
        const server_a: Server = {
            id: 1,
            server_url: "https://example.com/a",
            restrict_url: "/restrict/1",
        } as Server;

        const server_b: Server = {
            id: 2,
            server_url: "https://example.com/b",
            restrict_url: "/restrict/2",
        } as Server;

        let restrictServerAction = (): void => {
            // do nothing by default
        };
        let cancelRestrictionAction = (): void => {
            // do nothing by default
        };

        // To keep the test readable we keep those fake components internal to the test
        // eslint-disable-next-line vue/one-component-per-file
        const FakeListOfServers = defineComponent({
            setup() {
                const navigation: Navigation | undefined = inject(NAVIGATION);
                restrictServerAction = (): void => {
                    navigation?.restrict(server_b);
                };
            },
            template: '<span id="server-list"></span>',
        });

        // eslint-disable-next-line vue/one-component-per-file
        const FakeRestrictServer = defineComponent({
            setup() {
                const navigation: Navigation | undefined = inject(NAVIGATION);
                cancelRestrictionAction = (): void => {
                    navigation?.cancelRestriction();
                };
            },
            template: '<span id="restrict-server"></span>',
        });

        const pushState = vi.fn();

        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server_a, server_b],
            base_url: "/",
        } as unknown as Config);

        const wrapper = mount(App, {
            global: {
                stubs: {
                    RestrictServer: FakeRestrictServer,
                    ListOfServers: FakeListOfServers,
                },
            },
            props: {
                location: { pathname: "/" } as Location,
                history: { pushState } as unknown as History,
            },
        });

        expect(wrapper.findComponent(ListOfServers).exists()).toBe(true);
        expect(wrapper.findComponent(RestrictServer).exists()).toBe(false);

        restrictServerAction();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListOfServers).exists()).toBe(false);
        expect(wrapper.findComponent(RestrictServer).exists()).toBe(true);
        expect(pushState).toHaveBeenCalledWith({}, "", server_b.restrict_url);

        cancelRestrictionAction();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ListOfServers).exists()).toBe(true);
        expect(wrapper.findComponent(RestrictServer).exists()).toBe(false);
        expect(pushState).toHaveBeenCalledWith({}, "", "/");
    });
});
