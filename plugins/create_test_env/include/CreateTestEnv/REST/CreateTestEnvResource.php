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

use Tuleap\REST\Header;
use Tuleap\CreateTestEnv\MattermostNotifier;
use Tuleap\CreateTestEnv\NotificationBotDao;
use Tuleap\CreateTestEnv\CreateTestEnvironment;
use Tuleap\CreateTestEnv\Exception\CreateTestEnvException;
use Tuleap\CreateTestEnv\Exception\InvalidInputException;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\SenderServices\Sender;
use Luracast\Restler\RestException;

class CreateTestEnvResource
{
    private $notifier;

    public function __construct()
    {
        $this->notifier = new MattermostNotifier(
            new BotFactory(new BotDao()),
            new NotificationBotDao(),
            new Sender(
                new EncoderMessage(),
                new ClientBotMattermost(),
                new BotMattermostLogger()
            )
        );
    }

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
     * @throws 403 RestException You are not authorized to use this route
     * @throws 400 RestException Invalid request
     * @throws 500 RestException Server error
     */
    public function post($secret, $firstname, $lastname, $email)
    {
        try {
            $this->checkSecret($secret);

            $tmp_name = $this->createTempDir();
            $test_env = new CreateTestEnvironment(
                $this->notifier,
                $tmp_name
            );
            $test_env->main($firstname, $lastname, $email);

            $this->cleanUpTempDir($tmp_name);

            return (new TestEnvironmentRepresentation())->build(
                $test_env->getProject(),
                \HTTPRequest::instance()->getServerUrl(),
                $test_env->getUser()
            );
        } catch (InvalidInputException $exception) {
            if (! empty($tmp_name)) {
                $this->cleanUpTempDir($tmp_name);
            }
            $this->notifier->notify('Client error at environment creation: '.$exception->getMessage());
            throw new RestException(400, $exception->getMessage());
        } catch (CreateTestEnvException $exception) {
            if (! empty($tmp_name)) {
                $this->cleanUpTempDir($tmp_name);
            }
            $this->notifier->notify('Server error at environment creation: '.$exception->getMessage());
            throw new RestException(500, $exception->getMessage());
        }
    }

    private function createTempDir()
    {
        $tmp_name = tempnam(\ForgeConfig::get('codendi_cache_dir'), 'tuleap_create_test_env');
        unlink($tmp_name);
        return $tmp_name;
    }

    private function cleanUpTempDir($tmp_name)
    {
        if (file_exists($tmp_name.'/project.xml')) {
            unlink($tmp_name.'/project.xml');
        }
        if (file_exists($tmp_name.'/users.xml')) {
            unlink($tmp_name.'/users.xml');
        }
        if (is_dir($tmp_name)) {
            rmdir($tmp_name);
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
