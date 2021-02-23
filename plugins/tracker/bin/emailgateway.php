#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayFilter;

require_once __DIR__ . '/../../../src/www/include/pre.php';

$fd       = fopen("php://stdin", "r");
$raw_mail = "";
while (! feof($fd)) {
    $raw_mail .= fread($fd, 1024);
}
fclose($fd);

$logger = BackendLogger::getDefaultLogger();
$logger->info("Entering email gateway");

$recipient_factory                 = Tracker_Artifact_MailGateway_RecipientFactory::build();
$tracker_config                    = new MailGatewayConfig(new MailGatewayConfigDao());
$user_manager                      = UserManager::instance();
$tracker_factory                   = TrackerFactory::instance();
$artifact_factory                  = Tracker_ArtifactFactory::instance();
$incoming_message_token_builder    = new Tracker_Artifact_IncomingMessageTokenBuilder($recipient_factory);
$incoming_message_insecure_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
    $user_manager,
    $tracker_factory,
    $artifact_factory
);
$incoming_message_factory          = new Tracker_Artifact_MailGateway_IncomingMessageFactory(
    $tracker_config,
    $incoming_message_token_builder,
    $incoming_message_insecure_builder
);
$incoming_mail_dao                 = new Tracker_Artifact_Changeset_IncomingMailDao();

$citation_stripper   = new Tracker_Artifact_MailGateway_CitationStripper();
$notifier            = new Tracker_Artifact_MailGateway_Notifier();
$filter              = new MailGatewayFilter();
$mailgateway_builder = new Tracker_Artifact_MailGateway_MailGatewayBuilder(
    $incoming_message_factory,
    $citation_stripper,
    $notifier,
    $incoming_mail_dao,
    $artifact_factory,
    Tracker_FormElementFactory::instance(),
    new Tracker_ArtifactByEmailStatus($tracker_config),
    $logger,
    $filter
);
$incoming_mail       = new \Tuleap\Tracker\Artifact\MailGateway\IncomingMail($raw_mail);
$mailgateway         = $mailgateway_builder->build($incoming_mail);

try {
    $mailgateway->process($incoming_mail);
} catch (Exception $e) {
    $logger->error($e->getMessage());
}
$logger->info("End email gateway");
