import file_field_module from './file-field.js';
import angular           from 'angular';
import 'angular-mocks';

import {
    rewire$uploadTemporaryFile,
    rewire$uploadAdditionalChunk,
    restore
} from '../../rest/rest-service.js';
import { file_upload_rules } from './file-upload-rules-state.js';

describe("TuleapArtifactModalFileUploadService", () => {
    let $q,
        TuleapArtifactModalFileUploadService;

    beforeEach(() => {
        angular.mock.module(file_field_module);

        angular.mock.inject(function(
            _$q_,
            _TuleapArtifactModalFileUploadService_
        ) {
            $q                                   = _$q_;
            TuleapArtifactModalFileUploadService = _TuleapArtifactModalFileUploadService_;
        });

        installPromiseMatchers();
    });

    afterEach(() => {
        restore();
    });

    describe("uploadAllTemporaryFiles() -", function() {
        var first_upload, second_upload, third_upload,
            first_file, second_file, third_file;
        beforeEach(function() {
            first_upload  = $q.defer();
            second_upload = $q.defer();
            third_upload  = $q.defer();
            spyOn(TuleapArtifactModalFileUploadService, "uploadTemporaryFile").and.callFake(function(file) {
                switch (file.description) {
                    case "one":
                        return first_upload.promise;
                    case "two":
                        return second_upload.promise;
                    case "three":
                        return third_upload.promise;
                }
            });

            first_file  = { description: "one" };
            second_file = { description: "two" };
            third_file  = { description: "three" };
        });

        it("Given an array of temporary files objects to upload, when I upload all those files to my temporary list, then for each temporary file uploadTemporaryFile() will be called and a resolved promise will be returned", function() {
            var temporary_files = [first_file, second_file, third_file];

            var promise = TuleapArtifactModalFileUploadService.uploadAllTemporaryFiles(temporary_files);
            first_upload.resolve(45);
            second_upload.resolve(40);
            third_upload.resolve(56);

            expect(promise).toBeResolvedWith([45, 40, 56]);
            expect(TuleapArtifactModalFileUploadService.uploadTemporaryFile).toHaveBeenCalledWith(first_file);
            expect(TuleapArtifactModalFileUploadService.uploadTemporaryFile).toHaveBeenCalledWith(second_file);
            expect(TuleapArtifactModalFileUploadService.uploadTemporaryFile).toHaveBeenCalledWith(third_file);
        });

        it("Given that the limit for temporary files is almost reached and given an array of temporary files objects to upload, when I upload all those files to my temporary list, then a promise will be rejected with the error", function() {
            var temporary_files = [first_file, second_file];
            var error_response = {
                error: {
                    code: 403,
                    message: "Forbidden: Maximum number of temporary files reached: 5"
                }
            };

            var promise = TuleapArtifactModalFileUploadService.uploadAllTemporaryFiles(temporary_files);
            first_upload.resolve(54);
            second_upload.reject(error_response);

            expect(promise).toBeRejectedWith(error_response);
        });
    });

    describe("uploadTemporaryFile() -", () => {
        let uploadTemporaryFile,
            uploadAdditionalChunk;
        beforeEach(() => {
            uploadTemporaryFile = jasmine.createSpy("uploadTemporaryFile");
            rewire$uploadTemporaryFile(uploadTemporaryFile);
            uploadAdditionalChunk = jasmine.createSpy("uploadAdditionalChunk");
            rewire$uploadAdditionalChunk(uploadAdditionalChunk);
        });

        describe("Given that the max chunk size that can be sent is 128 bytes", () => {
            beforeEach(() => {
                file_upload_rules.max_chunk_size = 128;
            });

            it("and given an object with a 128 bytes file and a description property, when I upload this file to my temporary list, then the only chunk will be sent using the POST REST route and a promise will be resolved with the new temporary file's id", () => {
                var new_temporary_file_id = 11;
                var file_to_upload = {
                    file: {
                        name: "atomist.jpg",
                        base64: generateRandomBase64(127)
                    },
                    description: "antirealism ephemeralness writable"
                };
                uploadTemporaryFile.and.returnValue($q.when(new_temporary_file_id));

                var promise = TuleapArtifactModalFileUploadService.uploadTemporaryFile(file_to_upload);

                expect(promise).toBeResolvedWith(new_temporary_file_id);
                expect(uploadTemporaryFile).toHaveBeenCalledWith(
                    file_to_upload.file,
                    file_to_upload.description
                );
                expect(uploadAdditionalChunk).not.toHaveBeenCalled();
            });

            it("and given an object with a 257 bytes file, when I upload this file to my temporary list, then it will be split into 3 chunks, the two last chunks will be uploaded one after the other using the PUT REST route and a promise will be resolved with the new temporary file's id", () => {
                var new_temporary_file_id = 14;
                var file_to_upload = {
                    file: {
                        name: "dikkop.pdf",
                        base64: generateRandomBase64(257)
                    },
                    description: "resistive detentive"
                };
                uploadTemporaryFile.and.returnValue($q.when(new_temporary_file_id));
                uploadAdditionalChunk.and.returnValue($q.when());

                var promise = TuleapArtifactModalFileUploadService.uploadTemporaryFile(file_to_upload);

                expect(promise).toBeResolvedWith(new_temporary_file_id);
                expect(uploadTemporaryFile).toHaveBeenCalledWith(
                    file_to_upload.file,
                    file_to_upload.description
                );
                expect(uploadAdditionalChunk).toHaveBeenCalledWith(
                    new_temporary_file_id,
                    jasmine.any(String),
                    2
                );
                expect(uploadAdditionalChunk).toHaveBeenCalledWith(
                    new_temporary_file_id,
                    jasmine.any(String),
                    3
                );
            });
        });

        it("Given an object with no file property, when I try to upload this file to my temporary list, then it will return a resolved promise (letting us use $q.all even for empty temporary files)", function() {
            var file_to_upload = {
                description: "interior syringin platycephalous"
            };

            var promise = TuleapArtifactModalFileUploadService.uploadTemporaryFile(file_to_upload);

            expect(promise).toBeResolved();
        });
    });

    function generateRandomBase64(length) {
        const chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
        const random_string = [];
        for (let i = length - 1; i >= 0; i--) {
            const random_int = Math.floor(Math.random() * chars.length);
            random_string.push(chars[random_int]);
        }
        return random_string.join("");
    }
});
