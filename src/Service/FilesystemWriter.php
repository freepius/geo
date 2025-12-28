<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class FilesystemWriter
{
    public const OUTPUT_DIR = 'var';

    /**
     * Symfony Filesystem instance.
     */
    protected ?Filesystem $fs;

    /**
     * Get the Symfony Filesystem instance.
     */
    protected function fs(): Filesystem
    {
        return $this->fs ??= new Filesystem();
    }

    /**
     * Output directory where files will be stored.
     */
    protected string $outputDir;

    /**
     * Get the output directory where files will be stored.
     */
    public function getOutputDir(): string
    {
        return $this->outputDir ?? static::OUTPUT_DIR;
    }

    /**
     * Set the output directory where files will be stored.
     */
    public function setOutputDir(string $outputDir): self
    {
        $this->outputDir = $outputDir;

        return $this;
    }
}
