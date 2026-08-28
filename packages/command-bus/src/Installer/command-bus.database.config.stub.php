<?php

use Tempest\CommandBus\AsyncCommandRepositories\DatabaseCommandRepository;
use Tempest\CommandBus\CommandBusConfig;

return new CommandBusConfig(
    commandRepositoryClass: DatabaseCommandRepository::class,
);
