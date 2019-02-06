<?php

namespace Tuleap\Baseline\REST;

use Luracast\Restler\Restler;

class BaselineRestResourcesInjector
{
    public function populate(Restler $restler)
    {
        $restler->addAPIClass('\\Tuleap\\Baseline\\REST\\BaselinesResource', 'baselines');
    }
}
