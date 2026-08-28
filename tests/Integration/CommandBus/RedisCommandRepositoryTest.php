<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\CommandBus;

use PHPUnit\Framework\Attributes\PostCondition;
use PHPUnit\Framework\Attributes\PreCondition;
use PHPUnit\Framework\Attributes\Test;
use Tempest\CommandBus\AsyncCommandRepositories\RedisCommandRepository;
use Tempest\CommandBus\Exceptions\PendingCommandCouldNotBeResolved;
use Tempest\KeyValue\Redis\Redis;
use Tests\Tempest\Fixtures\Commands\MyCommand;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;
use Throwable;

use function Tempest\Support\Random\uuid;

/**
 * @internal
 */
final class RedisCommandRepositoryTest extends FrameworkIntegrationTestCase
{
    #[PreCondition]
    protected function configure(): void
    {
        try {
            $this->container->get(Redis::class)->connect();
        } catch (Throwable) {
            $this->markTestSkipped('Could not connect to Redis.');
        }
    }

    #[PostCondition]
    protected function cleanup(): void
    {
        try {
            $this->container->get(Redis::class)->flush();
        } catch (Throwable) { // @mago-expect lint:no-empty-catch-clause
        }
    }

    #[Test]
    public function store_and_retrieve(): void
    {
        $repository = $this->container->get(RedisCommandRepository::class);
        $command = new MyCommand();

        $repository->store($uuid = uuid(), $command);

        $pending = $repository->getPendingCommands();
        $this->assertArrayHasKey($uuid, $pending);
        $this->assertEquals($command, $pending[$uuid]);
        $this->assertEquals($command, $repository->findPendingCommand($uuid));

        $repository->markAsFailed($uuid);
        $this->assertArrayNotHasKey($uuid, $repository->getPendingCommands());

        $this->expectException(PendingCommandCouldNotBeResolved::class);
        $repository->findPendingCommand($uuid);
    }

    #[Test]
    public function marking_as_done_removes_record(): void
    {
        $repository = $this->container->get(RedisCommandRepository::class);
        $command = new MyCommand();

        $repository->store($uuid = uuid(), $command);
        $pending = $repository->getPendingCommands();
        $this->assertArrayHasKey($uuid, $pending);

        $repository->markAsDone($uuid);

        $this->assertArrayNotHasKey($uuid, $repository->getPendingCommands());
    }

    #[Test]
    public function cant_find_not_stored_command(): void
    {
        $repository = $this->container->get(RedisCommandRepository::class);

        $uuid = uuid();

        $this->expectException(PendingCommandCouldNotBeResolved::class);
        $repository->findPendingCommand($uuid);
    }
}
