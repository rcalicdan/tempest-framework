<?php

use Tempest\CommandBus\AsyncCommandRepositories\RedisCommandRepository;
use Tempest\CommandBus\CommandBusConfig;

return new CommandBusConfig(
    commandRepositoryClass: RedisCommandRepository::class,
);
