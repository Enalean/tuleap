/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import SoloCard from "./SoloCard.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import { Card, ColumnDefinition } from "../../../type";

describe("SoloCard", () => {
    it("displays the parent card in its own cell when status is null", () => {
        const wrapper = shallowMount(SoloCard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            { id: 2, label: "To do" } as ColumnDefinition,
                            { id: 3, label: "Done" } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                card: {
                    id: 43,
                    status: null
                } as Card
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the parent card in its own cell when tracker id is not part of columns mapping", () => {
        const wrapper = shallowMount(SoloCard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [{ tracker_id: 42 }, { tracker_id: 43 }]
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [{ tracker_id: 42 }, { tracker_id: 43 }]
                            } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                card: {
                    id: 43,
                    tracker_id: 666,
                    status: {
                        id: 1001,
                        label: "Fixed"
                    }
                } as Card
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays the parent card in Done column when status maps this column", () => {
        const wrapper = shallowMount(SoloCard, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [
                            {
                                id: 2,
                                label: "To do",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1002 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1003 }]
                                    }
                                ]
                            } as ColumnDefinition,
                            {
                                id: 3,
                                label: "Done",
                                mappings: [
                                    { tracker_id: 42, accepts: [{ id: 1000 }] },
                                    {
                                        tracker_id: 43,
                                        accepts: [{ id: 1001 }]
                                    }
                                ]
                            } as ColumnDefinition
                        ]
                    }
                })
            },
            propsData: {
                card: {
                    id: 43,
                    tracker_id: 43,
                    status: {
                        id: 1001,
                        label: "Fixed"
                    }
                } as Card
            }
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
