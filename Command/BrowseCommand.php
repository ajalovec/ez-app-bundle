<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Api\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Origammi\Bundle\EzAppBundle\Command\Helper\TableHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BrowseCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class BrowseCommand extends ContainerAwareCommand
{
    const DEFAULT_LOCATION_ID = 1;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ContentType[]
     */
    private $loadedContentTypes = [];

    /**
     * @param TableHelper    $table
     * @param InputInterface $input
     *
     * @return void
     */
    abstract protected function configureTable(TableHelper $table, InputInterface $input);

    /**
     * @param Repository      $repository
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     */
    abstract protected function loadData(Repository $repository, InputInterface $input, OutputInterface $output);


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $table = new TableHelper(TableHelper::STYLE_SIMPLE);
        $this->configureTable($table, $input);

        $rows = $this->loadData($this->getRepository(), $input, $output);
        foreach ($rows as $k => $row) {
            $this->renderRow($table, $row);
        }

        $table->render($output);
    }

    /**
     * @param TableHelper $table
     * @param mixed       $data
     */
    protected function renderRow(TableHelper $table, $data)
    {
        $table->addRow($data);
    }

    /**
     * @param     $locationId
     * @param int $maxDepth
     *
     * @return array
     */
    final protected function loadLocationChildren($locationId, $maxDepth = 0)
    {
        try {
            $location = $this->getRepository()->getLocationService()->loadLocation($locationId);

            return $this->loadLocationsRecursive($location, $maxDepth);
        } catch (NotFoundException $e) {
            $this->output->writeln("<error>No location found with id $locationId</error>");
        } catch (UnauthorizedException $e) {
            $this->output->writeln("<error>Anonymous users are not allowed to read location with id $locationId</error>");
        }
    }

    /**
     * @param Location $location
     * @param          $maxDepth
     * @param int      $level
     *
     * @return array
     */
    private function loadLocationsRecursive(Location $location, $maxDepth, $level = 0)
    {
        $result   = [];
        $newDepth = $level;

        if ($location->depth > 0) {
            $newDepth++;

            $result[] = compact('location', 'level');
        }

        if (!$maxDepth || $level < $maxDepth) {
            $childLocations = $this->getRepository()->getLocationService()->loadLocationChildren($location, 0, 100);

            foreach ($childLocations->locations as $childLocation) {
                $result = array_merge($result, $this->loadLocationsRecursive($childLocation, $maxDepth, $newDepth));
            }
        }

        return $result;
    }

    /**
     * @return \eZ\Publish\Core\SignalSlot\Repository|object
     */
    final protected function getRepository()
    {
        return $this->getContainer()->get('ezpublish.api.repository');
    }

    /**
     * @param Location $location
     *
     * @return string
     */
    final protected function generateUrl(Location $location)
    {
        return $this->getContainer()->get('router')->generate('ez_urlalias', ['locationId' => $location->id]);
    }

    /**
     * @param Location $location
     *
     * @return ContentType
     */
    final protected function loadContentType(Location $location)
    {
        $id = $location->contentInfo->contentTypeId;

        if (isset($this->loadedContentTypes[$id])) {
            return $this->loadedContentTypes[$id];
        }

        return $this->loadedContentTypes[$id] = $this->getRepository()->getContentTypeService()->loadContentType($id);
    }

    /**
     * @return int
     */
    final protected function getSiteaccessRootLocationId()
    {
        return (int)$this->getContainer()->get('ezpublish.config.resolver')
            ->getParameter('content.tree_root.location_id')
            ;
    }
}
