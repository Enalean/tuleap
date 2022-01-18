'use strict';

import { describe, it, expect, beforeEach } from 'vitest';

var Scores = require('../../../backend/modules/scores');
var scores = new Scores();

describe("Module Scores", function() {
    var user_id = 101,
        room_id = 6;

    var user = {
        id: user_id
    };

    var user_updated = {
        id: user_id,
        avatar: 'avatar.png'
    };

    beforeEach(function() {
        scores.user_scores_collection = {};
    });

    describe("update()", function() {
        it("Given user id with new information, when I verify and update then user information are changed", function() {
            var data = {
                artifact_id: 40,
                status: 'blocked',
                previous_status: 'blocked',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user_updated, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0,
                            avatar: 'avatar.png'
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id and new status of an execution equal to 'notrun', when I verify and update then there are no changements", function() {
            var data = {
                artifact_id: 40,
                status: 'notrun',
                previous_status: 'blocked',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id and new status of an execution equal to previous status, when I change the status then there are no changements", function() {
            var data = {
                artifact_id: 40,
                status: 'blocked',
                previous_status: 'blocked',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id and new status of an execution not equal to previous status, when I change the status from notrun to passed then the score is updated", function() {
            var data = {
                artifact_id: 40,
                status: 'passed',
                previous_status: 'notrun',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 1
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id and new status of an execution not equal to previous status, when I change the status from notrun to failed then the score is updated", function() {
            var data = {
                artifact_id: 40,
                status: 'failed',
                previous_status: 'notrun',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 1
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from failed to notrun then the score is updated for the previous user", function() {
            var data = {
                artifact_id: 40,
                status: 'notrun',
                previous_status: 'failed',
                previous_user: {
                    id: 102
                }
            };

            scores.user_scores_collection[102] = [
                {
                    room_id: room_id,
                    user: {
                        id: 102,
                        score: 1
                    }
                }
            ];
            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from passed to blocked then the score is updated for the previous user", function() {
            var data = {
                artifact_id: 40,
                status: 'blocked',
                previous_status: 'passed',
                previous_user: {
                    id: 102
                }
            };

            scores.user_scores_collection[102] = [
                {
                    room_id: room_id,
                    user: {
                        id: 102,
                        score: 1
                    }
                }
            ];
            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from failed to blocked then the score is updated for the previous user", function() {
            var data = {
                artifact_id: 40,
                status: 'blocked',
                previous_status: 'failed',
                previous_user: {
                    id: 102
                }
            };

            scores.user_scores_collection[102] = [
                {
                    room_id: room_id,
                    user: {
                        id: 102,
                        score: 1
                    }
                }
            ];
            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from failed to passed then the score is updated for the user", function() {
            var data = {
                artifact_id: 40,
                status: 'passed',
                previous_status: 'failed',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 1
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from passed to notrun then the score is updated for the previous user", function() {
            var data = {
                artifact_id: 40,
                status: 'notrun',
                previous_status: 'passed',
                previous_user: {
                    id: 102
                }
            };

            scores.user_scores_collection[102] = [
                {
                    room_id: room_id,
                    user: {
                        id: 102,
                        score: 1
                    }
                }
            ];
            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });

        it("Given user id, new status of an execution and a previous submitter id, when I change the status test from blocked to notrun then the score isn't updated", function() {
            var data = {
                artifact_id: 40,
                status: 'notrun',
                previous_status: 'blocked',
                previous_user: {
                    id: 102
                }
            };

            expect(scores.update).toBeDefined();
            scores.update(user, room_id, data);
            expect(scores.user_scores_collection).toEqual({
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 0
                        }
                    }
                ],
                102: [
                    {
                        room_id: room_id,
                        user: {
                            id: 102,
                            score: 0
                        }
                    }
                ]
            });
        });
    });

    describe("getScoreByUserIdAndRoomId()", function() {
        beforeEach(function() {
            scores.user_scores_collection = {
                101: [
                    {
                        room_id: room_id,
                        user: {
                            id: 101,
                            score: 1
                        }
                    }
                ]
            };
        });

        it("Given a user score in collection, when I get score by user and campaign then the score is returned", function() {
            expect(scores.getScoreByUserIdAndRoomId).toBeDefined();
            expect(scores.getScoreByUserIdAndRoomId(user_id, room_id)).toEqual(1);
        });

        it("Given a user score in collection, when I get score by user who doesn't exist then 0 is returned", function() {
            expect(scores.getScoreByUserIdAndRoomId).toBeDefined();
            expect(scores.getScoreByUserIdAndRoomId(106, room_id)).toEqual(0);
        });

        it("Given a user score in collection, when I get score by campaign who doesn't exist then 0 is returned", function() {
            expect(scores.getScoreByUserIdAndRoomId).toBeDefined();
            expect(scores.getScoreByUserIdAndRoomId(user_id, 7)).toEqual(0);
        });
    });
});