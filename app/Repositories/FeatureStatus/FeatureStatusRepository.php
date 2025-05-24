<?php

namespace App\Repositories\FeatureStatus;

use LaravelEasyRepository\Repository;

interface FeatureStatusRepository extends Repository{

    function getFeatureStatus(): array;
}
