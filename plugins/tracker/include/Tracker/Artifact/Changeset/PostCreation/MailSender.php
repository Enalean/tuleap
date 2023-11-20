<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Codendi_HTMLPurifier;
use EventManager;
use MailBuilder;
use MailEnhancer;
use MailNotificationBuilder;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use trackerPlugin;
use Tuleap\Mail\MailAttachment;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

class MailSender
{
    /**
     * Send a notification
     *
     * @param Tracker_Artifact_Changeset $changeset changeset
     * @param array  $recipients the list of recipients
     * @param array  $headers    the additional headers
     * @param string $from       the mail of the sender
     * @param string $subject    the subject of the message
     * @param string $htmlBody   the html content of the message
     * @param string $txtBody    the text content of the message
     * @param string $message_id the id of the message
     * @param MailAttachment[] $attachments
     *
     * @return void
     */
    public function send(Tracker_Artifact_Changeset $changeset, $recipients, $headers, $from, $subject, $htmlBody, $txtBody, $message_id, array $attachments)
    {
        $hp                = Codendi_HTMLPurifier::instance();
        $breadcrumbs       = [];
        $tracker           = $changeset->getTracker();
        $project           = $tracker->getProject();
        $artifactId        = $changeset->getArtifact()->getId();
        $project_unix_name = $project->getUnixName(true);
        $tracker_name      = $tracker->getItemName();
        $mail_enhancer     = new MailEnhancer();

        foreach ($attachments as $attachment) {
            $mail_enhancer->addAttachment($attachment);
        }

        if ($message_id) {
            $mail_enhancer->setMessageId($message_id);
        }

        $server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $breadcrumbs[] = '<a href="' . $server_url . '/projects/' . $project_unix_name . '" />' . $hp->purify($project->getPublicName()) . '</a>';
        $breadcrumbs[] = '<a href="' . $server_url . '/plugins/tracker/?tracker=' . (int) $tracker->getId() . '" />' . $hp->purify($changeset->getTracker()->getName()) . '</a>';
        $breadcrumbs[] = '<a href="' . $server_url . '/plugins/tracker/?aid=' . (int) $artifactId . '" />' . $hp->purify($changeset->getTracker()->getName() . ' #' . $artifactId) . '</a>';

        $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
        $mail_enhancer->addPropertiesToLookAndFeel('unsubscribe_link', $this->getUnsubscribeLink($changeset->getArtifact()));
        $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));
        $mail_enhancer->addHeader("X-Codendi-Project", $project->getUnixName());
        $mail_enhancer->addHeader("X-Codendi-Tracker", $tracker_name);
        $mail_enhancer->addHeader("X-Codendi-Artifact-ID", $artifactId);
        $mail_enhancer->addHeader('From', $from);

        foreach ($headers as $header) {
            $mail_enhancer->addHeader($header['name'], $header['value']);
        }

        if ($htmlBody) {
            $htmlBody .= $this->getHTMLBodyFilter($project_unix_name, $tracker_name);
        }

        $txtBody .= $this->getTextBodyFilter($project_unix_name, $tracker_name);

        $mail_notification_builder = new MailNotificationBuilder(
            new MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(
                    UserManager::instance(),
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new MailLogger()
                )
            )
        );
        $mail_notification_builder->buildAndSendEmail(
            $project,
            $recipients,
            $subject,
            $htmlBody,
            $txtBody,
            $server_url . $changeset->getUri(),
            trackerPlugin::TRUNCATED_SERVICE_NAME,
            $mail_enhancer
        );
    }

    private function getTextBodyFilter($project_name, $tracker_name)
    {
        $project_filter = '=PROJECT=' . $project_name;
        $tracker_filter = '=TRACKER=' . $tracker_name;

        return PHP_EOL . $project_filter . PHP_EOL . $tracker_filter . PHP_EOL;
    }

    private function getHTMLBodyFilter($project_name, $tracker_name)
    {
        $filter  = '<div style="display: none !important;">';
        $filter .= '=PROJECT=' . $project_name . '<br>';
        $filter .= '=TRACKER=' . $tracker_name . '<br>';
        $filter .= '</div>';

        return $filter;
    }

    /**
     * @return string html call to action button to include in an html mail
     */
    private function getUnsubscribeLink(Artifact $artifact)
    {
        $link = \Tuleap\ServerHostname::HTTPSUrl() . '/plugins/tracker/?aid=' . (int) $artifact->getId() . '&func=manage-subscription';

        return '<a href="' . $link . '" target="_blank" rel="noreferrer">' .
            dgettext('tuleap-tracker', 'Unsubscribe') .
            '</a>';
    }
}
