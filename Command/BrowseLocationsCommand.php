<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use eZ\Publish\API\Repository\Values\Content\Location;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This commands walks through a subtree and prints out the content names.
 */
class BrowseLocationsCommand extends ContainerAwareCommand
{
    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    private $locationService;
    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    private $contentService;

    /**
     * This method override configures on input argument for the content id
     */
    protected function configure()
    {
        $this
            ->setName('origammi:ez:browse_locations')
            ->addArgument('locationId', InputArgument::OPTIONAL, 'Location ID to browse from')
            ->addOption('show-content-type', null, InputOption::VALUE_NONE, 'Show content types for each location')
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Set max depth for recursion.')
        ;
    }

    /**
     * Prints out the location name, and recursively calls itself on each its children
     *
     * @param array                                              $data
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param OutputInterface                                    $output
     * @param InputInterface                                     $input
     * @param int                                                $depth The current depth
     */
    private function browseLocation(array &$data, Location $location, OutputInterface $output, InputInterface $input, $depth = 0)
    {
        $contentType = $this->getContainer()->get('origammi_ezapp.api.content_type')->load($location->contentInfo->contentTypeId);

        // indent according to depth and write out the name of the content
        $name = sprintf('%s %s', str_pad('', $depth), $location->contentInfo->name);

        $d = [
            $location->id,
            $name,
        ];

        if ($input->getOption('show-content-type')) {
            $d[] = $contentType->identifier;
        }

        $data[] = $d;
        $maxDepth = (int)$input->getOption('depth');

        if ($maxDepth && $depth >= $maxDepth) {
            return;
        }
        // we request the location's children using the location service, and call browseLocation on each
        $childLocations = $this->locationService->loadLocationChildren($location);
        foreach ($childLocations->locations as $childLocation) {
            $this->browseLocation($data, $childLocation, $output, $input, $depth + 1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $repository \eZ\Publish\API\Repository\Repository */
        $repository            = $this->getContainer()->get('ezpublish.api.repository');
        $this->contentService  = $repository->getContentService();
        $this->locationService = $repository->getLocationService();
        // fetch the input argument
        $locationId = $input->getArgument('locationId') ?: $this->getContainer()->get('ezpublish.config.resolver')->getParameter('content.tree_root.location_id');


        $table = $this->getHelperSet()->get('table');
        $data = [];
        $headers = ['Id', 'Name'];

        if ($input->getOption('show-content-type')) {
            $headers[] = 'ContentType';
        }


        try {
            // load the starting location and browse
            $location = $this->locationService->loadLocation($locationId);
            $this->browseLocation($data, $location, $output, $input);
            $table
                ->setHeaders($headers)
                ->setRows($data)
                ->render($output)
            ;

        } catch (\eZ\Publish\API\Repository\Exceptions\NotFoundException $e) {
            $output->writeln("<error>No location found with id $locationId</error>");
        } catch (\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e) {
            $output->writeln("<error>Anonymous users are not allowed to read location with id $locationId</error>");
        }
    }
}
