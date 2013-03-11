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

    var story_1 = {"title":"titi","id":"121"};

    it('retrieves the children', function () {
        var container   = new Element('div'),
            url         = '/plugins/tracker/artifactChildren.json',
            viewer      = new tuleap.artifact.HierarchyViewer(url, container),
            server      = sinon.fakeServer.create(),
            artifact_id = 12;

        server.respondWith(
            "GET", url + '?aid=12',
            [
                200,
                { "Content-type": "application/json" },
                JSON.stringify([story_1])
            ]
        );

        viewer.getArtifactChildren(artifact_id);

        server.respond();
        container.innerText.should.contain(story_1.title);
    });
});
