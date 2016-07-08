How to run the tests
====================

For regular tests:

    docker run --rm -ti -v $PWD:/usr/share/tuleap -v $PWD:/output enalean/tuleap-test-rest:c6-php53-httpd22-mysql51

For cutting edge tests:

    docker run --rm -ti -v $PWD:/usr/share/tuleap -v $PWD:/output enalean/tuleap-test-rest:c6-php56-httpd24-mysql56
