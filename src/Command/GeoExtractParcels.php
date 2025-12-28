<?php

namespace App\Command;

use App\Service\GeoParcelsExtractor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'geo:extract-parcels',
    description: 'Extract parcels from cadastral data',
)]
class GeoExtractParcels extends Command
{
    public function __construct(
        protected GeoParcelsExtractor $extractor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'yaml',
                InputArgument::REQUIRED,
                'Yaml file with parcels to extract'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->extractor->extract(
            $input->getArgument('yaml')
        );

        return Command::SUCCESS;
    }

    /**
     * Regorganize  the data like this: `insee code => section => parcel => [extraction name, ...]`.
     */
    protected function getAllExtractionsByInseeSectionAndParcel(array $data): array
    {
        $all = [];

        foreach ($data as $name => $extraction) {
            foreach ($extraction['insee'] as $insee => $parcelsBySection) {
                foreach ($parcelsBySection as $section => $parcels) {
                    foreach ($parcels as $parcel) {
                        $all[$insee][$section][$parcel][] = $name;
                    }
                }
            }
        }

        return $all;
    }
}
