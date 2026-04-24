<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CheckCoverageCommand extends Command
{
    protected $signature = 'coverage:check
        {report : Path to a Clover XML coverage report}
        {--min=0 : Minimum covered statement percentage}';

    protected $description = 'Check statement coverage from a Clover XML report.';

    public function handle(): int
    {
        $reportPath = (string) $this->argument('report');
        $minimum = (float) $this->option('min');

        if (! is_file($reportPath)) {
            $this->error(sprintf('Coverage report not found: %s', $reportPath));

            return SymfonyCommand::FAILURE;
        }

        $metrics = $this->readMetrics($reportPath);

        if ($metrics === null) {
            $this->error(sprintf('Coverage report does not contain Clover project metrics: %s', $reportPath));

            return SymfonyCommand::FAILURE;
        }

        [$statements, $coveredStatements] = $metrics;
        $percentage = $statements === 0 ? 100.0 : ($coveredStatements / $statements) * 100;

        $this->line(sprintf(
            'Statement coverage: %.2f%% (%d/%d statements)',
            $percentage,
            $coveredStatements,
            $statements,
        ));

        if ($percentage < $minimum) {
            $this->error(sprintf('Statement coverage %.2f%% is below required %.2f%%.', $percentage, $minimum));

            return SymfonyCommand::FAILURE;
        }

        return SymfonyCommand::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function readMetrics(string $reportPath): ?array
    {
        $document = simplexml_load_file($reportPath);

        if (! $document instanceof SimpleXMLElement) {
            return null;
        }

        $metrics = $document->xpath('/coverage/project/metrics');

        if (! is_array($metrics) || $metrics === []) {
            return null;
        }

        $attributes = $metrics[0]->attributes();

        return [
            (int) ($attributes['statements'] ?? 0),
            (int) ($attributes['coveredstatements'] ?? 0),
        ];
    }
}
