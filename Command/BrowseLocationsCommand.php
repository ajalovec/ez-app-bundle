<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use Origammi\Bundle\EzAppBundle\Command\Helper\TableHelper;
use Origammi\Bundle\EzAppBundle\Command\Helper\TableRowData;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This commands walks through a subtree and prints out the content names.
 */
class BrowseLocationsCommand extends BrowseCommand
{
    /**
     * This method override configures on input argument for the content id
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('origammi:ez:browse:locations')
            ->addArgument('locationId', InputArgument::OPTIONAL, 'Location ID to browse from')
            ->addOption('depth', 'd', InputOption::VALUE_OPTIONAL, 'Set max depth for recursion.')
            ->addOption('content', '-c', InputOption::VALUE_NONE, 'Show content information for each location')
        ;
    }

    public function getLocationFormat(TableRowData $row, $id, $index)
    {
        if ($row['id'] == $this->getSiteaccessRootLocationId()) {
            $format = '<fg=green;options=underscore>%s</>';
        } elseif ($row['depth'] == 1) {
            $format = '<fg=green>%s</>';
        } else {
            $format = '<fg=white>%s</>';
        }

        return sprintf($format, $row->get($id));
    }

    protected function configureTable(TableHelper $table, InputInterface $input)
    {
        $showContentId = (bool)$input->getOption('content');

        $table->addColumn('contentType', 'Content type')
            ->setAlign(TableHelper::ALIGN_RIGHT)
            ->setValueResolver([$this, 'getLocationFormat'])
        ;

        $table->addColumn('id')
            ->setAlign(TableHelper::ALIGN_CENTER)
            ->setValueResolver(function (TableRowData $data, $id, $index) use ($showContentId) {
                $format = '<fg=white>%s</>';

                if ($data['invisible']) {
                    $format = '<fg=red;options=bold>%s</>';
                } elseif ($data['id'] == $this->getSiteaccessRootLocationId()) {
                    $format = '<fg=green;options=underscore>%s</>';
                } elseif ($data['depth'] == 1) {
                    $format = '<fg=green>%s</>';
                }

                return sprintf($format, $data->get($showContentId ? 'contentId' : $id));
            })
        ;

        $table->addColumn('name')
            ->setValueResolver(function (TableRowData $data, $id, $index) {
                $value = $this->getLocationFormat($data, $id, $index);

                if ($data['invisible']) {
                    $value = sprintf('%s <fg=red;options=bold>[ h ]</>', $value);
                }

                return str_repeat('  ', $data['level']) . $value;
            })
        ;

//        $table->addColumn('path');
//        $table->addColumn('remoteId');

    }

    protected function loadData(Repository $repository, InputInterface $input, OutputInterface $output)
    {
        return $this->loadLocationChildren(
            $input->getArgument('locationId') ?: self::DEFAULT_LOCATION_ID,
            abs($input->getOption('depth'))
        );
    }

    protected function renderRow(TableHelper $table, $row)
    {
        /** @var Location $location */
        $location = $row['location'];

        $row['depth']       = $location->depth;
        $row['id']          = $location->id;
        $row['path']        = $this->generateUrl($location);
        $row['contentId']   = $location->contentId;
        $row['name']        = $location->contentInfo->name;
        $row['contentType'] = $this->loadContentType($location)->identifier;
        $row['invisible']   = $location->invisible;
        $row['remoteId']    = $location->remoteId;

        if ($location->depth == 1) {
            $table->addSeparator();
        }

        $table->addRow($row);
    }

}
