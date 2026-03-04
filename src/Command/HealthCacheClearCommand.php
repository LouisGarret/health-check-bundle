<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'health:cache:clear',
    description: 'Clear cached health check results',
)]
final class HealthCacheClearCommand extends Command
{
    public function __construct(
        private readonly ?CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->cache === null) {
            $io->warning('No cache adapter configured.');

            return Command::SUCCESS;
        }

        $this->cache->delete('health_check_result');
        $io->success('Health check cache cleared.');

        return Command::SUCCESS;
    }
}
