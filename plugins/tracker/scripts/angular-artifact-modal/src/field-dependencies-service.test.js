import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalFieldDependenciesService", function () {
    var FieldDependenciesService;
    beforeEach(function () {
        angular.mock.module(artifact_modal_module);

        angular.mock.inject(function (_TuleapArtifactModalFieldDependenciesService_) {
            FieldDependenciesService = _TuleapArtifactModalFieldDependenciesService_;
        });
    });

    describe("getTargetFieldPossibleValues() -", function () {
        describe("Given an array of source values, a target field object and a collection representing the field dependencies rules,", function () {
            describe("and given there was one source value", function () {
                it("and given there was only one rule for this source value, when I get the possible values for the target field, then an array containing only the target value corresponding to the source value will be returned", function () {
                    var source_value_ids = [841];
                    var target_field = {
                        field_id: 32,
                        values: [{ id: 748 }, { id: 778 }, { id: 358 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 99,
                            source_value_id: 841,
                            target_field_id: 32,
                            target_value_id: 778,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([{ id: 778 }]);
                });

                it("and given there were no rules for this source value, when I get the possible values for the target field, then an empty array will be returned", function () {
                    var source_value_ids = [753];
                    var target_field = {
                        field_id: 91,
                        values: [{ id: 863 }],
                    };
                    var field_dependencies_rules = [];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([]);
                });

                it("and given there were two rules for this source value, when I get the possible values for the target field, then an array containing two target values will be returned", function () {
                    var source_value_ids = [293];
                    var target_field = {
                        field_id: 66,
                        values: [{ id: 964 }, { id: 197 }, { id: 520 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 46,
                            source_value_id: 293,
                            target_field_id: 66,
                            target_value_id: 964,
                        },
                        {
                            source_field_id: 46,
                            source_value_id: 293,
                            target_field_id: 66,
                            target_value_id: 197,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([{ id: 964 }, { id: 197 }]);
                });
            });

            describe("and given there was no source value (empty array)", function () {
                it("when I get the possible values for the target field, then an empty array will be returned", function () {
                    var source_value_ids = [];
                    var target_field = {
                        field_id: 13,
                        values: [{ id: 750 }, { id: 881 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 60,
                            source_value_id: 637,
                            target_field_id: 13,
                            target_value_id: 881,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([]);
                });
            });

            describe("and given there were two source values", function () {
                it("and given there was a rule for only one of the source values, when I get the possible values for the target field, then an array containing only the target value for the defined rule will be returned", function () {
                    var source_value_ids = [432, 574];
                    var target_field = {
                        field_id: 16,
                        values: [{ id: 197 }, { id: 736 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 74,
                            source_value_id: 432,
                            target_field_id: 16,
                            target_value_id: 197,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([{ id: 197 }]);
                });

                it("and given there were two rules with no target value in common, when I get the possible values for the target field, then an array containing two target values will be returned", function () {
                    var source_value_ids = [464, 597];
                    var target_field = {
                        field_id: 24,
                        values: [{ id: 344 }, { id: 979 }, { id: 549 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 38,
                            source_value_id: 597,
                            target_field_id: 24,
                            target_value_id: 344,
                        },
                        {
                            source_field_id: 38,
                            source_value_id: 464,
                            target_field_id: 24,
                            target_value_id: 979,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([{ id: 344 }, { id: 979 }]);
                });

                it("and given there were two rules which had a target value in common, when I get the possible values for the target field, then the common target value will not be duplicated in the returned array", function () {
                    var source_value_ids = [738, 851];
                    var target_field = {
                        field_id: 41,
                        values: [{ id: 781 }, { id: 150 }],
                    };
                    var field_dependencies_rules = [
                        {
                            source_field_id: 50,
                            source_value_id: 738,
                            target_field_id: 41,
                            target_value_id: 781,
                        },
                        {
                            source_field_id: 50,
                            source_value_id: 851,
                            target_field_id: 41,
                            target_value_id: 781,
                        },
                    ];

                    var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                        source_value_ids,
                        target_field,
                        field_dependencies_rules
                    );

                    expect(filtered_values).toEqual([{ id: 781 }]);
                });
            });
        });

        it("Given no target field object, when I get the possible values for the target field, then it will throw an exception", function () {
            var source_value_ids = [469];
            var field_dependencies_rules = [
                {
                    source_field_id: 17,
                    source_value_id: 647,
                    target_field_id: 30,
                    target_value_id: 853,
                },
            ];

            expect(function () {
                FieldDependenciesService.getTargetFieldPossibleValues(
                    source_value_ids,
                    undefined,
                    field_dependencies_rules
                );
            }).toThrow();
        });

        it("Given no collection representing the field dependencies rules, when I get the possible values for the target field, then an empty array will be returned", function () {
            var source_value_ids = [166];
            var target_field = {
                field_id: 83,
                values: [{ id: 993 }],
            };

            var filtered_values = FieldDependenciesService.getTargetFieldPossibleValues(
                source_value_ids,
                target_field,
                undefined
            );

            expect(filtered_values).toEqual([]);
        });
    });

    describe("setUpFieldDependenciesActions() -", function () {
        var callback;
        beforeEach(function () {
            callback = jest.fn();
        });

        it("Given field dependencies with one rule were defined in the tracker and given a callback, when I set up field dependencies actions, then the callback will be called once with the source field id, the target field object and the field_dependencies_rules as parameters", function () {
            var target_field = { field_id: 22 };
            var field_dependencies_rules = [
                {
                    source_field_id: 43,
                    source_value_id: 752,
                    target_field_id: 22,
                    target_value_id: 519,
                },
            ];
            var tracker = {
                fields: [target_field],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules,
                    },
                },
            };

            FieldDependenciesService.setUpFieldDependenciesActions(tracker, callback);

            expect(callback).toHaveBeenCalledWith(43, target_field, field_dependencies_rules);
            expect(callback.mock.calls.length).toEqual(1);
        });

        it("Given field dependencies were defined in the tracker and given there were two rules for different source fields, and given a callback, when I set up field dependencies actions, then the callback will be called twice", function () {
            var field_dependencies_rules = [
                {
                    source_field_id: 81,
                    source_value_id: 171,
                    target_field_id: 69,
                    target_value_id: 940,
                },
                {
                    source_field_id: 94,
                    source_value_id: 938,
                    target_field_id: 51,
                    target_value_id: 990,
                },
            ];
            var tracker = {
                fields: [{ field_id: 69 }, { field_id: 51 }],
                workflow: {
                    rules: {
                        lists: field_dependencies_rules,
                    },
                },
            };

            FieldDependenciesService.setUpFieldDependenciesActions(tracker, callback);

            expect(callback.mock.calls.length).toEqual(2);
            expect(callback).toHaveBeenCalledWith(81, { field_id: 69 }, field_dependencies_rules);
            expect(callback).toHaveBeenCalledWith(94, { field_id: 51 }, field_dependencies_rules);
        });

        it("Given field dependencies were defined in the tracker and given there were two rules for the same source field and given a callback, when I set up field dependencies actions, then the callback will be called twice", function () {
            var tracker = {
                fields: [{ field_id: 10 }],
                workflow: {
                    rules: {
                        lists: [
                            {
                                source_field_id: 43,
                                source_value_id: 752,
                                target_field_id: 22,
                                target_value_id: 519,
                            },
                            {
                                source_field_id: 43,
                                source_value_id: 723,
                                target_field_id: 59,
                                target_value_id: 272,
                            },
                        ],
                    },
                },
            };

            FieldDependenciesService.setUpFieldDependenciesActions(tracker, callback);

            expect(callback).toHaveBeenCalled();
            expect(callback.mock.calls.length).toEqual(2);
        });

        it("Given no field dependencies were defined in the tracker, when I set up field dependencies actions, then the callback will never be called", function () {
            var tracker = {
                fields: [{ field_id: 3 }],
                workflow: {},
            };

            FieldDependenciesService.setUpFieldDependenciesActions(tracker, callback);

            expect(callback).not.toHaveBeenCalled();
        });

        it("Given no callback, when I set up field dependencies actions, then there won't be an error", function () {
            var tracker = {
                fields: [{ field_id: 22 }],
                workflow: {
                    rules: {
                        lists: [
                            {
                                source_field_id: 43,
                                source_value_id: 752,
                                target_field_id: 22,
                                target_value_id: 519,
                            },
                        ],
                    },
                },
            };

            FieldDependenciesService.setUpFieldDependenciesActions(tracker);

            expect(callback).not.toHaveBeenCalled();
        });
    });
});
