<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('bixie_legend', 'sitemap_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('samlSPEnabled', 'bixie_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('rootfallback', 'tl_page')
;

$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'samlSPEnabled';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['samlSPEnabled'] = 'samlSPCertificate,samlSPPrivateKey,samlIdPMetadata';

$GLOBALS['TL_DCA']['tl_page']['fields']['samlSPEnabled'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['samlSPCertificate'] = [
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => ['mandatory' => true, 'filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'crt'],
    'sql' => "binary(16) NULL",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['samlSPPrivateKey'] = [
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => ['mandatory' => true, 'filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'pem'],
    'sql' => "binary(16) NULL",
];

$GLOBALS['TL_DCA']['tl_page']['fields']['samlIdPMetadata'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['mandatory' => true, 'useRawRequestData' => true, 'class' => 'noresize'],
    'sql' => 'longtext NULL',
];
