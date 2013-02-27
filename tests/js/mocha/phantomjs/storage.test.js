/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

describe('AZHU.storage', function () {

    var storage = AZHU.storage,
        clock
        ;

    beforeEach(function () {
        localStorage.setItem = sinon.spy();
        localStorage.getItem = sinon.stub();
        localStorage.getItem.withArgs('no match').returns(null);
        localStorage.getItem.withArgs('stored key').returns('{"value":"[123]","timestamp":100}');

        clock = sinon.useFakeTimers();
    });

    afterEach(function () {
        clock.restore();
    });

    it('saves value in json format with a timestamp', function () {
        storage.save('key', [456], 1);

        localStorage.setItem.should.have.been.calledWith(
            'key',
            '{"value":"[456]","timestamp":1000}'
        );
    });

    describe('load', function () {
        it('returns false if not found', function () {
            storage.load('no match').should.be.false;
        });

        it('returns the saved value', function () {
            var value = storage.load('stored key');
            expect(value[0]).to.equal(123);
        });

        it('returns false if timesup', function () {
            var value;

            clock.tick(99);
            value = storage.load('stored key');
            expect(value[0]).to.equal(123);

            clock.tick(1);
            storage.load('stored key').should.be.false;
        });
    });
});
