<?php

declare(strict_types=1);

namespace Tempest\CommandBus\AsyncCommandRepositories;

use Tempest\CommandBus\CommandRepository;
use Tempest\CommandBus\Exceptions\PendingCommandCouldNotBeResolved;
use Tempest\KeyValue\Redis\Redis;
use Throwable;

use function Tempest\Support\Str\after_first;

final class RedisCommandRepository implements CommandRepository
{
    private const string PENDING_PREFIX = 'command:pending:';

    private const string FAILED_PREFIX = 'command:failed:';

    public function __construct(
        private Redis $redis,
    ) {}

    public function store(string $uuid, object $command): void
    {
        $this->redis->set(
            key: self::PENDING_PREFIX . $uuid,
            value: serialize($command),
        );
    }

    public function getPendingCommands(): array
    {
        $commands = [];
        $cursor = '0';

        do {
            /** @var array<int,string> $keys */
            [$cursor, $keys] = $this->redis->command('SCAN', $cursor, 'MATCH', self::PENDING_PREFIX . '*', 'COUNT', '100');

            foreach ($keys as $key) {
                $value = $this->redis->get($key);

                if (! is_string($value)) {
                    continue;
                }

                try {
                    $command = unserialize($value);
                } catch (Throwable) {
                    continue;
                }

                if (! is_object($command)) {
                    continue;
                }

                $uuid = after_first($key, self::PENDING_PREFIX);
                $commands[$uuid] = $command;
            }
        } while ($cursor !== '0');

        return $commands;
    }

    public function findPendingCommand(string $uuid): object
    {
        $value = $this->redis->get(self::PENDING_PREFIX . $uuid);

        if (! is_string($value)) {
            throw new PendingCommandCouldNotBeResolved($uuid);
        }

        try {
            $command = unserialize($value);
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
        $this->redis->command('UNLINK', self::PENDING_PREFIX . $uuid);
    }

    public function markAsFailed(string $uuid): void
    {
        $pendingKey = self::PENDING_PREFIX . $uuid;

        if (! $this->redis->command('EXISTS', $pendingKey)) {
            return;
        }

        $this->redis->command('RENAME', $pendingKey, self::FAILED_PREFIX . $uuid);
    }
}
