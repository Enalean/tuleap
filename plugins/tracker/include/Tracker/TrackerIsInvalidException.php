<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker;

class TrackerIsInvalidException extends \Exception
{
    /**
     * @var string
     */
    private $translated_message;

    private function __construct(string $message)
    {
        parent::__construct($message);
        $this->translated_message = $message;
    }

    public static function buildInvalidLength(): self
    {
        return new self(dgettext('plugin-tracker', 'Tracker shortname length must be inferior to 25 characters.'));
    }

    public static function buildMissingRequiredProperties(): self
    {
        return new self(dgettext('plugin_tracker', 'Name, color, and short name are required.'));
    }

    public static function nameAlreadyExists(string $name): self
    {
        return new self(
            sprintf(
                dgettext('plugin_tracker', 'The tracker name %s is already used. Please use another one.'),
                $name
            )
        );
    }

    public static function nameIsInvalid(string $shortname): self
    {
        return new self(
            sprintf(
                dgettext(
                    'plugin_tracker',
                    'Invalid name: %s. Please use only alphanumerical characters or an unreserved reference.'
                ),
                $shortname
            )
        );
    }

    public static function shortnameIsInvalid(string $shortname): self
    {
        return new self(
            sprintf(
                dgettext(
                    'plugin_tracker',
                    'Invalid short name: %s. Please use only alphanumerical characters or an unreserved reference.'
                ),
                $shortname
            )
        );
    }

    public static function shortnameAlreadyExists(string $shortname): self
    {
        return new self(
            sprintf(
                dgettext('plugin_tracker', 'The tracker short name %s is already used. Please use another one.'),
                $shortname
            )
        );
    }

    public static function invalidTrackerTemplate(): self
    {
        return new self(
            dgettext('plugin_tracker', 'Invalid tracker template.')
        );
    }

    public static function invalidProjectTemplate(): self
    {
        return new self(
            dgettext('plugin_tracker', 'Invalid project template.')
        );
    }

    public static function trackerNotFound(string $template_id): self
    {
        return new self(
            sprintf(
                dgettext('plugin_tracker', 'The template id %s used for tracker creation was not found.'),
                $template_id
            )
        );
    }

    public static function invalidXmlFile(): self
    {
        return new self(
            dgettext('plugin_tracker', 'The provided file is not a valid file')
        );
    }

    public function getTranslatedMessage(): string
    {
        return $this->translated_message;
    }
}
