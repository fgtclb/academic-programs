<?php

declare(strict_types=1);

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;

defined('TYPO3') or die();

// The three ways a site package switches the rich text editor of a program field:
// for the program page type only, for every page type, and for every page type but
// on again for the program page type.
$GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACADEMIC_PROGRAM]['columnsOverrides']['job_profile']['config']['enableRichtext'] = false;
$GLOBALS['TCA']['pages']['columns']['performance_scope']['config']['enableRichtext'] = false;
$GLOBALS['TCA']['pages']['columns']['prerequisites']['config']['enableRichtext'] = false;
$GLOBALS['TCA']['pages']['types'][PageTypes::TYPE_ACADEMIC_PROGRAM]['columnsOverrides']['prerequisites']['config']['enableRichtext'] = true;
