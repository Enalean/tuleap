<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

class User_SSHKeyValidator_KeyValidation extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock;

    /** @var PFUser*/
    protected $user;
    /**
     * @var string
     */
    protected $key1;
    /**
     * @var string
     */
    protected $key2;
    /**
     * @var User_SSHKeyValidator
     */
    protected $validator;

    protected function setUp() : void
    {
        $this->key1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAxo4yIDI6bkSUVgXMZYmBZNDl3ttYUIxaThIX1hjp+Oxjo1yeI+vytb1UvESnu1fAhNB40KpPwL7md+UwfHyo2Jah9PMq6bfrSupAE6NOJQ4xG5W7hP70ih5UZtA9YuZfzDc7JsCpwlF7Fvhc+1u4uRYxuKQ+4SpzxCNkmMAMD9BzjXq0Jt/6MsEz+Txt6xoo+HAZXUnUq/XgqMh1A71zAjz6E1ADsd1vLYekQruy9uzhnq9Q7bi+evS1bvi7/O+csAqpIvN/stBqIzALpoAGY1Ek/YMKxjzNurnRTtwEuvqciaPk4aZGg5UvWL1B+yo7HuG/Je0KSz/+u+1efqLUxw== user@shunt';
        $this->key2 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAo2Z2ru57hk2p9wMkM66IxYV1HFKEJvWjWi7Otug/G14LWwO1VU5wNBJgJEfaAoL7ccRpWYpRKLAZdAPYq8nOVFsTU0X4z4mtIo8L1mlw+qXZ3KW77/QJ7sNbCZe6vpNcKg0+DX0e4n0h6R+lXIwi/ISM6wXPQU3uUKVRbcykC9YwEnQokFXXHRqeBzPjyRFval4SRMHAdcs2pjZtu5Et0pObR+Lrs532NE1tvDUrPbU1Oy+9w7bbcvbfjKeYX7FgdXmlYDYLcAfZG4wCHBBYbp5HNXTxhwv4wHq7Z20tEN4qqBnehCGPOpBIgbfBTdN9NftloRYrVPNAxKXhPd/VRQ== user@crampons';

        $this->user = new PFUser([
            'language_id' => 'en_US',
        ]);
        $this->validator = new User_SSHKeyValidator();
    }

    public function testItDoesntRaiseAnErrorWhenTheKeyIsValid() : void
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->never();

        $this->assertEquals(
            array($this->key1),
            $this->validator->validateAllKeys(array($this->key1))
        );
    }

    public function testItDoesntRaiseAnErrorWhenAllTheKeysAreValid() : void
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->never();

        $this->assertEquals(
            array($this->key1, $this->key2),
            $this->validator->validateAllKeys(array(
                $this->key1,
                $this->key2
            ))
        );
    }

    public function testItRaisesAWarningWhenTheKeyIsInvalid() : void
    {
        $keys = array("bla");

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', \Mockery::any())->once();

        $this->assertCount(0, $this->validator->validateAllKeys($keys));
    }

    public function testItRaisesAWarningWhenTheKeyIsNotValidOutsideAnAuthorizedKeysFile() : void
    {
        $keys = array(
            'tuleap.example.com,192.0.2.1 ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDNpemQvp5G/ldgg5diu/OZdNVV3mHqsHmTBcJKiFnfwxNxzZDdTb7hXQKEd6akU6qbmlGPr8AYMBEfII/C47o/B93y2trghS1dVYKyEq7Md/uZx+NFnGysNiMeWr1jPWHWEiNfKgbZPW6OMY200fNGXROmxvp4BQLID7bPLXVLctvCRO4uD2KlK66uWaql7QuGWxzY2C09d15Q/84oVwcIVook/luP1ieHg6syS9FutO+j0//Hfg2Cze/JrrxIZT2XUUAVeyM9uSwW2bBprmDI8rq3UXUotcJws9Pc4PgK7U5P4w1qBQFRonJSjYbK2+1EXLPvV5S60E2mwu6Ta513'
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', \Mockery::any())->once();

        $this->assertCount(0, $this->validator->validateAllKeys($keys));
    }

    public function testItRaisesAWarningWhenThePublicKeyContentIsNotValid() : void
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', \Mockery::any())->once();

        $keys = [
            'ssh-rsa BBBBB3NzaC1yc2EAAAABIwAAAQEAo2Z2ru57hk2p9wMkM66IxYV1HFKEJvWjWi7Otug/G14LWwO1VU5wNBJgJEfaAoL7ccRpWYpRKLAZdAPYq8nOVFsTU0X4z4mtIo8L1mlw+qXZ3KW77/QJ7sNbCZe6vpNcKg0+DX0e4n0h6R+lXIwi/ISM6wXPQU3uUKVRbcykC9YwEnQokFXXHRqeBzPjyRFval4SRMHAdcs2pjZtu5Et0pObR+Lrs532NE1tvDUrPbU1Oy+9w7bbcvbfjKeYX7FgdXmlYDYLcAfZG4wCHBBYbp5HNXTxhwv4wHq7Z20tEN4qqBnehCGPOpBIgbfBTdN9NftloRYrVPNAxKXhPd/VRQ=='
        ];

        $this->assertCount(0, $this->validator->validateAllKeys($keys));
    }

    public function testItRaisesAWarningWhenTheKeyIsInvalidAmongValidKeys() : void
    {
        $keys = array(
            $this->key1,
            "bla",
            $this->key2
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', \Mockery::any())->once();

        $this->assertEquals(array($this->key1, $this->key2), $this->validator->validateAllKeys($keys));
    }

    public function testItRaisesAWarningWhenTheSameKeyIsAddedTwice() : void
    {
        $keys = array(
            $this->key1,
            $this->key1
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', \Mockery::any())->once();

        $this->assertCount(1, $this->validator->validateAllKeys($keys));
    }
}
