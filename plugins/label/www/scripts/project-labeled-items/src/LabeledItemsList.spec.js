/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
import Vue from 'vue';

import { rewire$getLabeledItems, restore } from './rest-querier.js';
import LabeledItemsList from './LabeledItemsList.vue';

describe('LabeledItemsList', () => {
    let getLabeledItems;
    let LabeledItemsListVueElement;

    beforeEach(function() {
        getLabeledItems = jasmine.createSpy('getLabeledItems');
        rewire$getLabeledItems(getLabeledItems);

        LabeledItemsListVueElement = Vue.extend(LabeledItemsList);
    });

    afterEach(function() {
        restore();
    });

    it('Should display an error when no labels id are provided', () => {
        const vm = new LabeledItemsListVueElement({
            propsData: {
                dataLabelsId: "[]",
                projectLabelsId: 101
            }
        });

        vm.$mount();

        expect(vm.error).toEqual('Please select one or more labels by editing this widget');
    });

    it('Should display an error when REST route fails', async () => {
        getLabeledItems.and.returnValue(Promise.reject({
            response: {
                json: () => {
                    return {
                        error: {
                            code: 404,
                            message: 'Not Found'
                        }
                    }
                }
            }
        }));

        const vm = new LabeledItemsListVueElement({
            propsData: {
                dataLabelsId: "[1]",
                projectLabelsId: 101
            }
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.error).toEqual('404 Not Found');
    });

    it('Should display an empty state when no items are found', async () => {
        getLabeledItems.and.returnValue(Promise.resolve(
            { labeled_items: []}
        ));

        const vm = new LabeledItemsListVueElement({
            propsData: {
                dataLabelsId: "[1]",
                projectLabelsId: 101
            }
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.items).toEqual([]);
    });

    it('Should display a list of items.', async () => {
        getLabeledItems.and.returnValue(Promise.resolve({
            labeled_items: [
                {
                    title: 'test 1'
                },{
                    title: 'test 2'
                }
            ]
        }));

        const vm = new LabeledItemsListVueElement({
            propsData: {
                dataLabelsId: "[3, 4]",
                projectLabelsId: 101
            }
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.items).toEqual([
            { title: 'test 1' },
            { title: 'test 2' }
        ]);
    });
});
