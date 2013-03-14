/**
  * Copyright (c) Enalean, 2013. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

describe('HierarchyViewer', function () {
    var base_url = '/plugins/tracker/',
        stories  = [
            {title:"tea",    id:"121", status:"1",   xref:"story #121", url:"/path/to/121"},
            {title:"coffee", id:"122", status:"0",   xref:"story #122", url:"/path/to/122"},
            {title:"cake",   id:"19",  status:null,  xref:"task #19",   url:"/path/to/19"}
        ],
        locales = {
            tracker_hierarchy: {
                no_child_artifacts: 'whatever',
                title_column_name: 'Title',
                status_column_name: 'Status',
                open_status     : 'ongoing',
                closed_status   : 'rejected',
                null_status     : 'not available'
            }
        };

    describe('retrieves the children', function () {
        var artifact_id = 12,
            container,
            viewer,
            server;

        beforeEach(function () {
            server      = sinon.fakeServer.create();
            container   = new Element('div');
            viewer      = new tuleap.artifact.HierarchyViewer(base_url, container, locales);
        });

        describe('it does not have any children', function () {
            
            beforeEach(function () {
                server.respondWith(
                    "GET",
                    base_url + '?aid=' + artifact_id + '&func=get-children',
                    [
                        200,
                        { "Content-Type": "application/json" },
                        JSON.stringify([])
                    ]
                );

                viewer.getArtifactChildren(artifact_id);
                server.respond();
            });

            it('displays that there is no child', function () {
                container.down('.info-no-child').textContent.should.contain(locales.tracker_hierarchy.no_child_artifacts);
            });
        });

        describe('it has children', function () {
          
            beforeEach(function () {
                server.respondWith(
                    "GET", base_url + '?aid=' + artifact_id + '&func=get-children',
                    [
                        200,
                        { "Content-Type": "application/json" },
                        JSON.stringify(stories)
                    ]
                );

                viewer.getArtifactChildren(artifact_id);
                server.respond();
            });

            it('inserts a table', function () {
                container.down('table').should.exist;
            });

            it('the table has a header with title & status', function () {
                container.down('table').down('thead').textContent.should.contain('Title');
                container.down('table').down('thead').textContent.should.contain('Status');
            });

            describe('for each child', function () {
                var table;
                
                beforeEach(function () {
                    table = container.down('table');
                });

                it('displays the title', function () {
                    stories.map(function (story) {
                        table.textContent.should.contain(story.title);
                    });
                });

                it('modifies and displays the status', function () {
                    stories.map(function (story) {
                        table.textContent.should.contain(locales.tracker_hierarchy.open_status);
                        table.textContent.should.contain(locales.tracker_hierarchy.closed_status);
                        table.textContent.should.contain(locales.tracker_hierarchy.null_status);
                    });
                });

                it('displays the xref as a link', function () {
                    stories.map(function (story) {
                        table.down('a[href=' + story.url + ']').text.should.contain(story.xref);
                    });
                });
            });
        });
    });
});
