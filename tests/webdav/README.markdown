WebDAV scripting to ease test suite
===================================

*WARNING*: This is a work in progress. The aim of this is to replace the
testlink test suite for webdav. However not all tests are covered as of today.

Setup
-----

    $> sudo apt-get install cadaver

Launch
------

Add your login information in ~/.netrc file

    machine c5-53.valid.enalean.com login projectadmin password projectadmin_password

(replace `projectadmin` and `projectadmin_password` with real values)

Then launch the following command:

    cadaver -t http://c5-53.valid.enalean.com/plugins/webdav/ < dav.script.txt > output.txt

Assertion
---------

* You should have a fixture file named TOTO that contains 'Lorem ipsum' string.
* The output should look like the `expected.txt`.

      diff -u expected.txt output.txt

There is a `expected.bigfile.txt` to check when you set the `max_fil_size` webdav parameter to `1`.

Clean-up
--------

    rm output.txt TOTO

TODO
----

* Testlink #163, step 3 (upload same document = new version)
* Testlink #120, step 3 (download file with size upper than max size)
* Testlink #166 (without write permissions I cannot create stuff)
* Testlink #233 (folders with same name does not appear in webdav)
* Hidden package in FRS
* Write a makefile to orchestrate launching, assertions and clean-up
