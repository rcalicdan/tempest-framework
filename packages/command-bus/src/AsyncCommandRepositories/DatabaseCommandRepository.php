<?php

declare(strict_types=1);

namespace Tempest\CommandBus\AsyncCommandRepositories;

use Tempest\Clock\Clock;
use Tempest\CommandBus\CommandRepository;
use Tempest\CommandBus\Exceptions\PendingCommandCouldNotBeResolved;
use Throwable;

use function Tempest\Database\query;

final readonly class DatabaseCommandRepository implements CommandRepository
{
    public function __construct(
        private Clock $clock,
    ) {}

    public function store(string $uuid, object $command): void
    {
        query(StoredCommand::class)
            ->insert(
                id: $uuid,
                payload: serialize($command),
            )
            ->execute();
    }

    public function getPendingCommands(): array
    {
        $commands = [];

        $rows = query(StoredCommand::class)
            ->select()
            ->whereNull('failed_at')
            ->all();

        foreach ($rows as $row) {
            try {
                $command = unserialize($row->payload);
            } catch (Throwable) {
                continue;
            }

            if (! is_object($command)) {
                continue;
            }

            $commands[(string) $row->id] = $command;
        }

        return $commands;
    }

    public function findPendingCommand(string $uuid): object
    {
        $row = query(StoredCommand::class)
            ->select()
            ->whereField('id', $uuid)
            ->whereNull('failed_at')
            ->first();

        if ($row === null) {
            throw new PendingCommandCouldNotBeResolved($uuid);
        }

        try {
            $command = unserialize($row->payload);
        } catch (Throwable) {
            throw new PendingCommandCouldNotBeResolved($uuid);
        }

        if (! is_object($command)) {
            throw new PendingCommandCouldNotBeResolved($uuid);
        }

        return $command;
    }

    public function markAsDone(string $uuid): void
    {
        query(StoredCommand::class)
            ->delete()
            ->whereField('id', $uuid)
            ->whereNull('failed_at')
            ->execute();
    }

    public function markAsFailed(string $uuid): void
    {
        query(StoredCommand::class)
            ->update(failed_at: $this->clock->now())
            ->whereField('id', $uuid)
            ->execute();
    }
}
