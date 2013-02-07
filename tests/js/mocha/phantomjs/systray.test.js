/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

describe('systray', function() {

    var body,
        storage = {
            load: sinon.stub(),
            save: sinon.stub()
        };

    beforeEach(function () {
        body = new Element('div');
    });

    it('is defined', function() {
        tuleap.systray.should.be.an('object');
    });

    describe('when not in lab mode', function() {

        it('does not inject anything in body', function () {
            tuleap.systray.load(body, storage);

            expect(body.down('.systray')).to.not.exist;
        });
    });

    describe('when in lab mode', function() {

        beforeEach(function () {
            body.addClassName('lab-mode');
        });

        it('inject a systray in body', function () {
            tuleap.systray.load(body, storage);

            expect(body.down('.systray')).to.exist;
        });

        describe('collapsable', function () {

            beforeEach(function () {
                storage.save.returns([]);
            });

            it('is expanded by default', function () {
                tuleap.systray.load(body, storage);

                body.down('.systray').className.should.not.include('.systray-collapsed');
            });

            it('is collapsed if the user wants it that way', function () {
                storage.load.withArgs('systray-collapse').returns('collapse');

                tuleap.systray.load(body, storage);

                body.down('.systray').className.should.include('systray-collapsed');
            });

            it('is expanded if the user wants it that way', function () {
                storage.load.withArgs('systray-collapse').returns('expanded');

                tuleap.systray.load(body, storage);

                body.down('.systray').className.should.not.include('systray-collapsed');
            });
        });
    });
});
