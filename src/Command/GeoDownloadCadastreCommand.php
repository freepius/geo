<?php

namespace App\Command;

use App\Service\GeoCadastreDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'geo:download-cadastre-fr',
    description: 'Download cadastre data from geoportail.gouv.fr',
)]
class GeoDownloadCadastreCommand extends Command
{
    public function __construct(
        protected GeoCadastreDownloader $downloader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'insee',
                InputArgument::REQUIRED,
                'INSEE codes for municipalities to download (comma separated)'
            )

            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output directory',
                $this->downloader->getOutputDir()
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->downloader->setOutputDir(
            $input->getOption('output')
        );

        $inseeCodes = $this->processInseeCodes(
            $input->getArgument('insee')
        );

        if (0 === $count = count($inseeCodes)) {
            $io->error('No valid INSEE codes provided');
            return Command::FAILURE;
        }

        $io->writeln(sprintf(
            'Downloading cadastre data for %d %s : %s',
            $count,
            1 === $count ? 'municipality' : 'municipalities',
            implode(', ', $inseeCodes)
        ));

        $this->downloader->downloadByInsee($inseeCodes);

        return Command::SUCCESS;
    }

    /**
     * The INSEE codes are provided as a comma separated string (e.g. "12345, 67890").\
     * Each code is 5 digits long.
     */
    protected function processInseeCodes(string $codes): array
    {
        $codes = explode(',', $codes);
        $codes = array_map('trim', $codes);
        $codes = array_filter($codes, fn($code) => preg_match('/^\d{5}$/', $code));
        $codes = array_unique($codes);

        return $codes;
    }
}
