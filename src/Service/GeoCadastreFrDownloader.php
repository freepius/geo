<?php

namespace App\Service;

/**
 * Download French cadastral data from `data.gouv.fr`.
 */
class GeoCadastreFrDownloader extends Downloader
{
    protected const CADASTRE_BASE_URL =
        'https://cadastre.data.gouv.fr/data/etalab-cadastre/latest/geojson/communes/%s/%s/';

    public const AVAILABLE_FILENAMES = [
        'batiments',
        'communes',
        'feuilles',
        'lieux_dits',
        'parcelles',
        'prefixes_sections',
        'sections',
        'subdivisions_fiscales',
    ];

    /**
     * List of file names to download.
     */
    protected array $filenames;

    /**
     * Get the file names to download.
     */
    public function getFilenames(): array
    {
        return $this->filenames ?? static::AVAILABLE_FILENAMES;
    }

    /**
     * Set the file names to download (if empty, download all files).
     */
    public function setFilenames(array $filenames): self
    {
        $this->filenames = [] === $filenames
            ? static::AVAILABLE_FILENAMES
            : array_intersect($filenames, static::AVAILABLE_FILENAMES)
        ;

        return $this;
    }

    /**
     * Download the cadastral data for one or more INSEE codes.
     */
    public function downloadByInsee(array|string $inseeCodes): self
    {
        $inseeCodes = (array) $inseeCodes;

        foreach ((array) $inseeCodes as $code) {
            $filenames = $this->getFilenamesByOneInsee($code);

            $this->downloadFiles(
                $this->getBaseUrlByOneInsee($code),
                $filenames,
                1 === count($filenames) ? null : $code
            );
        }

        return $this;
    }

    /**
     * Get the base URL where to find all files for one INSEE code.
     */
    protected function getBaseUrlByOneInsee(string $inseeCode): string
    {
        return sprintf(static::CADASTRE_BASE_URL, substr($inseeCode, 0, 2), $inseeCode);
    }

    /**
     * Get file names to download for one INSEE code.
     * A file name is like `cadastre-<inseeCode>-<file>.json.gz`.
     */
    protected function getFilenamesByOneInsee(string $inseeCode): array
    {
        return array_map(
            fn($filename) => sprintf('cadastre-%s-%s.json.gz', $inseeCode, $filename),
            $this->getFilenames()
        );
    }
}
