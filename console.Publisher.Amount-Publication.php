<?php

/*
	TASK/DESCRIPTION:
	Recently ingested Empa reports need the new publication type called 'Magazine Article'
	Plan is to replace completely the entire MODS, since there are as good as no meta-data

	EXECUTION (bash command):
	php console.Publisher.Amount-Publication.php
	
	OBSERVATION/RESULT:
	Tasks (resp. expected state of cover page) and publication amount of publishers:
		[unCovered] => Array
			(
				[Elsevier] => 1698
				[American Chemical Society] => 468
				[Springer Nature] => 565
				[Taylor & Francis] => 163
				[Copernicus] => 126
			)
		[toDeCover] => Array
			(
				[Royal Society of Chemistry] => 114
				[Wiley] => 772
				[SPIE] => 6
				[American Institute of Physics] => 127
				[IOP Publishing] => 113
			)

*/
// ========================================================================
// function + libraries:

if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_downloader.php'); // for PDFdownloader
include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_hamster.php'); // for extending PDFhamster

define('PATH_DL_DONE', PATH_INSTALL . 'pdf_dl_done/' );

error_reporting(E_ERROR | E_PARSE);

$_pdf_counting = true;
$_cache_age_max = 3600;		// there is also a max introduced by having a timestamp in the file name!



function doraMakeStringKeyValue( $kvAry = [], $sSep = ': ' )
{
	$sAry = [];
	foreach( $kvAry as $key => $val ) {
		$sAry[] = @strval($key) . $sSep . @strval($val);
	}
	return $sAry;
}

function doraMakeFirefoxVersion()	// rather doraFakeFirefox... - this is total guess, however optimized/re-sync'ed: 2023-09-27
{
	$vApprox = ( ( time() >> 22 ) - 287 );	// guessing/reflecting (security) updates, >>21 - 691 could be ok too!?
	$vEngine = min( $vApprox, 109 );	// no further development of the enigine!(?)
	return ('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:' . $vEngine . '.0) Gecko/20100101 Firefox/' . $vApprox . '.0');
}

function doraMakeApiQueryHeader($method = '', $headerDataAry = [] )
{
	$httpData = array(		/* just to pretend human browser behavior */
		'http' => array(
			'header' => array(	/* defaults, may be overwitten */
				'User-Agent'      => /* 'Mozilla/5.0 (Lib4RI IT Services)' */ doraMakeFirefoxVersion(),
				'Accept-Language' => 'en-us,en;q=0.5',
				'Connection'      => 'close',
			),
			'method' => ( empty($method) ? 'GET' : $method ),
		),
	);
	foreach( $headerDataAry as $key => $val ) {
		$httpData['http']['header'][$key] = $val;
	}
	return $httpData;
}

function doraMakeCurlQuery($apiUrl, $timeout = 60, $headerDataAry, &$responseAry = null )
{
	$headerAry = doraMakeApiQueryHeader( 'GET', $headerDataAry );

	$cUrl = curl_init();
	curl_setopt($cUrl, CURLOPT_URL, $apiUrl );
	curl_setopt($cUrl, CURLOPT_HTTPHEADER, doraMakeStringKeyValue($headerAry['http']['header']) );
	if ( !empty($headerAry['http']['header']['User-Agent']) ) {
		curl_setopt($cUrl, CURLOPT_USERAGENT, $headerAry['http']['header']['User-Agent']);	// explicitly set it (again)
	}
	curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt($cUrl, CURLOPT_TIMEOUT, $timeout );
	curl_setopt($cUrl, CURLOPT_CONNECTTIMEOUT, ( ( $timeout + 1 ) >> 1 ) );
	if ( ( $jsonCode = curl_exec($cUrl) ) && @is_array($responseAry) )
	{
		$responseAry = curl_getinfo($cUrl);
	}
	curl_close($cUrl);
	/*
	if ( @is_array($http_response_header) && sizeof($http_response_header) ) {
		$resp0 = preg_replace('/\s+/','_',trim($http_response_header[0]));
		if ( stripos($resp0,'HTTP') === false || ( !strpos($resp0.'_','_200_') && !strpos($resp0.'_','_OK_') ) ) {
			// an error happened. Let's merge the real response with the http response:
			return json_encode( array(
					'query_status' => 'error',
					'query_header' => $this->makeApiQueryHeader(),
					'query_url' => $apiUrl,
					'json_data_from_api' => json_decode($jsonCode),
					'http_response_header' => $http_response_header,
				),
				JSON_PRETTY_PRINT );
		}
	}
	*/
	return $jsonCode;
}


$infoCurlAry = array();

function queryScopus( $id, $pretty = true ) { // id: '82755170946' or '2-s2.0-82755170946'
	$headerAry = array(
		'httpAccept' => 'application/json',
		'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0',
	);
	$link = 'https://api.elsevier.com/content/search/scopus?';
	$link .= 'APIKey=cb39991641e23177261e0f46318df89d&field=doi&query=SCOPUS-ID(' . substr(strrchr('-'.$id,'-'),1) . ')';

	$jsonCode = doraMakeCurlQuery( $link, 60, $headerAry, $infoCurlAry );
	return ( $pretty ? json_encode( json_decode( $jsonCode ), JSON_PRETTY_PRINT ) : $jsonCode );
}


function queryWoS( $id, $pretty = true ) {	// id: '000225079300029' or 'WOS:000225079300029'
	$headerAry = array(
		'X-ApiKey' => 'c78447255a00164abe4b203bb304be1a6a5263a6',
		'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0',
	);
	$link = 'https://wos-api.clarivate.com/api/woslite/?';
	$link .= 'databaseId=WOS&usrQuery=UT%3d(WOS:' . substr(strrchr(':'.$id,':'),1) . ')j&count=10&firstRecord=1';

	$jsonCode = doraMakeCurlQuery( $link, 60, $headerAry, $infoCurlAry );
	return ( $pretty ? json_encode( json_decode( $jsonCode ), JSON_PRETTY_PRINT ) : $jsonCode );
}

function doraPageSum( $pubAry ) {
	// see: https://www.wiki.lib4ri.ch/display/TD/Pages + https://www.wiki.lib4ri.ch/pages/viewpage.action?pageId=21889177
	$ary = array( @strval($pubAry['mods_part_extent_start_mt'][0]), @strval($pubAry['mods_relatedItem_host_part_extent_start_mt'][0]) );
	$ary = array_map( function($p) { return ( strpos($p,'pp') ? substr(strrchr('('.$p,'('),1) : '0' ); }, $ary );
	if ( $sum = max(intval($ary[0]),intval($ary[1])) ) {
		return ( $sum );		// pp indicates the right amount
	}
	if ( $sum = max( @intval($pubAry['mods_part_extent_end_mt'][0]) - @intval($pubAry['mods_part_extent_start_mt'][0]), 0 ) ) {
		return ( $sum + 1 );		// page range 25-27 would be the 3 pages (= diffrence + 1)
	}
	if ( $sum = max( @intval($pubAry['mods_relatedItem_host_part_extent_end_mt'][0]) - @intval($pubAry['mods_relatedItem_host_part_extent_start_mt'][0]), 0 ) ) {
		return ( $sum + 1 );		// page range 25-27 would be the 3 pages (= diffrence + 1)
	}
	return 0;
}
	

/*
// TEST Scopus:
$jsonCode = queryScopus( '2-s2.0-82755170946') . "\r\n";
echo "\r\n" . $jsonCode . "\r\n" . print_r($infoCurlAry,1) . "\r\n"; return;
// TEST WoS:
$jsonCode = queryWoS( '000225079300029') . "\r\n";
echo "\r\n" . $jsonCode . "\r\n" . print_r($infoCurlAry,1) . "\r\n"; return;
*/

// +AND+(fedora_datastream_info_PDF_ID_mt:*+OR+fedora_datastream_info_PDF2_ID_mt:*)
// mods_originInfo_publisher_mt
// mods_relatedItem_host_originInfo_publisher_mt

/*

(ohne Coverpage)
	https://www.dora.lib4ri.ch/islandora/search?islandora_solr_search_navigation=1
	+AND+mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2006-01-01T00:00:00Z%20TO%202012-01-01T00:00:00Z]
	+NOT+RELS_EXT_isMemberOfCollection_uri_ms:("info%5c%3afedora%5C/psi%5c%3apublications"
		+OR+"info%5c%3afedora%5C/psi%5c%3aexternal"
		+OR+"info%5c%3afedora%5C/wsl%5c%3apublications"
		+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-int"
		+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-ext"
		+OR+"info%5c%3afedora%5C/eawag%5c%3aext"
		+OR+"info%5c%3afedora%5C/wsl%5c%3aforum")
	+AND+mods_relatedItem_host_originInfo_publisher_s:("Elsevier"
		+OR+"Springer%20Nature"
		+OR+"American%20Chemical%20Society"
		+OR+"Taylor%5C%20%26%5C%20Francis"
		+OR+"Copernicus"
		+OR+"Springer")
	+AND+mods_genre_ms:"Journal%5C%20Article" 


(mit Coverpage)
	https://www.dora.lib4ri.ch/islandora/search?islandora_solr_search_navigation=1
	+AND+mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2006-01-01T00:00:00Z%20TO%202012-01-01T00:00:00Z]
	+NOT+RELS_EXT_isMemberOfCollection_uri_ms:("info%5c%3afedora%5C/psi%5c%3apublications"
		+OR+"info%5c%3afedora%5C/psi%5c%3aexternal"
		+OR+"info%5c%3afedora%5C/wsl%5c%3apublications"
		+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-int"
		+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-ext"
		+OR+"info%5c%3afedora%5C/eawag%5c%3aext"
		+OR+"info%5c%3afedora%5C/wsl%5c%3aforum")
	+AND+mods_relatedItem_host_originInfo_publisher_s:("Wiley"
		+OR+"IOP%20Publishing"
		+OR+"American%20Institute%20of%20Physics"
		+OR+"SPIE"
		+OR+"Royal%20Society%20of%20Chemistry")
	+AND+mods_genre_ms:"Journal%5C%20Article"

*/

$solrLinkData = 'PID%2c+mods_identifier_doi_mt%2c+mods_identifier_scopus_mt%2c+mods_identifier_ut_mt%2c+mods_identifier_issn_mt%2c+mods_identifier_e-issn_mt%2c+RELS_EXT_fullText_literal_mt%2c+mods_relatedItem_host_originInfo_publisher_mt%2c+mods_originInfo_publisher_mt%2c+mods_part_extent_start_mt%2c+mods_part_extent_end_mt%2c+mods_relatedItem_host_part_extent_start_mt%2c+mods_relatedItem_host_part_extent_end_mt%2c+RELS_EXT_isMemberOfCollection_uri_mt%2c+mods_genre_ms%2c+mods_note_additional?information_mt';
$solrLinkBase = 'http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?wt=csv&indent=true&csv.separator=|&sort=PID+asc&rows=987654321&q=PID:*+AND+mods_genre_ms:"Journal%5C%20Article"+AND+mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2006-01-01T00:00:00Z%20TO%202012-01-01T00:00:00Z]+NOT+RELS_EXT_isMemberOfCollection_uri_ms:("info%5c%3afedora%5C/psi%5c%3apublications"+OR+"info%5c%3afedora%5C/psi%5c%3aexternal"+OR+"info%5c%3afedora%5C/wsl%5c%3apublications"+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-int"+OR+"info%5c%3afedora%5C/wsl%5c%3awsl%5C-ext"+OR+"info%5c%3afedora%5C/eawag%5c%3aext"+OR+"info%5c%3afedora%5C/wsl%5c%3aforum")&fl=' . $solrLinkData;

$solrLinkAry = array(
	'unCovered' => str_replace('PID:*','mods_relatedItem_host_originInfo_publisher_mt:("Elsevier"+OR+"Springer%20Nature"+OR+"American%20Chemical%20Society"+OR+"Taylor%5C%20%26%5C%20Francis"+OR+"Copernicus"+OR+"Springer")',$solrLinkBase),
	'toDeCover' => str_replace('PID:*','mods_relatedItem_host_originInfo_publisher_mt:("Wiley"+OR+"IOP%20Publishing"+OR+"American%20Institute%20of%20Physics"+OR+"SPIE"+OR+"Royal%20Society%20of%20Chemistry")',$solrLinkBase),
);
// custom filter/requirement will be 'mods_identifier_doi_s' 


if ( $_pdf_counting ) {
	$tmp = '%2c+fedora_datastream_latest_PDF_SIZE_ms%2c+fedora_datastream_latest_PDF2_SIZE_ms%2c+fedora_datastream_latest_PDF3_SIZE_ms%2c+fedora_datastream_latest_PDF4_SIZE_ms%2c+fedora_datastream_latest_PDF5_SIZE_ms%2c+fedora_datastream_latest_PDF6_SIZE_ms%2c+fedora_datastream_latest_PDF7_SIZE_ms%2c+fedora_datastream_latest_PDF8_SIZE_ms%2c+fedora_datastream_latest_PDF9_SIZE_ms';
	foreach( $solrLinkAry as $idx => $link ) { $solrLinkAry[$idx] = $link.$tmp; }
}


$pxFilterAry = array(
	'unCovered' => array(
		'Elsevier',
		'Springer Nature',
		'American Chemical Society',
		'Taylor & Francis',
		'Copernicus',
		'Springer',
	),
	'toDeCover' => array(
		'Wiley',	/*	hard to get REAL PDF link, see https://bsssjournals.onlinelibrary.wiley.com/doi/epdf/10.1111/j.1365-2389.2006.00837.x */
		'IOP Publishing',
		'American Institute of Physics',
		'SPIE',		/*	only 6, done by Sarah @ 2023-10-25		*/
		'Royal Society of Chemistry',
	),
);


foreach( $pxFilterAry as $task => $pxAry ) { // set identical keys:
	$pxFilterAry[$task] = array_combine( array_map('strtolower',$pxAry), $pxAry );
	foreach( $pxAry as $px ) {
		$pxFilterAry['_all_'][strtolower($px)] = $px;	/* for overview/check reasons	*/
	}
}
// die( print_r( $pxFilterAry, 1 ) . "\r\n" );


$pxCountAry = [];
$pxFound = 0;
$pxFoundAry = [];

foreach( $pxFilterAry as $_pdf_task => $pxNameAry ) {
	if ( $_pdf_task == '_all_' ) { continue; }

	$pidAry = array( /* 'typical' data from Solr */ );
	$cacheFileSolr = PATH_DL_DONE . '_dora-pdf-dl.Solr.' . $_pdf_task . '.' . date("Y-m-d-H").'h' . '.json';
	$logFileSolr = PATH_DL_DONE . '_dora-pdf-dl.Solr.' . $_pdf_task . '.' . date("Y-m-d-H").'h' . '.log';

	$doiAry = array( /* pid => doiFound */ );
	$cacheFileDoi = PATH_DL_DONE . '_dora-pdf-dl.DOI.' . $_pdf_task . '.' . 'Results' . '.json';
	$logFileDoi = PATH_DL_DONE . '_dora-pdf-dl.DOI.' . $_pdf_task . '.' . 'Results' . '.log';

	// Get JSON file with DOIs found so far for the PIDs in DORA:
	if ( @filesize($cacheFileDoi) ) {
		$jsonData = @file_get_contents($cacheFileDoi);
		$doiAry = @json_decode( $jsonData, true );
	}


	// Get JSON file from Solr (caching if for the current hour):
	$jsonData = '';
	if ( @filesize($cacheFileSolr) && ( time() - filemtime($cacheFileSolr) < $_cache_age_max ) ) {
		$jsonData = @file_get_contents($cacheFileSolr);
	} else {
		$solrLink = $solrLinkAry[$_pdf_task];
		$solrLink = str_replace('lib-dora-dev1',@strval(exec('hostname')),$solrLink);		// DEV or PROD server?
		$solrLink = str_replace('wt=csv&','wt=json&',$solrLink.'&');  // JSON instead of CSV
		$jsonData = @file_get_contents($solrLink);
		@file_put_contents($cacheFileSolr,$jsonData);
	}
	$pidAry = json_decode( $jsonData, true );
	if ( @intval($pidAry['response']['numFound']) < 1  ) {
		echo '--- Could not find any items!? ---'; exit;
	}
	// echo print_r( array_slice($pidAry['response']['docs'],-1000,200), 1 ) . 'Total ' . $pidAry['response']['numFound'] . "\r\n"; return;

	$domainAry = [];
	$solrFieldAry = [];

	foreach( $pidAry['response']['docs'] as $pubIdx => $pubAry ) {

		$ary = explode(':',$pubAry['PID']);
		if ( intval($ary[0]) || !intval($ary[1]) ) { continue; }
		$pid = $ary[0].':'.rtrim($ary[1]);
	/*
		echo 'https://www.dora.lib4ri.ch/islandora/object/' . $pubAry['PID'] . "\r\n";
		echo print_r( $pubAry, 1 ) . "\r\n";
	*/

		// get Publisher:
		if ( !( $pxByPub = @trim($pubAry['mods_relatedItem_host_originInfo_publisher_mt'][0]) ) ) {
			if ( !( $pxByPub = @trim($pubAry['mods_originInfo_publisher_mt'][0]) ) ) {
				die( "ERROR: No publisher!?\r\n".print_r($pubAry,1)."\r\n" );
			}
		}

		$pxByPub = reset( $pubAry['mods_relatedItem_host_originInfo_publisher_mt'] );
		if ( empty($pxByPub) ) { continue; }
		$oaState = reset($pubAry['RELS_EXT_fullText_literal_mt']);
		$pxCountAry[$_pdf_task][$pxByPub]['_total_'] = @intval($pxCountAry[$_pdf_task][$pxByPub]['_total_']) + 1;
		if ( stripos($oaState,'Open') !== false ) {
			$pxCountAry[$_pdf_task][$pxByPub]['OA'] = @intval($pxCountAry[$_pdf_task][$pxByPub]['OA']) + 1;
		}

		if ( @!isset($pxFilterAry['_all_'][strtolower($pxByPub)]) ) {
			echo( "ERROR: '" . $pxByPub . "' is no *known* publisher!?\r\n".print_r($pubAry,1)."\r\n" );
		}
	/*
		if ( @!isset($pxFilterAry[$_pdf_task][strtolower($pxByPub)]) ) {
			echo( "ERROR: No *task* for '" . $pxByPub . "'!?\r\n".print_r($pubAry,1)."\r\n" );
			continue;
		}
	*/
		if ( @!intval($pubAry['mods_identifier_doi_mt']) ) {
			continue;
			die( "ERROR: No DOI !?\r\n".print_r($pubAry,1)."\r\n" );
		}
		continue; // if counting only, skip here!

	/*
		$pxWanted = 'SPIE';
		$pxWanted = 'Wiley';
		$pxWanted = 'IOP Publishing';
		$pxWanted = 'American Institute of Physics';
		$pxWanted = 'Royal Society of Chemistry';
		$pxWanted = 'Springer';
		$pxWanted = 'Springer Nature';
		$pxWanted = 'Taylor & Francis';
		$pxWanted = 'Elsevier';
		if ( stripos($pxByPub,$pxWanted) !== 0 ) { continue; }
		echo( $pxWanted . ": \r\n".print_r($pubAry,1)."\r\n" );
	*/

		$doi = reset($pubAry['mods_identifier_doi_mt']);
		if ( !intval($doi) && !( $doi = strchr($doi,'10.') ) ) { // should there be a leading http:...
			die( "ERROR: No DOI !\r\n".print_r($pubAry,1)."\r\n" );
		}

		$doraPageSum = doraPageSum($pubAry);
		$urlDora = 'https://www.dora.lib4ri.ch/' . strtok(strtr($pid,'-',':').':',	':') . '/islandora/object/' . $pid;

		$pdfCount = 0;
		if ( $_pdf_counting ) {
			for($p=0;$p<10;$p++) {
				$field = 'fedora_datastream_latest_PDF' . ( $p ? strval($p) : '' ) . '_SIZE_ms';
				if ( @is_array($pubAry[$field]) && @intval($pubAry[$field][0]) ) { $pdfCount++; }
			}
	//		if ( $pdfCount != 1 ) {	echo $pid . " ::: PDFs: " . $pdfCount . " ::: " . $doi . " ::: Page-Range: " . $doraPageSum . "\r\n"; }
		}


		$pblrByPubAry = [];
		foreach( ['mods_relatedItem_host_originInfo_publisher_mt','mods_originInfo_publisher_mt'] as $field ) {
			if ( @!isset($pubAry[$field]) ) { continue; }
			$solrFieldAry[$field] = ( @intval($solrFieldAry[$field]) + 1 );
			foreach( $pubAry[$field] as $px ) { 
				if ( $px = trim($px) ) {
					$pblrByPubAry[$px] = $px;
					$pxFoundAry[$px] = ( @empty($pxFoundAry[$px]) ? $pubAry['PID'] : strval(max(@intval($pxFoundAry[$px]),1) + 1 ).'*' );
				}
			}
		}

		$tmpAry = array(
			PATH_DL_DONE . '_pdfDL',
			$_pdf_task,
			strtr($pid,':','-'),
			strtr($pxByPub,' &','-+'),
			'pdf',
		);
		$pdf_fname = implode('.',$tmpAry);


		/*
		$down = new PdfDownloader();
		$down->setDoi($doi)->fetchHtml()->locatePdfUrl();
		
		$pdfConent = $down->setDoi($doi)->fetchHtml()->locatePdfUrl()->fetchPdf()->getPdf();
		echo "ELEVIER PDF: " . $down->getPdfUrl();
		file_put_contents("./tmp/dl-test.elsevier.pdf",$pdfConent);
		exit;
		*/
	 
		$pdfDL = new /* PdfDownloader() */ PdfHamster();
		$urlDoi = $pdfDL->getDoiResolver() . $doi;


	// http://dx.doi.org/10.1039/c002020f 
	// https://pubs.rsc.org/en/content/articlelanding/2010/EM/c002020f


		$htmlResolved = '';
		$urlResolved = '';
		$fetchAry = array(
			'verbose' => false,
			'timeout' => 30,
			'cookie' => '/tmp/cUrl.' . uniqid() . '.txt',
			'useragent' => $pdfDL->guessUserAgent(),
			'header' => array(),
			'info' => array(),
		);
		if ( stripos($pxByPub,'IOP') === 0 ) { // for IOP we get a Captcha page after some time
			$pxCountAry[$_pdf_task][$pxByPub] = @intval($pxCountAry[$_pdf_task][$pxByPub]) + 1;
			$urlResolved = 'https://iopscience.iop.org/article/' . $doi;		// anticipated
			$urlPdf = 'https://iopscience.iop.org/article/' . $doi . '/pdf';	// anticipated

			$htmlResolved = $pdfDL->fetchUrl( $urlResolved, $fetchAry );
			echo $htmlResolved; exit;
			$tmp = ( strpos($htmlResolved,'citation_pdf_url') ? 'OK' : 'Bad' );
			echo $tmp . ' ::: ' . $urlResolved . "\r\n";
		}
		if ( empty($urlResolved) ) { // default
			$htmlResolved = $pdfDL->fetchUrl( $urlDoi, $fetchAry );
			$urlResolved = $fetchAry['info']['url']; // afterwrds!
		}
	//	echo print_r( $fetchAry, 1 ); return;




	//	$urlPdf = $pdfDL->getPdfUrl();
		$pdfDomain = $pdfDL->getDomain($urlResolved,true);
		
		echo "urlResolved: " . $urlResolved . "\r\n";
		$className = 'pdf_dl_'.$pdfDomain;



		$tmpAry = array( 'link' => $urlResolved, 'html' => $htmlResolved );
		$pdfPub = new $className( $tmpAry );
	/*
		echo $urlResolved . " ---------------- ";
		echo $className . " ---------------- ";
		exit;
	*/

		$pdfUrl = $pdfPub->getPdfUrl();
	//	$pdfUrl = $className::getPdfUrl('html code etc.');
	//	$pdfUrl = 'https://education.github.com/git-cheat-sheet-education.pdf';
	//	https://pubs.rsc.org/en/content/articlepdf/2010/em/c002020f


		echo $pdfUrl . " ---------------- ";
		exit;


		$dirPx = PATH_DL_DONE . strtr($pxByPub,' /','_-') . '/';
		$locFile = str_replace(':','__',$pid) . '.' . strtr(urldecode(basename($pdfUrl)),' /','_-');

		if ( !class_exists($className) ) {
			die( "\r\n" . 'ERROR: Not supported: ' . $pdfDomain . ' / ' . $urlResolved . "\r\n" );
		}
		
		echo "------------ DOI: " . $doi . "\r\n";
		echo "------------ Homepage: " . $urlResolved . "\r\n";
		echo "------------ PDF to DL: " . $urlPdf . "\r\n";

		exit;

		echo "============== "  . $dirPx . "\r\n";
		echo "============== "  . $locFile . "\r\n";

		$dlSize = $pdfDL->download( $pdfUrl, $dirPx.$locFile );

		echo "============== "  . $dlSize . "\r\n";


		$infoAry[] = 'Local:';
		$infoAry[] = ' - File: ' . $locFile;
		$infoAry[] = ' - Size: ' . $dlSize;
		$locFile = str_replace(':','__',$pid) . '.' . 'fetch-info.txt';
		file_put_contents($locFile, print_r($fetchAry,1)."\r\n".implode("\r\n",$infoAry));
		

		/* to get e.g. Array (
			[verbose] => 
			[timeout] => 30
			[cookie] => /tmp/cUrl.6537a97995174.txt
			[useragent] => Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0
			[header] => Array (
				[0] => User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0
				[1] => Accept-Language: en-US,en;q=0.5
			)
			[info] => Array	(
				[url] => https://bsssjournals.onlinelibrary.wiley.com/doi/10.1111/j.1365-2389.2006.00837.x
				[content_type] => text/html; charset=UTF-8
				[http_code] => 403
				[header_size] => 2725
				[request_size] => 654
				[filetime] => -1
				[ssl_verify_result] => 0
				[redirect_count] => 2
				[total_time] => 0.425109
				[namelookup_time] => 0.005741
				[connect_time] => 0.010742
				[pretransfer_time] => 0.039017
				[size_upload] => 0
				[size_download] => 5911
				[speed_download] => 13904
				[speed_upload] => 0
				[download_content_length] => 247
				[upload_content_length] => 0
				[starttransfer_time] => 0.120696
				[redirect_time] => 0.304223
				[redirect_url] => 
				[primary_ip] => 162.159.129.87
				[certinfo] => Array()
			)
		)		*/
		
		
		/*
		$urlTmp = 'https://bsssjournals.onlinelibrary.wiley.com/doi/pdfdirect/10.1111/j.1365-2389.2006.00837.x?download=true&hmac=';
		file_put_contents(PATH_DL_DONE . 'wiley.pdf', $pdfDL->fetchUrl( $urlTmp, $fetchAry ) );
		
		echo print_r( $fetchAry, 1 ) . "\r\n"; return;
		*/
		
	/*
		$urlPdf = $pdfDL->getPdfUrl();
		echo "------------ DOI: " . $doi . "\r\n";
		echo "------------ Homepage: " . $urlResolved . "\r\n";
		echo "------------ PDF to DL: " . $urlPdf . "\r\n";
		if ( $pdfDL->fetchPdf() != false ) {
			$pdfContent = $pdfDL->getPdf();
			if ( !stristr(substr($pdfContent,0,10),'%PDF-') ) {
				echo ( "\r\n" . 'WARNING: DL is no PDF !? ' . $urlResolved . "\r\n" );
			}
			file_put_contents( $pdf_fname, $pdfContent );
			if ( @filesize($pdf_fname) ) {
				echo ( "\r\n" . 'SUCCESS: Downloaded+Saved ' . $pdf_fname . "\r\n" );
				exit;
				continue;
			}
			echo ( "\r\n" . 'ERROR: Could not save ' . $pdf_fname . "\r\n" );
			exit;
			continue;
		}
	*/

		$infoAry = array(
			'DORA:',
			' - Link : ' . $urlDora,
			' - Pages: ' . $doraPageSum,
			'Ext. Links:',
			' - DOI : ' . $urlDoi,
			$pdfDomain,
			$className,
			' - Home: ' . $urlResolved,
			' - PDF : ' . $pdfUrl,
		);

		$dirPx = PATH_DL_DONE . strtr($pxByPub,' /','_-') . '/';
		$locFile = str_replace(':','__',$pid) . '.' . strtr(urldecode(basename($pdfUrl)),' /','_-');
		$dlSize = $pdfDL->download( $pdfUrl, $dirPx.$locFile );
		$infoAry[] = 'Local:';
		$infoAry[] = ' - File: ' . $locFile;
		$infoAry[] = ' - Size: ' . $dlSize;
		$locFile = str_replace(':','__',$pid) . '.' . 'fetch-info.txt';
		file_put_contents($locFile, print_r($fetchAry,1)."\r\n".implode("\r\n",$infoAry));
		
		echo print_r($fetchAry,1) . "\r\n";
		echo implode("\r\n",$infoAry) . "\r\n";


		sleep( 3 );
		continue;
		// vvvvv --- test/skipped --- vvvvv

		$pdfDL->locatePdfUrl();
		$pdfUrl = $pdfDL->getPdfUrl();
		if ( empty($pdfUrl) ) {
	//		$pdfDL->locate_pdf();
			$pdfDL->load_publisher( $urlResolved );
		}
		echo "\r\n" . "\r\n" . "Pdf URL: " . $pdfUrl . "\r\n" . "\r\n" . "\r\n";

		//echo $pdfDL->getHtml();
		//echo $pdfDL->getUrl();
		//echo $pdfDL->getPdfUrl();	

	//	echo "PDF: '" . $pdf_fname . " - size " . filesize($pdf_fname) . "\r\n";

		break;
		
	}
}

foreach( $pxCountAry as $task => $tAry ) {
	foreach( $tAry as $px => $pxAry ) {
		$pxCountAry[$task][$px] = strval($pxAry['_total_'] . ' (incl. ' . $pxAry['OA']) . ' OA)';
	}
}
echo "\r\nPublisher Overview + Publications per Publisher: " . print_r( $pxCountAry,1) . "\r\n";	
	
return;
	
	
// @file_put_contents($jsonOutput,json_encode($doraPubAry,JSON_PRETTY_PRINT));

?>