describe("TuleapArtifactModalFileUploadService", function() {
    var $q, TuleapArtifactModalFileUploadService, TuleapArtifactModalRestService,
        TuleapArtifactModalFileUploadRules;
    beforeEach(function() {
        module('tuleap-artifact-modal-file-field', function($provide) {
            $provide.decorator('TuleapArtifactModalRestService', function(
                $delegate,
                $q
            ) {
                spyOn($delegate, "uploadTemporaryFile");
                spyOn($delegate, "uploadAdditionalChunk");
                spyOn($delegate, "getFileUploadRules").and.returnValue($q.defer().promise);

                return $delegate;
            });
        });

        inject(function(
            _$q_,
            _TuleapArtifactModalFileUploadService_,
            _TuleapArtifactModalRestService_,
            _TuleapArtifactModalFileUploadRules_
        ) {
            $q                                   = _$q_;
            TuleapArtifactModalFileUploadService = _TuleapArtifactModalFileUploadService_;
            TuleapArtifactModalRestService       = _TuleapArtifactModalRestService_;
            TuleapArtifactModalFileUploadRules   = _TuleapArtifactModalFileUploadRules_;
        });

        installPromiseMatchers();
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
                    code: 403 ,
                    message: "Forbidden: Maximum number of temporary files reached: 5"
                }
            };

            var promise = TuleapArtifactModalFileUploadService.uploadAllTemporaryFiles(temporary_files);
            first_upload.resolve(54);
            second_upload.reject(error_response);

            expect(promise).toBeRejectedWith(error_response);
        });
    });

    describe("uploadTemporaryFile() -", function() {
        var post_request, put_request;
        beforeEach(function() {
            post_request = $q.defer();
            put_request  = $q.defer();
            TuleapArtifactModalRestService.uploadTemporaryFile.and.returnValue(post_request.promise);
            TuleapArtifactModalRestService.uploadAdditionalChunk.and.returnValue(put_request.promise);
        });

        describe("Given that the max chunk size that can be sent is 128 bytes", function() {
            beforeEach(function() {
                TuleapArtifactModalFileUploadRules.max_chunk_size = 128;
            });

            it("and given an object with a 128 bytes file and a description property, when I upload this file to my temporary list, then the only chunk will be sent using the POST REST route and a promise will be resolved with the new temporary file's id", function() {
                var new_temporary_file_id = 11;
                var file_to_upload = {
                    file: {
                        name: "atomist.jpg",
                        base64: generateRandomBase64(127)
                    },
                    description: "antirealism ephemeralness writable"
                };
                var promise = TuleapArtifactModalFileUploadService.uploadTemporaryFile(file_to_upload);
                post_request.resolve(new_temporary_file_id);

                expect(promise).toBeResolvedWith(new_temporary_file_id);
                expect(TuleapArtifactModalRestService.uploadTemporaryFile).toHaveBeenCalledWith(
                    file_to_upload.file,
                    file_to_upload.description
                );
                expect(TuleapArtifactModalRestService.uploadAdditionalChunk).not.toHaveBeenCalled();
            });

            it("and given an object with a 257 bytes file, when I upload this file to my temporary list, then it will be split into 3 chunks, the two last chunks will be uploaded one after the other using the PUT REST route and a promise will be resolved with the new temporary file's id", function() {
                var new_temporary_file_id = 14;
                var file_to_upload = {
                    file: {
                        name: "dikkop.pdf",
                        base64: generateRandomBase64(257)
                    },
                    description: "resistive detentive"
                };

                var promise = TuleapArtifactModalFileUploadService.uploadTemporaryFile(file_to_upload);
                post_request.resolve(new_temporary_file_id);
                put_request.resolve();

                expect(promise).toBeResolvedWith(new_temporary_file_id);
                expect(TuleapArtifactModalRestService.uploadTemporaryFile).toHaveBeenCalledWith(
                    file_to_upload.file,
                    file_to_upload.description
                );
                expect(TuleapArtifactModalRestService.uploadAdditionalChunk).toHaveBeenCalledWith(
                    new_temporary_file_id,
                    jasmine.any(String),
                    2
                );
                expect(TuleapArtifactModalRestService.uploadAdditionalChunk).toHaveBeenCalledWith(
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
        var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz".split("");
        var random_string = [];
        for (var i = length - 1; i >= 0; i--) {
            random_string.push(_.sample(chars));
        }
        return random_string.join("");
    }
});
