<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Dto;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Ko = 'ko';
}
