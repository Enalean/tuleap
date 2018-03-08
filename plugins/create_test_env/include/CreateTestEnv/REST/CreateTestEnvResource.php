<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CreateTestEnv\REST;

use Luracast\Restler\RestException;
use Tuleap\CreateTestEnv\CreateTestEnvironment;
use Tuleap\CreateTestEnv\Exception\CreateTestEnvException;
use Tuleap\REST\Header;

class CreateTestEnvResource
{
    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a new set of credential
     *
     * @param string $secret {@from body} Access secret
     * @param string $firstname {@from body} User firstname
     * @param string $lastname {@from body} User lastname
     * @param string $email {@from body} User email
     *
     * @access public
     *
     * @return TestEnvironmentRepresentation
     *
     * @throws RestException
     */
    public function post($secret, $firstname, $lastname, $email)
    {
        try {
            $this->checkSecret($secret);

            $tmp_name = tempnam(\ForgeConfig::get('codendi_cache_dir'), 'tuleap_create_test_env');
            unlink($tmp_name);
            $test_env = new CreateTestEnvironment($tmp_name);
            $test_env->main($firstname, $lastname, $email);

            return (new TestEnvironmentRepresentation())->build(
                $test_env->getProject(),
                \HTTPRequest::instance()->getServerUrl(),
                $test_env->getUser()
            );
        } catch (CreateTestEnvException $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @param $submitted_secret
     * @throws RestException
     */
    private function checkSecret($submitted_secret)
    {
        if (! hash_equals($this->getSecret(), $submitted_secret)) {
            throw new RestException(403, "You are not authorized to use this route");
        }
    }

    /**
     * @return string
     * @throws RestException
     */
    private function getSecret()
    {
        $path = $this->getPlugin()->getPluginEtcRoot().'/creation_secret';
        if (! file_exists($path)) {
            $random = (new \RandomNumberGenerator(32))->getNumber();
            touch($path);
            chmod($path, 0600);
            file_put_contents($path, $random);
            chmod($path, 0400);
        }

        if (substr(sprintf('%o', fileperms($path)), -4) !== '0400') {
            throw new RestException(500, "Secret is not properly secured server side");
        }

        $content = trim(file_get_contents($path));
        if (strlen($content) < 32) {
            throw new RestException(500, "Secret is not strong enough server side");
        }

        return $content;
    }

    /**
     * @return \create_test_envPlugin
     */
    private function getPlugin()
    {
        return \PluginManager::instance()->getPluginByName(\create_test_envPlugin::NAME);
    }
}
