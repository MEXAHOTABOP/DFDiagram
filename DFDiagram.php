<?php
/*
 * DFDiagram
 * MediaWiki extension for including Dwarf Fortress diagrams
 *
 * This is the file that should be included by LocalSettings.php
 */
if (!isset($wgDFDConfigFile)) {
	$wgDFDConfigFile = "$IP/extensions/DFDiagram/diagram_config.txt";
}
if (!isset($wgDFDDefaultDiagramPath)) {
	$wgDFDDefaultDiagramPath = "$IP/extensions/DFDiagram/default_diagram.txt";
}

require_once 'Diagram.php';

$DFDFile = new DFDBlockFile($wgDFDConfigFile);

/*
 * Add hooks
 */

$wgHooks['ParserFirstCallInit'][] = 'DFDMWHooks::init';
$wgResourceModules['ext.DFDiagram'] = array(
	'styles' => "modules/ext.DFDiagram.css",
	'scripts' => array(
		'modules/ext.DFDiagram.js',
		'modules/df-tileset/df-tileset.js',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'DFDiagram'
);

$wgHooks['BeforePageDisplay'][] = 'DFDMWHooks::includeModules';

/*
 * Credits for Special:Version
 */

$wgExtensionCredits['DFDiagram'][] = array(
	'path' => __FILE__,
	'name' => 'DFDiagram',
	'author' =>'Lethosor',
	'url' => 'https://github.com/lethosor/DFDiagram',
	'description' => 'Adds support for DF-style diagrams',
	'version'  => '0.4',
);

