#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require 'pre.php';

$fd = fopen("php://stdin", "r");
$raw_mail = "";
while (!feof($fd)) {
    $raw_mail .= fread($fd, 1024);
}
fclose($fd);

$logger = new BackendLogger();
$logger->info("Entering email gateway");

$recipient_factory = Tracker_Artifact_MailGatewayRecipientFactory::build();

$parser           = new Tracker_Artifact_MailGateway_Parser($recipient_factory);
$citation_sripper = new Tracker_Artifact_MailGateway_CitationStripper();
$mailgateway      = new Tracker_Artifact_MailGateway_MailGateway(
    $parser,
    $citation_sripper,
    $logger
);

try {
    $mailgateway->process($raw_mail);
} catch (Exception $e) {
    $logger->error($e->getMessage());
}
$logger->info("End email gateway");