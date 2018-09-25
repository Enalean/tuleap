<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class User_SSHKeyValidatorTest extends TuleapTestCase {

    /** @var PFUser*/
    protected $user;

    public function setUp() {
        parent::setUp();

        $this->key1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAxo4yIDI6bkSUVgXMZYmBZNDl3ttYUIxaThIX1hjp+Oxjo1yeI+vytb1UvESnu1fAhNB40KpPwL7md+UwfHyo2Jah9PMq6bfrSupAE6NOJQ4xG5W7hP70ih5UZtA9YuZfzDc7JsCpwlF7Fvhc+1u4uRYxuKQ+4SpzxCNkmMAMD9BzjXq0Jt/6MsEz+Txt6xoo+HAZXUnUq/XgqMh1A71zAjz6E1ADsd1vLYekQruy9uzhnq9Q7bi+evS1bvi7/O+csAqpIvN/stBqIzALpoAGY1Ek/YMKxjzNurnRTtwEuvqciaPk4aZGg5UvWL1B+yo7HuG/Je0KSz/+u+1efqLUxw== user@shunt';
        $this->key2 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAo2Z2ru57hk2p9wMkM66IxYV1HFKEJvWjWi7Otug/G14LWwO1VU5wNBJgJEfaAoL7ccRpWYpRKLAZdAPYq8nOVFsTU0X4z4mtIo8L1mlw+qXZ3KW77/QJ7sNbCZe6vpNcKg0+DX0e4n0h6R+lXIwi/ISM6wXPQU3uUKVRbcykC9YwEnQokFXXHRqeBzPjyRFval4SRMHAdcs2pjZtu5Et0pObR+Lrs532NE1tvDUrPbU1Oy+9w7bbcvbfjKeYX7FgdXmlYDYLcAfZG4wCHBBYbp5HNXTxhwv4wHq7Z20tEN4qqBnehCGPOpBIgbfBTdN9NftloRYrVPNAxKXhPd/VRQ== user@crampons';

        $this->user      = aUser()->build();
        $this->validator = new User_SSHKeyValidator();
    }
}

class User_SSHKeyValidator_KeyValidationTest extends User_SSHKeyValidatorTest {

    public function itDoesntRaiseAnErrorWhenTheKeyIsValid() {
        stub($GLOBALS['Response'])->addFeedback()->never();

        $this->assertEqual(
            array($this->key1),
            $this->validator->validateAllKeys(array($this->key1))
        );
    }

    public function itDoesntRaiseAnErrorWhenAllTheKeysAreValid() {
        stub($GLOBALS['Response'])->addFeedback()->never();

        $this->assertEqual(
            array($this->key1, $this->key2),
            $this->validator->validateAllKeys(array(
                $this->key1,
                $this->key2
            ))
        );
    }

    public function itRaisesAWarningWhenTheKeyIsInvalid() {
        $keys = array("bla");

        stub($GLOBALS['Response'])->addFeedback('warning', '*')->once();

        $this->assertCount($this->validator->validateAllKeys($keys), 0);
    }

    public function itRaisesAWarningWhenTheKeyIsNotValidOutsideAnAuthorizedKeysFile()
    {
        $keys = array(
            'tuleap.example.com,192.0.2.1 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDNpemQvp5G/ldgg5diu/OZdNVV3mHqsHmTBcJKiFnfwxNxzZDdTb7hXQKEd6akU6qbmlGPr8AYMBEfII/C47o/B93y2trghS1dVYKyEq7Md/uZx+NFnGysNiMeWr1jPWHWEiNfKgbZPW6OMY200fNGXROmxvp4BQLID7bPLXVLctvCRO4uD2KlK66uWaql7QuGWxzY2C09d15Q/84oVwcIVook/luP1ieHg6syS9FutO+j0//Hfg2Cze/JrrxIZT2XUUAVeyM9uSwW2bBprmDI8rq3UXUotcJws9Pc4PgK7U5P4w1qBQFRonJSjYbK2+1EXLPvV5S60E2mwu6Ta513'
        );

        stub($GLOBALS['Response'])->addFeedback('warning', '*')->once();

        $this->assertCount($this->validator->validateAllKeys($keys), 0);
    }

    public function itRaisesAWarningWhenTheKeyIsInvalidAmongValidKeys() {
        $keys = array(
            $this->key1,
            "bla",
            $this->key2
        );

        stub($GLOBALS['Response'])->addFeedback('warning', '*')->once();

        $this->assertEqual(array($this->key1, $this->key2), $this->validator->validateAllKeys($keys));
    }

    public function itRaisesAWarningWhenTheSameKeyIsAddedTwice() {
        $keys = array(
            $this->key1,
            $this->key1
        );

        stub($GLOBALS['Response'])->addFeedback('warning', '*')->once();

        $this->assertCount($this->validator->validateAllKeys($keys), 1);
    }
}

class User_SSHKeyValidator_InputManagementTest extends User_SSHKeyValidatorTest {

    public function itUpdatesWithOneKey() {
        $keys = $this->validator->validateAllKeys(array($this->key1));

        $this->assertCount($keys, 1);
        $this->assertEqual($this->key1, $keys[0]);
    }

    public function itUpdatesWithTwoKeys() {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1,
            $this->key2
        ));

        $this->assertCount($keys, 2);
        $this->assertEqual($this->key1, $keys[0]);
        $this->assertEqual($this->key2, $keys[1]);
    }

    public function itUpdatesWithAnExtraSpaceAfterFirstKey() {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1." ",
            $this->key2
        ));

        $this->assertCount($keys, 2);
        $this->assertEqual($this->key1, $keys[0]);
        $this->assertEqual($this->key2, $keys[1]);
    }

    public function itUpdatesWithAnEmptyKey() {
        $keys = $this->validator->validateAllKeys(array(
            $this->key1,
            '',
            $this->key2
        ));

        $this->assertCount($keys, 2);
        $this->assertEqual($this->key1, $keys[0]);
        $this->assertEqual($this->key2, $keys[1]);
    }
}