<?php

declare(strict_types=1);

namespace Tempest\CommandBus\Installer;

/** @internal */
enum CommandBusStorage: string
{
    case DATABASE = 'database';
    case REDIS = 'redis';
}
