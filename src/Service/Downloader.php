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
     * Symfony Filesystem instance.
     */
    protected Filesystem $fs;

    /**
     * Output directory where files will be stored.
     */
    protected string $outputDir;

    /**
     * Get the Symfony Filesystem instance.
     */
    protected function fs(): Filesystem
    {
        return $this->fs ??= new Filesystem();
    }

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
     * Return the absolute path of the downloaded file.
     */
    public function downloadFile(string $url, string $outputFileName): string
    {
        $httpClient = HttpClient::create();

        /** @var \Symfony\Contracts\HttpClient\ResponseInterface $response */
        $response = $httpClient->request('GET', $url);

        $filePath = sprintf('%s/%s', $this->getOutputDir(), $outputFileName);

        $this->fs()->dumpFile($filePath, $response->getContent());

        return realpath($filePath);
    }

    /**
     * Download a list of files from an URL, and store them in a sub-directory (optional).
     * Return the list of absolute paths of the downloaded files.
     */
    public function downloadFiles(string $baseUrl, iterable $filenames, string $subDir = null): array
    {
        $filePaths = [];

        $baseDir = $this->getOutputDir();

        $this->setOutputDir("$baseDir/$subDir");

        foreach ($filenames as $filename) {
            $filePaths[] = $this->downloadFile("$baseUrl/$filename", $filename);
        }

        $this->setOutputDir($baseDir);

        return $filePaths;
    }

    /**
     * Decompress a `.gz` file and return the result file path.
     * Delete the `.gz` file if needed.
     */
    public function gzDecompress(string $gzFilePath, bool $deleteGzFile = true): string
    {
        $filePath = preg_replace('/\.gz$/', '', $gzFilePath);

        $this->fs()->dumpFile(
            $filePath,
            gzdecode(file_get_contents($gzFilePath))
        );

        if ($deleteGzFile) {
            $this->fs()->remove($gzFilePath);
        }

        return $filePath;
    }
}
