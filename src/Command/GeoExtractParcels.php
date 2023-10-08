<?php

namespace App\Command;

use App\Service\GeoCadastreFrDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'geo:extract-parcels',
    description: 'Extract parcels from cadastral data',
)]
class GeoExtractParcels extends Command
{
    public const EXTRACTION_OUTPUT_DIR = 'var/geojson';

    /**
     * Symfony Filesystem instance.
     */
    protected readonly Filesystem $fs;

    public function __construct(
        protected GeoCadastreFrDownloader $downloader
    ) {
        parent::__construct();

        $this->fs = new Filesystem();
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

        $data = Yaml::parseFile(
            $input->getArgument('yaml')
        );

        $pathByExtraction = [];

        /**
         * For each extraction, create a geojson file
         * containing only the "FeatureCollection" declaration.
         */
        foreach ($data as $name => $extraction) {
            $io->writeln("Extraction: $name");

            $pathByExtraction[$name] = $path = sprintf('%s/%s.geojson', self::EXTRACTION_OUTPUT_DIR, $name);
            $io->writeln("  - Create output geojson file: $path");

            $this->fs->dumpFile($path, '{"type": "FeatureCollection","features": [' . PHP_EOL);
        }

        $data = $this->getAllExtractionsByInseeSectionAndParcel($data);

        foreach ($data as $insee => $extractionsBySectionAndParcel) {
            $io->writeln("Download, decompress and browse the \"parcelles\" file for INSEE $insee.");

            [$path] = $this->downloader
                ->setFilenames(['parcelles'])
                ->downloadByInsee($insee)
            ;

            $handle = fopen($path, 'r');

            // Skip the first line (the "FeatureCollection" declaration).
            fgets($handle);

            // Read all the "Feature" lines (each one corresponding to a parcel).
            while ($line = fgets($handle)) {
                // Extract the parcel ID.
                // eg: {"type":"Feature","id":"260650000B0132"
                $id = substr($line, 24, 14);

                // From this ID, extract section and parcel numbers.
                // eg: 260650000B0132 => 0B and 0132
                $section = substr($id, 8, 2);
                $parcel = substr($id, 10, 4);

                // Remove their right extra zeros.
                // eg: 0B => B and 0132 => 132
                $section = ltrim($section, '0');
                $parcel = ltrim($parcel, '0');

                // If this parcel is not in the "to keep" list, skip it.
                if (null === $extractions = $extractionsBySectionAndParcel[$section][$parcel] ?? null) {
                    continue;
                }

                // For each related extraction, append the parcel line to its geojson file.
                foreach ($extractions as $name) {
                    $io->writeln("  - Append parcel [$section, $parcel] to $name");
                    $this->fs->appendToFile($pathByExtraction[$name], $line);
                }
            }

            fclose($handle);
        }

        // Close the "FeatureCollection" of all geojson files.
        foreach ($pathByExtraction as $path) {
            $this->fs->appendToFile($path, ']}');
        }

        // Remove the "parcelles" files.
        $io->writeln('Remove the "parcelles" files.');
        $this->fs->remove($this->downloader->getOutputDir());

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
