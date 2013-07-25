<?php
header('Content-type: application/json');
echo
'{
    "targets": {
       "200" : {
            "id" : 200,
            "label" : "status",
            "values" : {
                "300" : {
                    "id" : 300,
                    "label" :  "To Do"
                },
                "301" : {
                    "id" : 301,
                    "label" :  "Ongoing"
                },
                "302" : {
                    "id" : 302,
                    "label" :  "Done"
                },
                "303" : {
                    "id" : 303,
                    "label" :  "Cancelled"
                }
            }
        },
       "201" : {
            "id" : 201,
            "label" : "assigned to",
            "values" : {
                "310" : {
                    "id" : 310,
                    "label" :  "mickey"
                },
                "311" : {
                    "id" : 311,
                    "label" :  "donald"
                },
                "312" : {
                    "id" : 312,
                    "label" :  "pluto"
                },
                "313" : {
                    "id" : 313,
                    "label" :  "daisy"
                }
            }
        }
    },

    "conditions": [
        {
            "name" : "at_least_one",
            "operator" : "or"
        },
        {
            "name" : "all_of",
            "operator" : "and"
        }
    ],

    "triggers": {
        "400" : {
            "id" : 400,
            "name" : "bugs",
            "fields" : {
                "500" : {
                    "id" : 500,
                    "label" : "status",
                    "values" : {
                        "600" : {
                            "id" : 600,
                            "label" :  "To Do"
                        },
                        "601" : {
                            "id" : 601,
                            "label" :  "Ongoing"
                        },
                        "602" : {
                            "id" : 602,
                            "label" :  "Done"
                        },
                        "603" : {
                            "id" : 603,
                            "label" :  "Cancelled"
                        }
                    }
                },
                "501" : {
                    "id" : 501,
                    "label" : "severity",
                    "values" : {
                        "610" : {
                            "id" : 610,
                            "label" :  "minor"
                        },
                        "611" : {
                            "id" : 611,
                            "label" :  "average"
                        },
                        "612" : {
                            "id" : 612,
                            "label" :  "serious"
                        },
                        "613" : {
                            "id" : 613,
                            "label" :  "critical"
                        }
                    }
                }
            }
        },
        "401" : {
            "id" : 401,
            "name" : "requests",
            "fields" : {
                "510" : {
                    "id" : 510,
                    "label" : "author",
                    "values" : {
                        "620" : {
                            "id" : 620,
                            "label" :  "lala"
                        },
                        "621" : {
                            "id" : 621,
                            "label" :  "po"
                        },
                        "622" : {
                            "id" : 622,
                            "label" :  "tinky-winky"
                        },
                        "623" : {
                            "id" : 623,
                            "label" :  "dipsy"
                        }
                    }
                },
                "511" : {
                    "id" : 511,
                    "label" : "difficulty",
                    "values" : {
                        "630" : {
                            "id" : 630,
                            "label" :  "easy"
                        },
                        "631" : {
                            "id" : 631,
                            "label" :  "ok"
                        },
                        "632" : {
                            "id" : 632,
                            "label" :  "hard"
                        }
                    }
                }
            }
        }
    }
}';

?>