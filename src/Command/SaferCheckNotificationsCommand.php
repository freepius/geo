<?php

namespace App\Command;

use App\Service\SaferNotificationChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'safer:check-notifications',
    description: 'Check SAFER notifications for specific communes in Drôme (26)',
)]
class SaferCheckNotificationsCommand extends Command
{
    public function __construct(
        private SaferNotificationChecker $notificationChecker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('communes', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Commune names to check (separated by spaces)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output results as JSON')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $communes = $input->getArgument('communes');
        $jsonOutput = $input->getOption('json');

        $io->info(sprintf('Checking SAFER notifications for %d commune(s)...', count($communes)));

        try {
            $results = $this->notificationChecker->checkNotifications($communes);

            if ($jsonOutput) {
                $output->writeln(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }

            if (empty($results)) {
                $io->warning('No notifications found for the specified communes.');
                return Command::SUCCESS;
            }

            $io->success(sprintf('Found notifications for %d commune(s):', count($results)));

            foreach ($results as $commune => $data) {
                $newBadge = $data['isNew'] ? ' <fg=red;options=bold>[NOUVEAU]</>' : '';
                $io->writeln(sprintf(
                    '  • <info>%s</info>: %d notification(s)%s',
                    $commune,
                    $data['count'],
                    $newBadge
                ));
                $io->writeln(sprintf('    %s', $data['url']));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
