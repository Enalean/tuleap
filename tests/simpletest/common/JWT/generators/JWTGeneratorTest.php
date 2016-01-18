<?php

use Tuleap\JWT\Generators\JWTGenerator;
use Firebase\JWT\JWT;

class JWTGeneratorTest extends TuleapTestCase {

    /** @var UserManager */
    private $user_manager;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var  JWTGenerator */
    private $jwt_generator;

    /** @var string */
    private $private_key;

    public function setUp() {
        parent::setUp();

        $user = stub('PFUser')->getId()->returns(9);

        $this->user_manager = stub('UserManager')->getCurrentUser()->returns($user);

        $u_groups = array('@site_active');

        $this->ugroup_literalizer = mock('UGroupLiteralizer');
        stub($this->ugroup_literalizer)->getUserGroupsForUserWithArobase()->returns($u_groups);

        $this->private_key   = "private_key_test";
        $this->jwt_generator = new JWTGenerator($this->private_key, $this->user_manager, $this->ugroup_literalizer);
    }

    public function testJWTDecodedWithAlgorithmHS512() {
        $token   = $this->jwt_generator->getToken();
        $decoded = null;
        $decoded = JWT::decode($token, $this->private_key, array('HS512'));
        $this->assertTrue(is_object($decoded));
    }

    public function testContentJWT() {
        $expected = array(
            'user_id'     => 9,
            'user_rights' => array('@site_active')
        );

        $token        = $this->jwt_generator->getToken();
        $decoded      = JWT::decode($token, $this->private_key, array('HS512'));
        $decoded_data = (array) $decoded->data;

        $this->assertEqual($decoded_data, $expected);
    }
}