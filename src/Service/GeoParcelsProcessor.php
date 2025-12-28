<?php

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Compute and print some data from a parcels geojson file.
 *
 * @todo **This code does not work!**
 * We have to find now: how to get the type of a parcel?
 * Because, in the geojson file from `cadastre.data.gouv.fr`, there is no type.
 */
class GeoParcelsProcessor
{
    protected const PARCEL_TYPES = [
        'BR' => 'Futaie résineuse',
        'BT' => 'Tailli simple',
        'L'  => 'Lande',
        'T'  => 'Terre',
        'P'  => 'Pré',
        'S'  => 'Sol',
    ];

    /**
     * Total surface by parcel type.
     */
    protected array $surfaceByType = [];

    public function process(string $geojsonFilePath): self
    {
        $geojson = json_decode(
            file_get_contents($geojsonFilePath)
        );

        $parcels = $geojson->features;

        foreach ($parcels as $parcel) {
            $surface = & $this->surfaceByType[$parcel->properties->type];
            $surface += $parcel->properties->contenance;
        }

        return $this;
    }

    public function printSurfaceByType(OutputInterface $out): self
    {
        $out->writeln('Surface by parcel type:');

        foreach ($this->surfaceByType as $type => $surface) {
            $out->writeln(
                sprintf(
                    "\t* %s = %.2f ha",
                    static::PARCEL_TYPES[$type] ?? $type,
                    $surface / 10000
                )
            );
        }

        $out->writeln(
            sprintf(
                "TOTAL = %.2f ha",
                array_sum($this->surfaceByType) / 10000
            )
        );

        return $this;
    }
}
