<?php

namespace Mhe\Newsletter\Forms;

use SilverStripe\Forms\GridField\AbstractGridFieldComponent;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\ORM\Filterable;
use SilverStripe\ORM\SS_List;

/**
 * Filters GridField data with fixed conditions
 */
class GridFieldFixedFilter extends AbstractGridFieldComponent implements GridField_DataManipulator
{
    public function __construct(public array $filterCriteria)
    {
    }

    protected function checkDataType($dataList): bool
    {
        return ($dataList instanceof Filterable);
    }

    /**
     * filter list with given criteria
     */
    public function getManipulatedData(GridField $gridField, SS_List $dataList): SS_List
    {
        if (!$this->checkDataType($dataList)) {
            return $dataList;
        }
        if (empty($this->filterCriteria)) {
            return $dataList;
        }
        return $dataList->filter($this->filterCriteria);
    }
}
