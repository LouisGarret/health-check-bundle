<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Command;

use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'health:check',
    description: 'Run all health checks and display their status',
)]
final class HealthCheckCommand extends Command
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->healthCheckService->runAll();

        $io->title('Health Check');

        $rows = [];
        foreach ($result['checks'] as $name => $check) {
            $status = $check['status'] === HealthStatus::Ok ? '<fg=green>✓ OK</>' : '<fg=red>✗ KO</>';
            $error = $check['error'] ?? '';
            $rows[] = [$name, $status, $error];
        }

        if (\count($rows) === 0) {
            $io->warning('No health checks registered.');

            return Command::SUCCESS;
        }

        $io->table(['Check', 'Status', 'Error'], $rows);

        if ($result['status'] === HealthStatus::Ok) {
            $io->success('All checks passed.');

            return Command::SUCCESS;
        }

        $failedCount = \count(array_filter($result['checks'], fn (array $c) => $c['status'] === HealthStatus::Ko));
        $io->error(\sprintf('%d of %d check(s) failed.', $failedCount, \count($result['checks'])));

        return Command::FAILURE;
    }
}
