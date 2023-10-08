<?php

namespace App\Command;

use App\Service\GeoCadastreFrDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'geo:download-cadastre-fr',
    description: 'Download cadastral data from data.gouv.fr',
)]
class GeoDownloadCadastreFrCommand extends Command
{
    public function __construct(
        protected GeoCadastreFrDownloader $downloader
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

            ->addOption(
                'files',
                null,
                InputOption::VALUE_OPTIONAL,
                'Files to download (comma separated) ; if empty, download all files',
                implode(',', $this->downloader->getFilenames())
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inseeCodes = $this->processInseeCodes(
            $input->getArgument('insee')
        );

        if (0 === $count = count($inseeCodes)) {
            $io->error('No valid INSEE codes provided');
            return Command::FAILURE;
        }

        $this->downloader->setOutputDir(
            $input->getOption('output')
        );

        $this->downloader->setFilenames(
            $this->processFilenames(
                $input->getOption('files')
            )
        );

        $io->writeln(sprintf(
            'Downloading cadastral data for %d %s : %s',
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

    /**
     * The file names are provided as a comma separated string (e.g. "cadastre, sections").\
     * Each file name must be one of the available file names (see `GeoCadastreFrDownloader::AVAILABLE_FILENAMES`).
     */
    protected function processFilenames(string $files): array
    {
        if ('' === $files) {
            return [];
        }

        $files = explode(',', $files);
        $files = array_map('trim', $files);
        $files = array_filter($files, fn($file) => in_array($file, $this->downloader::AVAILABLE_FILENAMES));
        $files = array_unique($files);

        return $files;
    }
}
