<?php

declare(strict_types=1);

namespace Tempest\CommandBus\AsyncCommandRepositories;

use Tempest\Database\PrimaryKey;
use Tempest\Database\Table;
use Tempest\Database\Uuid;
use Tempest\DateTime\DateTime;

#[Table('commands')]
final class StoredCommand
{
    #[Uuid]
    public PrimaryKey $id;

    public string $payload;

    public ?DateTime $failed_at = null;
}
