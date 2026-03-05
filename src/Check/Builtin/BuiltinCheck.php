<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Check\Builtin;

enum BuiltinCheck: string
{
    case Doctrine = 'doctrine';
    case AssetMapper = 'asset_mapper';
}
