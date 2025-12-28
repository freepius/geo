<?php

namespace App\Command;

use App\Service\GeoParcelsProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'geo:compute-parcels',
    description: 'Compute and print some data from a parcels geojson file.',
)]
class GeoComputeParcels extends Command
{
    public function __construct(
        protected GeoParcelsProcessor $processor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'geojson',
                InputArgument::REQUIRED,
                'geojson file containing parcels'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->processor
            ->process($input->getArgument('geojson'))
            ->printSurfaceByType($io)
        ;

        return Command::SUCCESS;
    }
}
