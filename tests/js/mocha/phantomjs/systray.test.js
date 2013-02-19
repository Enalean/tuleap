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

describe('Le systray', function() {

    var body,
        storage = {
            load: sinon.stub(),
            save: sinon.stub()
        },
        server;

    beforeEach(function () {
        body = new Element('div');
        server  = sinon.fakeServer.create();
    });

    afterEach(function () {
        server.restore();
        storage.save.reset();
    });

    it('is defined', function() {
        tuleap.systray.should.be.an('object');
    });

    describe('when not in lab mode', function() {

        it('does not inject anything in body', function () {
            tuleap.systray.load(body, storage);

            expect(body.down('.systray')).to.not.exist;
        });

        it('clear the cache to be sure that no information is leaked', function () {
            tuleap.systray.load(body, storage);

            storage.save.should.have.been.calledWith('systray-links', []);
        });
    });

    describe(', when in lab mode,', function() {

        beforeEach(function () {
            body.addClassName('lab-mode');
        });

        describe('load links', function () {

            describe('retrieves links in the cache,', function () {

                describe('there is at least one link,', function () {

                    beforeEach(function () {
                        var some_links = [{ label: 'toto', href: '/path' }];
                        storage.load.withArgs('systray-links').returns(some_links);
                    });

                    it('inject links in the systray', function () {
                        tuleap.systray.load(body, storage);

                        body.down('.systray_links a[href=/path]').text.should.contain('toto');
                    });

                    describe('inject a systray', function () {

                        it('is in the body', function () {
                            tuleap.systray.load(body, storage);

                            expect(body.down('.systray')).to.exist;
                        });

                        describe('the systray is collapsable', function () {

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

                describe('there is no link,', function () {

                    it('does not inject a systray in the body', function () {
                        storage.load.withArgs('systray-links').returns([]);

                        tuleap.systray.load(body, storage);

                        expect(body.down('.systray')).to.not.exist;
                    });
                });

                describe('there is no more than one link', function () {

                    it('does not add a dropdown', function () {
                        storage.load.withArgs('systray-links').returns([
                            {label: 'le link', href: '/path/to/link'}
                        ]);

                        tuleap.systray.load(body, storage);

                        expect(body.down('.systray .dropdown-menu')).to.not.exist;
                        expect(body.down('.systray .dropdown-toggle')).to.not.exist;
                    });
                });

                describe('there is more than one link', function () {

                    it('adds a dropdown menu', function () {
                        storage.load.withArgs('systray-links').returns([
                            {label: 'le link',  href: '/path/to/link'},
                            {label: 'le link2', href: '/path/to/link2'}
                        ]);

                        tuleap.systray.load(body, storage);

                        expect(body.down('.systray .dropdown-toggle')).to.exist;
                        expect(body.down('.systray .dropdown-menu')).to.exist;
                        body.down('.systray .dropdown-menu a[href=/path/to/link2]').text.should.be.equal('le link2');
                    });
                });
            });

            describe('if not found, retrieves links from the server,', function () {

                beforeEach(function () {
                    server.respondWith(
                        "GET", "/systray.json",
                        [
                            200,
                            { "Content-type": "application/json" },
                            '[{"label":"titi","href":"/path/to/titi"}]'
                        ]
                    );
                    storage.load.withArgs('systray-links').returns(undefined);

                    tuleap.systray.load(body, storage);
                    server.respond();
                });

                it('injects links coming from the server', function () {
                    body.down('.systray_links a[href=/path/to/titi]').text.should.contain('titi');
                });

                it.skip('store links in the cache to save the rainforest', function () {
                    //skip it until we know what to do: https://groups.google.com/d/topic/sinonjs/vD1xUXTy9LM/discussion
                    storage.save.should.have.been.calledWith('systray-links', [{"label":"titi","href":"/path/to/titi"}]);
                });
            });
        });
    });
});
