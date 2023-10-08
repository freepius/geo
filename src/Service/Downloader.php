<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Helper class to download files.
 */
class Downloader
{
    public const DEFAULT_OUTPUT_DIR = 'var/download';

    /**
     * Output directory where files will be stored.
     */
    protected string $outputDir;

    /**
     * Get the output directory where files will be stored.
     */
    public function getOutputDir(): string
    {
        return $this->outputDir ?? static::DEFAULT_OUTPUT_DIR;
    }

    /**
     * Set the output directory where files will be stored.
     */
    public function setOutputDir(string $outputDir): self
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    /**
     * Download a file from an URL.
     */
    public function downloadFile(string $url, string $outputFileName): self
    {
        $filesystem = new Filesystem();

        // Create output directory if it does not exist yet
        $filesystem->mkdir($this->getOutputDir());

        $httpClient = HttpClient::create();

        /** @var \Symfony\Contracts\HttpClient\ResponseInterface $response */
        $response = $httpClient->request('GET', $url);

        $filesystem->dumpFile(
            sprintf('%s/%s', $this->getOutputDir(), $outputFileName),
            $response->getContent()
        );

        return $this;
    }

    /**
     * Download a list of files from an URL, and store them in a sub-directory (optional).
     */
    public function downloadFiles(string $baseUrl, iterable $filenames, string $subDir = null): self
    {
        $baseDir = $this->getOutputDir();

        $this->setOutputDir("$baseDir/$subDir");

        foreach ($filenames as $filename) {
            $this->downloadFile($baseUrl . $filename, $filename);
        }

        return $this->setOutputDir($baseDir);
    }
}
