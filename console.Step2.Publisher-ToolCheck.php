<?php
/*
	TASK/DESCRIPTION:
	Will try to download PDFs form a given/hard-coded set of web pages.
	Corresponding support must be available in pdf_dl_lib/publisher-handling
	for each website/publisher

	EXECUTION (bash command):
	php console.Step2.Publisher-ToolCheck.php

*/
// ========================================================================
// function + libraries:

if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_downloader.php'); // for PDFdownloader
include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_hamster.php'); // for extending PDFhamster

define('PATH_DL_TEST', PATH_INSTALL . 'pdf_dl_test/' );

error_reporting(E_ERROR | E_PARSE);

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
	
	Springer:
	https://link.springer.com/article/10.1007/s00027-011-0183-x

*/

$pxAryOFF = array(	/* as debug-reference, if PDFHamster class basically is working */
	'lib4ri' => 'https://www.dora.lib4ri.ch/eawag/islandora/object/eawag:14327',
	'fraho' => 'http://www.fraho.ch/job/publisher-test.html',
);
$pxAry = array(	/* real case expamples */
	'iop' => 'https://iopscience.iop.org/article/10.1088/1748-9326/6/3/034004',
	'aip' => 'https://pubs.aip.org/aip/pof/article/22/5/054104/257544/Identifying-unstable-modes-in-stratified-shear',
	'rsc' => 'https://pubs.rsc.org/en/content/articlelanding/2010/EM/c002020f',
	'wiley' => 'https://bsssjournals.onlinelibrary.wiley.com/doi/10.1111/j.1365-2389.2006.00837.x',
	'springer' => 'https://link.springer.com/article/10.1007/s00027-011-0183-x',
	'sciencedirect' => 'https://www.sciencedirect.com/science/article/pii/S0304380005004965',
	'tandfonline' => 'https://www.tandfonline.com/doi/full/10.1080/09603123.2010.550036',
	'acs' => 'https://pubs.acs.org/doi/10.1021/es200743t',
);

$handleAry = array(	/* which object class to use */
	'PdfDownloader' => array(
		'acs',
		'tandfonline',
		'wiley',
		'sciencedirect' /* 'elsevier' */,
	),
	'PdfHamster' => array(
		/* by default this will be used, however if both classes should be used, it must be specified in both sub-arrays */
		'wiley',
		'sciencedirect' /* 'elsevier' */,
	),
);

$toolAry = array(
	'Wget',
	'cUrl',
	'PHP',
);


foreach( $toolAry as $tool ) {

	foreach( $pxAry as $pxIdx => $pxUrl ) {
		$fileBase = PATH_DL_TEST . $pxIdx . '.';
		$pxIdx = strtolower($pxIdx);
		
		$objClass = 'PdfDownloader';
		if ( in_array($pxIdx,$handleAry[$objClass]) ) {

			$doi = $argv[1];
			$pdf_fname = $argv[2];
			
			// temp. overrides:
			$doi = substr(strchr($pxUrl,'/10.'),1);
			$pdf_name = $fileBase . $objClass. '.' . $tool . '.pdf';

			$down = new $objClass();

			// $down->setDoi($doi)->fetchHtml();
			// $down->locatePdfUrl();
			// $pdf = $down->returnPdf($doi);

			$down->setDoi($doi)->fetchHtml()->locatePdfUrl();

			$down->fetchPdf();
			if ($down->getPdf() == false){
				echo 'PDF not fetched';
			}
			else{
				file_put_contents($pdf_fname, $down->getPdf());
			}

			//echo $down->getHtml();

			// echo $down->getUrl();
			//echo $down->getPdfUrl();			

			$objClass = 'PdfHamster';
			if ( !in_array($pxIdx,$handleAry[$objClass]) ) { continue; }
		}


		$objClass = 'PdfHamster';
		$pdfDL = new $objClass();
	//	echo $pdfDL->randomUserAgent() . "\r\n";
	//	echo $pdfDL->randomName() . "\r\n";

		$pxClass = 'pdf_dl_' . $pxIdx;
		

	//	echo "Class: " . $pxClass . "\r\n" . "File : " . $fileBase . "\r\n\r\n"; continue;

		if ( !class_exists($pxClass) ) { continue; }

		$fetchAry = array(
			'link' => $pxUrl,
			'tool' => $tool,
			'file' => $fileBase . $objClass. '.' . $tool . '.html',
			'pdf' => $fileBase . $objClass. '.' . $tool . '.pdf',
		);
		$pxDl = new $pxClass( $fetchAry );
		$pdfUrl = $pxDl->getPdfUrl();
		$logFile = $fileBase . $objClass. '.' . $tool . '.txt';
		file_put_contents( $logFile, $pxDl->getPdfUrl() );
		
		if ( !empty($pdfUrl) ) {
			echo "\r\nPDF: " . $pdfUrl . "\r\n";
			$dlAry = array();
			$pdfDL->download( $pdfUrl, $fetchAry['pdf'], $dlAry );
		}

		if ( @isset($fetchAry['html']) ) { unset($fetchAry['html']); /* simply too much feedback */ }
		echo print_r($fetchAry,1);

		echo "\r\n\r\n";
		sleep( 5 );
	}

	sleep( 2 );
	echo "\r\n";
}		

?>