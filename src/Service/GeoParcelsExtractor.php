<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * From a YAML file containing one or more parcels extractions:
 * - retrieve the appropriate "parcelles" geojson files on `cadastre.data.gouv.fr`,
 * - create a geojson file by extraction
 * - then write in it the parcels to extract.
 *
 * The YAML data must be organized like this:
 * ```YAML
 * my-extraction:
 *   insee:
 *     12345:
 *       AB: [123, 456]
 *       CD: [789]
 *     67890: {EF: [123]}
 * ```
 */
class GeoParcelsExtractor extends FilesystemWriter
{
    public const OUTPUT_DIR = 'var/geojson';

    /**
     * Description of the extractions to perform (including the parcels to extract).
     */
    protected array $data;

    /**
     * The path to each extraction file.
     */
    protected array $paths = [];

    public function __construct(
        protected GeoCadastreFrDownloader $downloader
    ) {
    }

    public function extract(string $yamlFile): void
    {
        $this->data = Yaml::parseFile($yamlFile);

        $this->startExtractionFiles();

        $this->organizeExtractionsByInseeSectionAndParcel();

        $this->writeParcelsToExtract();

        $this->endExtractionFiles();
    }

    /**
     * For each extraction, create a geojson file
     * containing only the "FeatureCollection" declaration.
     */
    protected function startExtractionFiles(): void
    {
        foreach (array_keys($this->data) as $name) {
            $this->paths[$name] = $path = sprintf('%s/%s.geojson', $this->getOutputDir(), $name);

            $this->fs()->dumpFile($path, '{"type": "FeatureCollection","features": [' . PHP_EOL);
        }
    }

    /**
     * Regorganize the data like this: `insee code => section => parcel => [extraction name, ...]`.
     */
    protected function organizeExtractionsByInseeSectionAndParcel(): void
    {
        $new = [];

        foreach ($this->data as $name => $extraction) {
            foreach ($extraction['insee'] as $insee => $parcelsBySection) {
                foreach ($parcelsBySection as $section => $parcels) {
                    foreach ($parcels as $parcel) {
                        $new[$insee][$section][$parcel][] = $name;
                    }
                }
            }
        }

        $this->data = $new;
    }

    /**
     * For each INSEE code, download the "parcelles" file,
     * then browse it to extract the searched parcels into the appropriate geojson files.
     */
    protected function writeParcelsToExtract(): void
    {
        // We accumulate the parcel lines for each extraction.
        $allParcelsByExtraction = [];

        foreach ($this->data as $insee => $extractionsBySectionAndParcel) {
            // Download & decompress the "parcelles" file for $insee.
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
                // eg: {"type":"Feature","id":"260650000B0132" => 260650000B0132
                $id = substr($line, 24, 14);

                // From this ID, extract section and parcel numbers.
                // eg: 260650000B0132 => 0B and 0132
                $section = substr($id, 8, 2);
                $parcel = substr($id, 10, 4);

                // Remove their right extra zeros.
                // eg: 0B => B and 0132 => 132
                $section = ltrim($section, '0');
                $parcel = ltrim($parcel, '0');

                // If this parcel is not in the list, skip it.
                if (null === $extractions = $extractionsBySectionAndParcel[$section][$parcel] ?? null) {
                    continue;
                }

                // Append the parcel line to each related extraction.
                foreach ($extractions as $name) {
                    $allParcelsByExtraction[$name][] = $line;
                }
            }

            fclose($handle);
            $this->fs()->remove($path);
        }

        // Write the parcel lines in each geojson file.
        foreach ($allParcelsByExtraction as $name => $parcels) {
            $this->fs()->appendToFile(
                $this->paths[$name],
                // Remove the last comma to avoid a json syntax error.
                rtrim(implode('', $parcels), ',' . PHP_EOL) . PHP_EOL
            );
        }
    }

    /**
     * Close the "FeatureCollection" of all geojson files.
     */
    protected function endExtractionFiles(): void
    {
        foreach ($this->paths as $path) {
            $this->fs()->appendToFile($path, ']}');
        }
    }
}
