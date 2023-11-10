<?php
/*
	TASK/DESCRIPTION:
	Will use a free Elevier/Scopus API to get the DOI from a Scopus ID.

	EXECUTION (bash command):
	php console.ScopusID-to-DOI.php

*/
// ========================================================================
// function + libraries:

if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

include_once( PATH_INSTALL . 'pdf_dl_lib/pdf_downloader.php'); // for PDFdownloader
// inc...


function doraMakeStringKeyValue( $kvAry = [], $sSep = ': ' )
{
	$sAry = [];
	foreach( $kvAry as $key => $val ) {
		$sAry[] = @strval($key) . $sSep . @strval($val);
	}
	return $sAry;
}

function doraMakeFirefoxVersion()	// this is total guess, optimized/re-sync'ed: 2023-09-27
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

	$headerAry['http']['header']['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0';
	
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


//$jsonCode = queryScopus( '2-s2.0-82755170946') . "\r\n";
$jsonCode = queryWoS( '000225079300029') . "\r\n";
echo "\r\n" . $jsonCode . "\r\n" . print_r($infoCurlAry,1) . "\r\n"; return;




$solrLinkAry = array(
	'wos-only' => 'http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?wt=csv&indent=true&csv.separator=|&sort=PID+asc&rows=987654321&q=PID:*%5c%3a*+AND+mods_identifier_ut_mt:*+NOT+mods_identifier_doi_mt:10*+NOT+mods_identifier_scopus_mt:*+AND+mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2006-01-01T00%3A00%3A00Z%20TO%202012-01-01T00%3A00%3A00Z]+NOT+(RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/psi%5c%3Apublications%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/psi%5c%3Aexternal%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Apublications%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Awsl%5c-int%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Awsl%5c-ext%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/eawag%5c%3Aext%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Aforum%22)&fl=PID%2c+mods_identifier_doi_mt%2c+mods_identifier_doi_mt%2c+mods_identifier_scopus_mt%2c+mods_identifier_ut_mt%2c+mods_identifier_issn_mt%2c+mods_identifier_e-issn_mt%2c+mods_originInfo_publisher_mt%2c+mods_note_notesLib4RI_mt%2c+mods_note_additional?information_mt',
	'dora-all' => 'http://lib-dora-prod1.emp-eaw.ch:8080/solr/collection1/select?wt=csv&indent=true&csv.separator=|&sort=PID+asc&rows=987654321&q=PID:*%5c%3a*+AND+(mods_identifier_doi_mt:*+OR+mods_identifier_scopus_mt:*+OR+mods_identifier_ut_mt:*)+AND+mods_originInfo_encoding_w3cdtf_keyDate_yes_dateIssued_dt:[2006-01-01T00%3A00%3A00Z%20TO%202012-01-01T00%3A00%3A00Z]+NOT+(RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/psi%5c%3Apublications%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/psi%5c%3Aexternal%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Apublications%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Awsl%5c-int%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Awsl%5c-ext%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/eawag%5c%3Aext%22+OR+RELS_EXT_isMemberOfCollection_uri_ms:%22info%5c%3Afedora%5c/wsl%5c%3Aforum%22)&fl=PID%2c+mods_identifier_doi_mt%2c+mods_identifier_doi_mt%2c+mods_identifier_scopus_mt%2c+mods_identifier_ut_mt%2c+mods_identifier_issn_mt%2c+mods_identifier_e-issn_mt%2c+mods_originInfo_publisher_mt%2c+mods_note_notesLib4RI_mt%2c+mods_note_additional?information_mt',
);	


$pidAry = array( /* 'typical' data from Solr */ );
$cacheFileSolr = '/tmp/_dora-pdf-dl.Solr.' . date("Y-m-d-H").'h' . '.json';
$logFileSolr = '/tmp/_dora-pdf-dl.Solr.' . date("Y-m-d-H").'h' . '.log';

$doiAry = array( /* pid => doiFound */ );
$cacheFileDoi = '/tmp/_dora-pdf-dl.DOI.' . 'Results' . '.json';
$logFileDoi = '/tmp/_dora-pdf-dl.DOI.' . 'Results' . '.log';

// Get JSON file with DOIs found so far for the PIDs in DORA:
if ( @filesize($cacheFileDoi) ) {
	$jsonData = @file_get_contents($cacheFileDoi);
	$doiAry = @json_decode( $jsonData, true );
}

// Get JSON file from Solr (caching if for the current hour):
$jsonData = '';
if ( @filesize($cacheFileSolr) ) {
	$jsonData = @file_get_contents($cacheFileSolr);
} else {
	$solrLink = $solrLinkAry['dora-all'];
	$solrLink = str_replace('lib-dora-dev1',@strval(exec('hostname')),$solrLink);		// DEV or PROD server?
	$solrLink = str_replace('wt=csv&','wt=json&',$solrLink.'&');  // JSON instead of CSV
	$jsonData = @file_get_contents($solrLink);
	@file_put_contents($cacheFileSolr,$jsonData);
}
$pidAry = json_decode( $jsonData, true );
if ( @intval($pidAry['response']['numFound']) < 1  ) {
	echo '--- Could not any items!? ---'; exit;
}
// echo print_r( $pidAry['response']['docs'], 1 ) . 'Total ' . $pidAry['response']['numFound'] . "\r\n"; return;



$loopNum = 0;

foreach( $pidAry['response']['docs'] as $pIdx => $pAry ) {

	$ary = explode(':',$pAry['PID']);
	if ( intval($ary[0]) || !intval($ary[1]) ) { continue; }
	$pid = $ary[0].':'.rtrim($ary[1]);
/*
	if ( @empty($pAry['mods_identifier_scopus_mt']) ) { continue; }
	echo $pid . " ::: " . print_r( $pAry['mods_identifier_scopus_mt'], 1 ) . "\r\n";
	$id = reset($pAry['mods_identifier_scopus_mt']);
	$jsonCode = queryScopus( $id );
	$jsonAry = json_decode( $jsonCode, true );
*/
	if ( @empty($pAry['mods_identifier_ut_mt']) ) { continue; }
	echo $pid . " ::: " . print_r( $pAry['mods_identifier_ut_mt'], 1 ) . "\r\n";
	$id = reset($pAry['mods_identifier_ut_mt']);
	$jsonCode = queryWoS( $id );
	$jsonAry = json_decode( $jsonCode, true );

	echo $pid . " ::: " . print_r( $jsonAry, 1 ) . "\r\n";

break;
	
	$doi = @strval($jsonAry['search-results']['entry'][0]['prism:doi']);
	
	echo print_r( $doi, 1 );

	break;
	
}
	
	
	
	
	
	
	
	
// @file_put_contents($jsonOutput,json_encode($doraPubAry,JSON_PRETTY_PRINT));

?>