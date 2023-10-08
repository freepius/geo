<?php

namespace App\Service;

/**
 * Download French cadastral data from `data.gouv.fr`.
 */
class GeoCadastreDownloader extends Downloader
{
    protected const CADASTRE_BASE_URL =
        'https://cadastre.data.gouv.fr/data/etalab-cadastre/latest/geojson/communes/%s/%s/';

    protected const AVAILABLE_FILES = [
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
     * Download the cadastral data by one or more INSEE codes.
     */
    public function downloadByInsee(array|string $inseeCodes): void
    {
        $inseeCodes = (array) $inseeCodes;

        foreach ((array) $inseeCodes as $code) {
            $this->downloadFiles(
                $this->getBaseUrlByOneInsee($code),
                $this->getFilenamesToDownloadByOneInsee($code),
                $code
            );
        }
    }

    /**
     * Get the base URL where to find all files for one INSEE code.
     */
    protected function getBaseUrlByOneInsee(string $inseeCode): string
    {
        return sprintf(static::CADASTRE_BASE_URL, substr($inseeCode, 0, 2), $inseeCode);
    }

    /**
     * Get all file names to download for one INSEE code.
     * A file name is like `cadastre-<inseeCode>-<file>.json.gz`.
     */
    protected function getFilenamesToDownloadByOneInsee(string $inseeCode): iterable
    {
        foreach (static::AVAILABLE_FILES as $file) {
            yield sprintf('cadastre-%s-%s.json.gz', $inseeCode, $file);
        }
    }
}
