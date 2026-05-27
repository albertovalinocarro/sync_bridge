<?php

namespace App\Command;

use App\Repository\WebhookEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync:status',
    description: 'Show webhook event sync status summary per client',
)]
class SyncStatusCommand extends Command
{
    public function __construct(
        private WebhookEventRepository $repository
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'client',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Filter by client ID'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clientId = $input->getOption('client');

        $io->title('SyncBridge - Webhook Event Status');

        $statuses = ['pending', 'processsed', 'synced', 'failed', 'skipped'];
        $rows = [];

        $clients = $clientId
            ? [$clientId]
            : $this->getDistinctClients();

        foreach ($clients as $client) {
            $row   = [$client];
            $total = 0;
            
            foreach ($statuses as $status) {
                $count  = $this->repository->countByFilters($client, $status);
                $row[]  = $count;
                $total += $count;
            }
            
            $row[]  = $total;
            $rows[] = $row;
        }

        $table = new Table($output);
        $table->setHeaders(['Client', 'Pending', 'Processed', 'Synced', 'Failed', 'Skipped', 'Total']);
        $table->setRows($rows);
        $table->render();

        // Highlight if there are failed events
        $totalFailed = array_sum(array_column($rows, 4));
        if ($totalFailed > 0) {
            $io->warning(sprintf('%d failed event(s) need attention. Run app:sync:retry-failed to requeue.', $totalFailed));
        } else {
            $io->success('All events processed successfully.');
        }

        return Command::SUCCESS;
    }

    private function getDistinctClients(): array
    {
        return $this->repository->findDistinctClientIds();
    }

}