#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2011-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 */

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Git\Webhook\GitWebhookStatusLogger;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/gitPlugin.php';

const COUNT_THRESHOLD = 100;
$exit_status_code     = 0;

$git_dao                = new GitDao();
$user_manager           = UserManager::instance();
$git_repository_factory = new GitRepositoryFactory(
    $git_dao,
    ProjectManager::instance()
);
$system_event_manager   = new Git_SystemEventManager(
    SystemEventManager::instance(),
    $git_repository_factory
);

$git_plugin                 = PluginManager::instance()->getPluginByName('git');
$logger                     = $git_plugin->getLogger();
$repository_path            = $argv[1];
$git_exec                   = new Git_Exec($repository_path, $repository_path);
$git_repository_url_manager = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());

$user_name = getenv('GL_USER');
if ($user_name === false) {
    $user_informations = posix_getpwuid(posix_geteuid());
    $user_name         = $user_informations['name'];
}

$http_client          = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
$http_request_factory = HTTPFactoryBuilder::requestFactory();

$mail_builder = new MailBuilder(
    TemplateRendererFactory::build(),
    new MailFilter(
        UserManager::instance(),
        new ProjectAccessChecker(
            PermissionsOverrider_PermissionsOverriderManager::instance(),
            new RestrictedUserCanAccessProjectVerifier(),
            EventManager::instance()
        ),
        new MailLogger()
    )
);

$webhook_dao  = new \Tuleap\Git\Webhook\WebhookDao();
$post_receive = new Git_Hook_PostReceive(
    new Git_Hook_LogAnalyzer(
        $git_exec,
        $logger
    ),
    $git_repository_factory,
    $user_manager,
    new Git_Ci_Launcher(
        new Jenkins_Client(
            $http_client,
            $http_request_factory,
            HTTPFactoryBuilder::streamFactory(),
            new JenkinsCSRFCrumbRetriever($http_client, $http_request_factory)
        ),
        new Git_Ci_Dao(),
        $logger
    ),
    new Git_Hook_ParseLog(
        new Git_Hook_LogPushes(
            $git_dao
        ),
        new Git_Hook_ExtractCrossReferences(
            $git_exec,
            ReferenceManager::instance()
        ),
        $logger
    ),
    $system_event_manager,
    EventManager::instance(),
    new \Tuleap\Git\Webhook\WebhookRequestSender(
        new \Tuleap\Webhook\Emitter(
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            HttpClientFactory::createAsyncClient(),
            new GitWebhookStatusLogger($webhook_dao)
        ),
        new \Tuleap\Git\Webhook\WebhookFactory($webhook_dao),
        $logger
    ),
    new \Tuleap\Git\Hook\PostReceiveMailSender(
        $git_repository_url_manager,
        $mail_builder,
        new \Tuleap\Git\Hook\PostReceiveMailsRetriever(
            new \Tuleap\Git\Notifications\UsersToNotifyDao(),
            new \Tuleap\Git\Notifications\UgroupsToNotifyDao(),
            new UGroupManager()
        )
    )
);

$post_receive->beforeParsingReferences($repository_path);

$count = 0;
while ($count <= COUNT_THRESHOLD && $line = fgets(STDIN)) {
    $count += 1;
    list($old_rev, $new_rev, $refname) = explode(' ', trim($line));
    try {
        $post_receive->execute($repository_path, $user_name, $old_rev, $new_rev, $refname);
    } catch (Exception $exception) {
        $exit_status_code = 1;
        $logger->error("[git post-receive] $repository_path $user_name $refname $old_rev $new_rev " . $exception->getMessage());
    }
}

if ($count >= COUNT_THRESHOLD) {
    echo "*** info: More than " . COUNT_THRESHOLD . " references in push.\n";
    echo "*** info: further analysis skipped: email, reference extraction,\n";
    echo "*** info: trigger of continuous integration...\n";
}

exit($exit_status_code);
