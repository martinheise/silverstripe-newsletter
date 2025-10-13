<?php

namespace Mhe\Newsletter\Forms;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\GridField\GridFieldExportButton;

/**
 * GridFieldExportButton with some enhancements
 * - configurable export file name
 */
class GridFieldEnhancedExportButton extends GridFieldExportButton
{
    protected string $exportNamePrefix = 'export-';

    public function __construct($targetFragment = "after", $exportColumns = null)
    {
        parent::__construct($targetFragment, $exportColumns);
    }

    public function handleExport($gridField, $request = null): ?HTTPResponse
    {
        $now = date("d-m-Y-H-i");
        $fileName = "$this->exportNamePrefix$now.csv";

        if ($fileData = $this->generateExportFileData($gridField)) {
            return HTTPRequest::send_file($fileData, $fileName, 'text/csv');
        }
        return null;
    }

    public function getExportNamePrefix(): string
    {
        return $this->exportNamePrefix;
    }

    public function setExportNamePrefix(string $exportNamePrefix): void
    {
        $this->exportNamePrefix = $exportNamePrefix;
    }
}
