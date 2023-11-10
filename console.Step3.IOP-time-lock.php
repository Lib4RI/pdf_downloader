<?php
/*
	TASK/DESCRIPTION:
	Recently ingested Empa reports need the new publication type called 'Magazine Article'
	Plan is to replace completely the entire MODS, since there are as good as no meta-data

	EXECUTION (bash command):
	php console.Step3.IOP-time-lock.php
	
	OBSERVATION/RESULT:
	DOI gets resolved + we get Publishers homepage for that publication, there it's possible
	to grab the PDF link, if downloaded *automatically* with cUrl/Wget/PHP it will be HTML code.

*/
// ========================================================================
// function + libraries:

if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_downloader.php'); // for PDFdownloader
include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_hamster.php'); // for extending PDFhamster

define('PATH_DL_TEST', PATH_INSTALL . 'pdf_dl_test/' );

/*
	TEST:
	http://www.fraho.ch/job/publisher-test.html
	WILEY - example page:
	https://bsssjournals.onlinelibrary.wiley.com/doi/10.1111/j.1365-2389.2006.00837.x
	American Institute of Physics - example page:
	https://pubs.aip.org/aip/pof/article/22/5/054104/257544/Identifying-unstable-modes-in-stratified-shear
	IOP Publishing - example page:
	https://iopscience.iop.org/article/10.1088/1748-9326/6/3/034004
	RSC / Royal Society of Chemistry - example page:
	https://pubs.rsc.org/en/content/articlelanding/2010/EM/c002020f

*/

$pxAry = array(
/*
	'lib4ri' => 'https://www.dora.lib4ri.ch/eawag/islandora/object/eawag:14327',
	'fraho' => 'http://www.fraho.ch/job/publisher-test.html',
*/

	'iop' => 'https://iopscience.iop.org/article/10.1088/1748-9326/6/3/034004',
/*
	'aip' => 'https://pubs.aip.org/aip/pof/article/22/5/054104/257544/Identifying-unstable-modes-in-stratified-shear',
	'rsc' => 'https://pubs.rsc.org/en/content/articlelanding/2010/EM/c002020f',
	'wiley' => 'https://bsssjournals.onlinelibrary.wiley.com/doi/10.1111/j.1365-2389.2006.00837.x',
*/

);

$toolAry = array(
	'Wget',
	'cUrl',
	'PHP',
);

foreach( $toolAry as $tool ) {

	foreach( $pxAry as $pxIdx => $pxUrl ) {
		$fileBase = PATH_DL_TEST . $tool . '.' . $pxIdx;
		
		$pdfDL = new PdfHamster();
	//	echo $pdfDL->randomUserAgent() . "\r\n";
	//	echo $pdfDL->randomName() . "\r\n";

		$class = 'pdf_dl_' . strtolower($pxIdx);

	//	echo "Class: " . $class . "\r\n" . "File : " . $fileBase . "\r\n\r\n"; continue;

		if ( !class_exists($class) ) { continue; }

		$fetchAry = array(
			'link' => $pxUrl,
			'tool' => $tool,
			'file' => $fileBase . '.html',
			'pdf' => $fileBase . '.pdf',
		);
		$pxDl = new $class( $fetchAry );
		$pdfUrl = $pxDl->getPdfUrl();
		file_put_contents( $fileBase.'.txt', $pxDl->getPdfUrl() );
		
		if ( !empty($pdfUrl) ) {
			echo "\r\nPDF to download: " . $pdfUrl . "\r\n";
			$dlAry = array();
			$pdfDL->download( $pdfUrl, $fetchAry['pdf'], $dlAry );
		}

		if ( @isset($fetchAry['html']) ) { unset($fetchAry['html']); /* simply too much feedback */ }
		echo print_r($fetchAry,1);

	//	break;
		echo "\r\n\r\n";
		sleep( 5 );
	}

//	break;
	sleep( 2 );
	echo "\r\n";
}		

// TO DOWNLOAD: https://pubs.rsc.org/en/content/articlepdf/2010/em/c002020f



?>