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

namespace Tuleap\Test\Builders\Config;

use Tuleap\Config\ConfigKeyMetadata;
use Tuleap\Config\SecretValidator;
use Tuleap\Config\ValueValidator;

final class ConfigKeyMetadataBuilder
{
    private bool $is_secret                    = false;
    private ?SecretValidator $secret_validator = null;
    private ?ValueValidator $value_validator   = null;
    private bool $has_default_value            = false;

    private function __construct(private readonly bool $can_be_modified)
    {
    }

    public static function aModifiableMetadata(): self
    {
        return new self(true);
    }

    public static function aNonModifiableMetadata(): self
    {
        return new self(false);
    }

    public function withValidator(ValueValidator $value_validator): self
    {
        $this->value_validator = $value_validator;

        return $this;
    }

    public function withSecretValidator(SecretValidator $secret_validator): self
    {
        $this->is_secret        = true;
        $this->secret_validator = $secret_validator;

        return $this;
    }

    public function withDefaultValue(): self
    {
        $this->has_default_value = true;

        return $this;
    }

    public function build(): ConfigKeyMetadata
    {
        return new ConfigKeyMetadata(
            'summary',
            $this->can_be_modified,
            $this->is_secret,
            false,
            $this->has_default_value,
            $this->secret_validator,
            $this->value_validator,
            null
        );
    }
}
