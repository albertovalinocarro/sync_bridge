<?php

namespace App\Command;

use App\Message\OrderReceivedMessage;
use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync:retry-failed',
    description: 'Requeue failed webhook events for reprocessing',
)]
class SyncRetryFailedCommand extends Command
{
    public function __construct(
        private WebhookEventRepository $repository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus
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

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Show what would be retried without actually requeueing'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clientId = $input->getOption('client');
        $dryRun = $input->getOption('dry-run');

        $io->title('SyncBridge - Retry Failed Events');

        $failed = $this->repository->findByStatus('failed', $clientId);

        if (empty($failed)) {
            $io->success('No failed events found.');
            return Command::SUCCESS;
        }

        $io->table(
            ['ID', 'Client', 'Event Type', 'Created At'],
            array_map(fn($e) => [
                $e->getId(),
                $e->getClientId(),
                $e->getEventType(),
                $e->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $failed)
        );

        if ($dryRun) {
            $io->note(sprintf('Dry run — %d event(s) would be requeued.', count($failed)));
            return Command::SUCCESS;
        }

        $io->confirm(
            sprintf('Requeue %d failed event(s)?', count($failed)),
            false
        );

        $count = 0;
        foreach ($failed as $event) {
            $event->setStatus('pending');
            $this->entityManager->flush();
            $this->bus->dispatch(new OrderReceivedMessage($event->getId()));
            $count++;
        }

        $io->success(sprintf('%d event(s) requeued successfully.', $count));

        return Command::SUCCESS;
    }

}