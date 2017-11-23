/**
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

import tlp from 'tlp';
import { getLabeledItems } from './rest-querier.js';

describe('getLabeledItems', () => {
    const project_id = 101;
    const labels_id  = [3, 4];
    let get;

    beforeEach(function() {
        get = spyOn(tlp, 'get');
    });

    it('Returns the items', async () => {
        get.and.returnValue(Promise.resolve({
            headers: {
                get: function() {
                    /** 'X-PAGINATION-SIZE' */
                    return 10;
                }
            },
            json: function() {
                return Promise.resolve({
                    labeled_items: [{ title: "Le title" }],
                    are_there_items_user_cannot_see: false
                })
            }
        }));

        const { labeled_items } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(labeled_items).toEqual([{ title: "Le title" }]);
    });

    it('Returns the are_there_items_user_cannot_see flag', async () => {
        get.and.returnValue(Promise.resolve({
            headers: {
                get: function() {
                    /** 'X-PAGINATION-SIZE' */
                    return 10;
                }
            },
            json: function() {
                return Promise.resolve({
                    labeled_items: [{ title: "Le title" }],
                    are_there_items_user_cannot_see: false
                })
            }
        }));

        const { are_there_items_user_cannot_see } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(are_there_items_user_cannot_see).toEqual(false);
    });

    it('Sets has_more to true if there are still elements to fetch', async () => {
        get.and.returnValue(Promise.resolve({
            headers: {
                get: function() {
                    /** 'X-PAGINATION-SIZE' */
                    return 10;
                }
            },
            json: function() {
                return Promise.resolve({
                    labeled_items: [{ title: "Le title" }],
                    are_there_items_user_cannot_see: false
                })
            }
        }));

        const { has_more } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(has_more).toEqual(true);
    });

    it('Sets has_more to false if there are no more elements to fetch', async () => {
        get.and.returnValue(Promise.resolve({
            headers: {
                get: function() {
                    /** 'X-PAGINATION-SIZE' */
                    return 10;
                }
            },
            json: function() {
                return Promise.resolve({
                    labeled_items: [{ title: "Le title" }],
                    are_there_items_user_cannot_see: false
                })
            }
        }));

        const { has_more } = await getLabeledItems(project_id, labels_id, 9, 1);

        expect(has_more).toEqual(false);
    });

    it('Returns the offset so that the caller update its offset in case of recursive calls', async () => {
        get.and.returnValue(Promise.resolve({
            headers: {
                get: function() {
                    /** 'X-PAGINATION-SIZE' */
                    return 10;
                }
            },
            json: function() {
                return Promise.resolve({
                    labeled_items: [{ title: "Le title" }],
                    are_there_items_user_cannot_see: false
                })
            }
        }));

        const { offset } = await getLabeledItems(project_id, labels_id, 9, 1);

        expect(offset).toEqual(9);
    });

    it('Fetches items recursively until it finds at least one readable', async () => {
        get.and.returnValues(
            Promise.resolve({
                headers: {
                    get: function() {
                        /** 'X-PAGINATION-SIZE' */
                        return 10;
                    }
                },
                json: function() {
                    return Promise.resolve({
                        labeled_items: [],
                        are_there_items_user_cannot_see: true
                    })
                }
            }),
            Promise.resolve({
                headers: {
                    get: function() {
                        /** 'X-PAGINATION-SIZE' */
                        return 10;
                    }
                },
                json: function() {
                    return Promise.resolve({
                        labeled_items: [],
                        are_there_items_user_cannot_see: true
                    })
                }
            }),
            Promise.resolve({
                headers: {
                    get: function() {
                        /** 'X-PAGINATION-SIZE' */
                        return 10;
                    }
                },
                json: function() {
                    return Promise.resolve({
                        labeled_items: [{ title: "Le title" }],
                        are_there_items_user_cannot_see: false
                    })
                }
            })
        );

        const { offset, labeled_items } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(get.calls.count()).toEqual(3);
        expect(get.calls.argsFor(0)).toEqual(
            [
                '/api/projects/' + project_id + '/labeled_items',
                {
                    params: {
                        query: JSON.stringify({labels_id}),
                        offset: 0,
                        limit: 1
                    }
                }
            ]
        );
        expect(get.calls.argsFor(1)).toEqual(
            [
                '/api/projects/' + project_id + '/labeled_items',
                {
                    params: {
                        query: JSON.stringify({labels_id}),
                        offset: 1,
                        limit: 1
                    }
                }
            ]
        );
        expect(get.calls.argsFor(2)).toEqual(
            [
                '/api/projects/' + project_id + '/labeled_items',
                {
                    params: {
                        query: JSON.stringify({labels_id}),
                        offset: 2,
                        limit: 1
                    }
                }
            ]
        );
        expect(offset).toEqual(2);
        expect(labeled_items).toEqual([{ title: "Le title" }]);
    });
});
