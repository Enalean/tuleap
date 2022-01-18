'use strict';

import { describe, it, expect } from 'vitest';

var JWT = require('../../../backend/modules/jwt');
var jwt = new JWT('private_key_to_change');

describe("Module JWT", function() {

    describe("isTokenValid()", function() {
        it("Given rights, when I check if the token is valid with incorrect key then false is returned", function () {
            var decoded = {};
            expect(jwt.isTokenValid(decoded)).toEqual(false);
        });

        it("Given rights, when I check if the token is valid with correct key then true is returned", function () {
            var decoded = {
                data: {
                    user_id: 165,
                    user_rights: ['@site_active', '@trackerv3_project_admin', '@rest-test_project_members']
                },
                exp: 1453288667
            };

            let received_error = null;
            function errorToken(err) {
                received_error = err;
            }

            expect(jwt.isTokenValid(decoded, errorToken)).toEqual(true);
            expect(received_error).toStrictEqual(null);
        });
    });

    describe("decodeToken()", function() {
        it("Given token encoded, when I decode the token with incorrect private key then object error is returned", function () {
            var jwt_incorrect = new JWT('private_key_to_change_incorrect');

            var encoded = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJleHAiOjE0NTMyODkzNjYsImRhdGEiOnsidXNlcl9pZCI6MTAyfX0.nAkQMZuHWw8JF3pr2fgUU_mEGx_riFrmUL8ivzuz88--mKsnwCpKR2ZBqfaZE_Aafx6Yb4LVC-Px9ByOBiZIhg';
            var decoded = {
                exp: 1453289366,
                data: {
                    user_id: 102
                }
            };

            let received_error = null;
            function errorToken(err) {
                received_error = err;
            }

            expect(jwt_incorrect.decodeToken(encoded, errorToken)).not.toEqual(decoded);
            expect(received_error).not.toStrictEqual(null);
        });
    });

    describe("isDateExpired()", function() {
        it("Given an expired date, when I check if the date is expired with an expired date then true is returned", function () {
            var dataExpired = Math.floor(Date.now() / 1000) - 100;
            expect(jwt.isDateExpired(dataExpired)).toEqual(true);
        });

        it("Given an expired date, when I check if the date is expired with a not expired date then false is returned", function () {
            var dataExpired = Math.floor(Date.now() / 1000) + 100;
            expect(jwt.isDateExpired(dataExpired)).toEqual(false);
        });
    });
});
