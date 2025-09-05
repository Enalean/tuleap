<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload;

use Codendi_Mail;
use ForgeConfig;
use Psr\Log\LoggerInterface;
use TemplateRendererFactory;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Mail\TemplateWithoutFooter;

final readonly class ProjectImportNotifierStatus implements NotifyProjectImportStatus
{
    public function __construct(private LoggerInterface $logger, private LocaleSwitcher $locale_switcher)
    {
    }

    #[\Override]
    public function notify(\Project $project, \PFUser $project_admin, NotifyProjectImportMessage $message): void
    {
        $this->locale_switcher->setLocaleForSpecificExecutionContext(
            $project_admin->getLocale(),
            function () use ($project, $project_admin, $message) {
                $mail = new Codendi_Mail();
                $mail->setLookAndFeelTemplate(new TemplateWithoutFooter());
                $mail->setFrom(ForgeConfig::get('sys_noreply'));
                $mail->setTo($project_admin->getEmail());
                $mail->setSubject($message->getSubject());

                $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__);

                $mail->setBodyHtml($renderer->renderToString($message->getHTMLTemplateName(), $message->getPresenter()));
                $mail->setBodyText($renderer->renderToString($message->getTextTemplateName(), $message->getPresenter()));

                if (! $mail->send()) {
                    $this->logger->error(
                        "Unable to send project #{$project->getID()} import feedback to user #{$project_admin->getId()}"
                    );
                }
            }
        );
    }
}
