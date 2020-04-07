<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use Exception;

class CustomMetadataException extends Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message)
    {
        parent::__construct($message);
        $this->i18n_message = $i18n_message;
    }

    public static function metadataNotFound(string $metadata_name): self
    {
        return new self(
            sprintf("metadata %s is not found", $metadata_name),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'The property with short name %s does not exist in project.'
                ),
                $metadata_name
            )
        );
    }

    public static function valueProvidedForListMetadata(string $metadata_name): self
    {
        return new self(
            sprintf("metadata %s is a multiple list", $metadata_name),
            dgettext(
                'tuleap-docman',
                sprintf('The property with short name %s is a multiple list, value should be empty and list_value should be provided.', $metadata_name)
            )
        );
    }

    public static function listValueProvidedForMetadata(string $metadata_name): self
    {
        return new self(
            sprintf("metadata %s is not a list and a list_value is provided", $metadata_name),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'The property with short name %s is a value, list_value should be empty and value should be provided.'
                ),
                $metadata_name
            )
        );
    }

    public static function listOnlyAcceptSingleValues(string $metadata_name): self
    {
        return new self(
            sprintf("list %s has too many values", $metadata_name),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'The list type property %s can accept only one value.'
                ),
                $metadata_name
            )
        );
    }

    public static function unknownValue(array $error_unknown, string $metadata_name): self
    {
        $errors_string = implode(',', $error_unknown);
        return new self(
            sprintf("value: %s are unknown for metadata %s", $errors_string, $metadata_name),
            sprintf(
                dngettext(
                    'tuleap-docman',
                    "The value '%s' of '%s' is unknown",
                    "The values of '%s' for field '%s' are unknown",
                    count($error_unknown)
                ),
                $errors_string,
                $metadata_name
            )
        );
    }

    public static function missingKeysForCreation(array $errors): self
    {
        $errors_string = implode(',', $errors);
        return new self(
            sprintf("missing metadata keys: %s", $errors_string),
            sprintf(
                dngettext(
                    'tuleap-docman',
                    "The metadata '%s' short_name must be provided for document creation",
                    "The metadata '%s' short_names must be provided for document creation",
                    count($errors)
                ),
                $errors_string
            )
        );
    }

    public static function missingRequiredKeysForCreation(array $errors): self
    {
        $errors_string = implode(',', $errors);
        return new self(
            sprintf("missing required values for: %s", $errors_string),
            sprintf(
                dngettext(
                    'tuleap-docman',
                    "The value of '%s' is required",
                    "The values of '%s' are required",
                    count($errors)
                ),
                $errors_string
            )
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
