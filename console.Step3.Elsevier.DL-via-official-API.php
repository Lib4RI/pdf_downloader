<?php
/*
	TASK/DESCRIPTION:
	Recently ingested Empa reports need the new publication type called 'Magazine Article'
	Plan is to replace completely the entire MODS, since there are as good as no meta-data

	EXECUTION (bash command):
	php console.Step3.Elsevier.DL-via-official-API.php
	
	OBSERVATION/RESULT:
	Successful for OA publications/PDFs, should also work soon with non-OA since 'entitled'.
	Dimitris is already in touch with Elsevier - 09-Nov-2023

*/
// ========================================================================
// function + libraries:

if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_downloader.php'); // for PDFdownloader
include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_hamster.php'); // for extending PDFhamster

define('PATH_DL_TEST', PATH_INSTALL . 'pdf_dl_test/' );


// Command line-Test:
// wget -U "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36" --header="Accept-Language: en-US,en;q=0.5" "https://api.elsevier.com/content/article/doi/10.1016/j.scitotenv.2023.166767?APIKey=f3e6378c22cd0a118af789071b0ea6d7&httpAccept=application/pdf " -O "10.1016_j.scitotenv.2023.166767.pdf"


// $doi = '10.1016/j.ecolmodel.2005.10.039';	// not OA - if not authorized this will end up with a 1-page-PDF, see Warning in HTTP-response!
$doi = '10.1016_j.scitotenv.2023.166767';	// OA - full PDF!

$tool = 'cUrl';		// DL-tool to use, as tested this also works with Wget and PHP



$queryAry = array(
	'APIKey' => 'f3e6378c22cd0a118af789071b0ea6d7',
	'httpAccept' => 'application/pdf',
);
$url = 'https://api.elsevier.com/content/article/doi/' . $doi . '?' . http_build_query($queryAry,'','&',PHP_QUERY_RFC3986);

$fetchAry = array(
	'tool' => $tool,
	'file' => PATH_DL_TEST . $tool . '.Elsevier.' . strtr($doi,'/','_') . '.pdf',
	'info' => [],	/* empty but set to be enrichted with feedback from cUrl/Wget/PHP */
	'verbose' => 0,
);

$pdfDL = new PdfHamster();
$result = $pdfDL->fetchUrl( $url, $fetchAry ); // REMIND: $fetchAry will be enriched with data (it is linked as reference/pointer)

echo "\r\nFeedback from Tool '" . $tool . ' - ' . print_r($fetchAry,1);
echo ( $result ? "...OK!" : "...bad!?" ) . "\r\n";

?>