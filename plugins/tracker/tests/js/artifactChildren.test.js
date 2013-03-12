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

    var base_url = '/plugins/tracker',
        stories  = [
            {title:"tea",    id:"121", status:"open",   xref:"story #121", url:"/path/to/121"},
            {title:"coffee", id:"122", status:"closed", xref:"story #122", url:"/path/to/122"}
        ];

    describe('retrieves the children', function () {

        var container,
            viewer;

        beforeEach(function () {
            var server      = sinon.fakeServer.create(),
                artifact_id = 12;

            container = new Element('div');
            viewer    = new tuleap.artifact.HierarchyViewer(base_url, container);
            server.respondWith(
                "GET", base_url + '/artifactChildren.json?aid=12',
                [
                    200,
                    { "Content-type": "application/json" },
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
            container.down('table').down('thead').innerText.should.contain('Title');
            container.down('table').down('thead').innerText.should.contain('Status');
        });

        describe('for each child', function () {

            var table;

            beforeEach(function () {
                table = container.down('table');
            });

            it('displays the title', function () {
                stories.map(function (story) {
                    table.innerText.should.contain(story.title);
                });
            });

            it('displays the status', function () {
                stories.map(function (story) {
                    table.innerText.should.contain(story.status);
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
