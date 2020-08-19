<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use Tuleap\Color\AllowedColorsCollection;

class AvatarGenerator
{
    public function generate(\PFUser $user, string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        $this->getImage($user)->save($path, null, 'png');
    }

    public function generateAsDataUrl(\PFUser $user): string
    {
        return (string) $this->getImage($user)->encode('data-url');
    }

    private function getImage(\PFUser $user): \Intervention\Image\Image
    {
        $color_collection = new AllowedColorsCollection();
        $colors           = $color_collection->getColors();
        $nb_colors        = count($colors);
        $current_color    = array_keys($colors)[$user->getId() % $nb_colors];

        $image = (new \LasseRafn\InitialAvatarGenerator\InitialAvatar())
            ->autoFont()
            ->size(128)
            ->background($colors[$current_color]['secondary'])
            ->color($colors[$current_color]['text'])
            ->name($user->getRealName())
            ->generate();

        return $image;
    }
}
