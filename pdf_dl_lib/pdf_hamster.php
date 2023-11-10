<?php

if ( !defined('PATH_INSTALL') ) { define('PATH_INSTALL','./'); }

define( 'PATH_LIB_PX', PATH_INSTALL . 'pdf_dl_lib/publisher-handling/' );

// Including all inc-files for publisher directory:
if ( defined('PATH_LIB_PX') && !empty(PATH_LIB_PX) && @is_dir(PATH_LIB_PX) ) {
	$dirInc = PATH_LIB_PX;
	foreach( @scandir($dirInc) as $dirItem ) {
		if ( substr($dirItem,0,7) == 'pdf_dl_' && substr($dirItem,-4) == '.inc' && filesize($dirInc.$dirItem) ) {
	/*	*/	echo "PDF Download, including '" . $dirItem . "'...\r\n";
			include_once($dirInc.$dirItem);
		}
	}
}


class PdfHamster extends PdfDownloader
{
	public $pdf_site;
	public $doi;
	public $dom;
	public $html;
	public $url;

	public function __construct($id = null)
	{
		parent::__construct();
		if ( @intval($id) == 10 ) { $this->setDoi($doi); }
		$this->pdf_site = '';	// assigned by locate_pdf()
		$this->dom = new DOMDocument('1.0', 'iso-8859-1');
	}

	public static function guessUserAgent($browser = '')	// this is total guess, however optimized/re-sync'ed: 2023-09-27
	{
		if ( $browser == 'firefox' || $browser != 'firefox' ) { // only for Firefox so far...
			$vApprox = ( ( time() >> 22 ) - 287 );	// guessing/reflecting (security) updates, >>21 - 691 could be ok too!?
			$vEngine = min( $vApprox, 109 );	// no further development of the enigine!(?)
			return ('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:' . $vEngine . '.0) Gecko/20100101 Firefox/' . $vApprox . '.0');
		}
		return 'Mozilla/5.0 (' . randomName() . '; 64bit; rv:1.0) Gecko/2010';
	}

	public static function randomName() {
		$name = str_split('QWRTZPSDFGHJKLYXCVBNM')[mt_rand(0,20)];
		for($r=0;$r<(mt_rand(2,4) >> 1);$r++) {
			$name .= str_split('aeiou')[mt_rand(0,4)];
			$rand = mt_rand(0,4);
			$name .= ( $rand ? substr(str_split('wwrrttrrttzzppssddffggllllbbnnmm',2)[mt_rand(0,15)],0,max($rand >> 1,1)) : str_split('phtzckthstthstxxch',2)[mt_rand(0,8)] );
		}
		$name .= str_split('euuiioooaaayy')[mt_rand(0,12)];
		$rand = mt_rand(0,15);
		return ( $name . ( $rand < 3 ? substr('nhn',$rand,1) : '' ) );
	}

	public static function randomUserAgent( $allAsArray = 0 ) {		// made for Eawag, see also/generally: https://en.wikipedia.org/wiki/Usage_share_of_web_browsers
		$browserAry = ['opera','','','','edge','firefox','','','chrome']; // leave some empty to shift/increase dominance!
		$uaAry = [];
		$jsonFile = '/tmp/browser.user-agent.weighted-list.json';		// cache file
		if ( !( $ft = @filemtime($jsonFile) ) || ( time() - $ft ) > 86400 ) { // =24h
			foreach( $browserAry as $bIdx => $browser ) {
				$urlTmp = 'https://www.whatismybrowser.com/guides/the-latest-user-agent/' . $browser;
				$fetchAry = array( 'useragent' => PdfHamster::guessUserAgent() );
				if ( !( $htmlCode = PdfHamster::fetchByPhp( $urlTmp, $fetchAry ) ) ) { continue; }
				$htmlObj = new DOMDocument('1.0', 'iso-8859-1');
				if ( !( @$htmlObj->loadHTML($htmlCode) ) ) { continue; }
				$bCount = 0;
				foreach( $htmlObj->getElementsByTagName('li') as $tag ) {
					if ( substr($tag->textContent,0,8) != 'Mozilla/' ) { continue; }
					while( $bCount < ( $bIdx + 1 ) ) {	// to add the 1st UA string found more than once
						$uaAry[] = $tag->textContent;
						$bCount++;
					}
					$uaAry[] = $tag->textContent;
					$bCount++;
					if ( $bCount > ( $bIdx + 3 ) ) { break; }
				}
			}
			$uaAry = array_filter($uaAry);
			if ( !empty($uaAry) ) {
				file_put_contents($jsonFile,json_encode($uaAry,JSON_PRETTY_PRINT));
			}
		}
		if ( empty($uaAry) ) {
			$uaAry = json_decode( file_get_contents($jsonFile), true );
		}
		if ( !empty($uaAry) ) {
			if ( $allAsArray ) {
				if ( $allAsArray !== true && $allAsArray !== 1 ) { shuffle($uaAry); /* by default if enabled */ }
				return $uaAry;
			}
			return $uaAry[rand(0,sizeof($uaAry)-1)];
		}
		$browser = '';
		if ( @!method_exists('PdfHamster','guessUserAgent') || !( $browser = PdfHamster::guessUserAgent() ) ) {
			$browser = 'Mozilla/5.0 (Generic)'; // aux. value - ensure however this function will return a(ny) string!
		}
		return ( ( !$allAsArray && is_array($browser) ) ? $browser : array($browser) );
	}

	public static function randomLanguage( $extraEnUS = 8 )
	{
		$ary = array(
			'en-GB,en;q=0.8',	/* considered/weighted once */ 
			'en-GB,en;q=0.8',	/* considered/weighted once */ 
			'en-GB,en;q=0.7',	/* considered/weighted once */
			'en-GB,en;q=0.7',	/* considered/weighted once */
			'en-GB,en;q=0.7',	/* considered/weighted once */
			'en-US,en;q=0.5',	/* keep en-US for last entry, will be prevalent: selection below 'assumes' extra entryies */
		);
		$max = sizeof($ary) - 1;
		return $ary[min(rand(0,$max+$extraEnUS),$max)];
	}

	public static function makeStringArray( $kvAry = [], $sSep = ': ', $skipNumKey = true )
	{
		$sAry = [];
		foreach( $kvAry as $key => $val ) {
			$sAry[] = ( ( $skipNumKey && ( $key === 0 || intval($key) ) ) ? '' : ( @strval($key) . $sSep ) ) . @strval($val);
		}
		return $sAry;
	}


	public static function fetchUrl( $url, &$optAry = [] /* pointer on external array */ )
	{
		if ( @!empty($optAry['url']) ) { // $optAry['url'] set in the array is dominant, overwriting!
			$url = $optAry['url'];
		}
		elseif ( empty($url) && @!empty($optAry['link']) ) { // $optAry['link'] is subsidiary, will 'assist' only if $url input is empty.
			$url = $optAry['link'];
		}
		$funcName = 'fetchBy' . ( @empty($optAry['tool']) ? 'Curl' : ucFirst(strtolower($optAry['tool'])) );
		return ( method_exists('PdfHamster',$funcName) ? PdfHamster::$funcName( $url, $optAry ) : false );
	}

	public static function fetchByPhp( $url, &$optAry = [] /* pointer on external array */ )
	{
		$userAgent = ( @empty($optAry['useragent']) ? ( method_exists('PdfHamster','randomUserAgent') ? PdfHamster::randomUserAgent() : 'Mozilla/5.0 (Generic)' ) : $optAry['useragent'] );
		$httpData = array(
			'http' => array(
				'header' => array(
					'User-Agent: ' . /* PdfHamster::guessUserAgent($browser) */ $userAgent,
					'Accept-Language: en-US,en;q=0.5',
				 ),
				'method' => 'GET',
			),
		);
		if ( $content = file_get_contents($url,false,stream_context_create($httpData)) ) {
			if ( $locFile = @trim($optAry['file']) ) {
				if ( !is_dir(dirname($locFile)) ) {
					@mkdir( dirname($locFile), ( empty($optAry['dirPerm']) ? 0777 : $optAry['dirPerm'] ), true ); // try at least...
				}
				file_put_contents($locFile,$content);
			}
			return $content;
		}
		return false;
	}

	public static function fetchByWget( $url, &$optAry = [] /* pointer on external array */ )
	{
		if ( empty($url) ) { return false; }
		$userAgent = ( @empty($optAry['useragent']) ? ( method_exists('PdfHamster','randomUserAgent') ? PdfHamster::randomUserAgent() : 'Mozilla/5.0 (Generic)' ) : $optAry['useragent'] );
		if ( @empty($headerAry['Accept-Language']) ) { $headerAry['Accept-Language'] = PdfHamster::randomLanguage(); }
		$headerAry = PdfHamster::makeStringArray($headerAry);
		$cmdAry = array(
			'wget',
			'-U "' . $userAgent . '"',
			'--tries=3',
			( @boolval($optAry['verbose']) ? '--verbose' : '--quiet' ),
		/*	'--continue',	*/
			'--header "' . implode('" --header "',$headerAry) . '"',
			'"' . $url . '"',
		);
		$deleteAfter = false;
		if ( !( $locFile = @trim($optAry['file']) ) ) {
			$locFile = '/tmp/tmp.DL.Wget.' . uniqid() . '.dat';
			$deleteAfter = true;
		}
		if ( !is_dir(dirname($locFile)) ) {
			@mkdir( dirname($locFile), ( empty($optAry['dirPerm']) ? 0777 : $optAry['dirPerm'] ), true ); // try at least...
		}
		$cmdAry[] = '-O "' . $locFile . '"';

		if ( @isset($optAry['info']) && empty($optAry['info']) && !is_array($optAry['info']) ) {
			$optAry['info'] = array();
		}
		if ( @empty($optAry['info']['command']) ) {
			$optAry['info']['command'] = implode(' ',$cmdAry);
		}
		$optAry['info']['result'] = null;
		exec( implode(' ',$cmdAry), $optAry['info'], $optAry['info']['result'] );

		if ( $deleteAfter ) {
			$dataGot = @file_get_contents($locFile);
			unlink($locFile);
			return $dataGot;
		}
		return file_get_contents($locFile);
	}

	
	public static function fetchByCurl( $url, &$optAry = [] /* pointer on external array */ )
	{
		if ( empty($url) ) { return false; }
		/* Defaults (example) for $optAry = array(
			'verbose' => false,
			'timeout' => 10,
			'cookie' => '/tmp/cUrl.' . uniqid() . '.txt',
			'useragent' => 'Mozilla/5.0 (Whatever/Test)',
			'header' => array( 'User-Agent' => 'Mozialla/...', 'Accept-Language' => 'en-us,en;q=0.5', 'API-Key' => 'abcdef...' ),
			'info' => array(),
		*/
		
		$userAgent = ( @empty($optAry['useragent']) ? ( method_exists('PdfHamster','randomUserAgent') ? PdfHamster::randomUserAgent() : 'Mozilla/5.0 (Generic)' ) : $optAry['useragent'] );
		$cUrl = curl_init($url);
		$headerAry = ( @is_array($optAry['header']) ? $optAry['header'] : array() );
		if ( @empty($headerAry['User-Agent']) ) { $headerAry['User-Agent'] = $userAgent; }
		if ( @empty($headerAry['Accept-Language']) ) { $headerAry['Accept-Language'] = PdfHamster::randomLanguage(); }
		$headerAry = PdfHamster::makeStringArray($headerAry);
		curl_setopt($cUrl, CURLOPT_HTTPHEADER, $headerAry );
		if ( @is_array($optAry['header']) ) { // add (update) the header data used
			$optAry['header'] = $headerAry;
		}
		if ( !empty($optAry['cookie']) /* expecting a file path */ ) {
			curl_setopt($cUrl, CURLOPT_COOKIEJAR, $optAry['$cookie'] );
			curl_setopt($cUrl, CURLOPT_COOKIEFILE, $optAry['cookie'] );
		}
		curl_setopt($cUrl, CURLOPT_USERAGENT, $userAgent );
		curl_setopt($cUrl, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($cUrl, CURLOPT_BINARYTRANSFER, @boolval($optAry['binary']) );
		curl_setopt($cUrl, CURLOPT_VERBOSE, @boolval($optAry['verbose']) );
		curl_setopt($cUrl, CURLOPT_HEADER, true );
		if ( !( $timeout = @intval($optAry['timeout']) ) ) { $timeout = 30; }
		curl_setopt( $cUrl, CURLOPT_CONNECTTIMEOUT, max($timeout,0) );
		curl_setopt( $cUrl, CURLOPT_TIMEOUT, max($timeout*3,0) );	// arbitrary multiplyer, in case e.g. of (assumed) 3 redirections
		$dataGot = curl_exec($cUrl);
		if ( $locFile = @trim($optAry['file']) ) {
			if ( !is_dir(dirname($locFile)) ) {
				@mkdir( dirname($locFile), ( empty($optAry['dirPerm']) ? 0777 : $optAry['dirPerm'] ), true ); // try at least...
			}
			file_put_contents($locFile,$dataGot);
		}
	/*
		// alternate handling/test with file-handle - skipped, not fully tested!
		$fpLocal = null;	
		if ( $locFile = @trim($optAry['file']) ) {
			if ( !is_dir(dirname($locFile)) ) {
				@mkdir( dirname($locFile), ( empty($optAry['dirPerm']) ? 0777 : $optAry['dirPerm'] ), true ); // try at least...
			}
			if ( $fpLoc = fopen($locFile,'w') ) {
				curl_setopt( $cUrl, CURLOPT_FILE, $fpLoc);
			}
		}
		$dataGot = curl_exec($cUrl);
		echo $dataGot;
		if ( $fpLocal ) {
			fclose($fpLocal);
		}
	*/
		if ( @isset($optAry['info']) && empty($optAry['info']) ) {
			$optAry['info'] = curl_getinfo($cUrl); // we will need this for CURLINFO_EFFECTIVE_URL
			$tmp = curl_getinfo( $cUrl, CURLINFO_EFFECTIVE_URL ); // this should always(?) be the same as resulting $optAry['info']['url']
			if ( $tmp != $optAry['info']['url'] ) { $optAry['info']['effective_url'] = $tmp; /* rather added for debug reasons only */ }
			$optAry['info']['response'] = "\r\n" . trim(substr($dataGot,0,curl_getinfo($cUrl,CURLINFO_HEADER_SIZE))) . "\r\n";
		}
		curl_close($cUrl);
		return $dataGot;
	}
	
	public static function download( $url = '', $locFile = '', &$optAry = [] )
	{
		if ( empty($url) ) { return false; }
		if ( !empty($locFile) ) {
			$optAry['file'] = $locFile;
		}
		$defaultAry = array(
			'binary' => true,
			'dirPerm' => 0777,
			'regExp' => '/^\%PDF\-/',
		);
		foreach( $defaultAry as $defKey => $defVal ) {
			if ( @!isset($optAry[$defKey]) ) { $optAry[$defKey] = $defVal; }
		}
		if ( !( $dlData = PdfHamster::fetchUrl( $url, $optAry ) ) ) {
			return false;
		}
		if ( !empty($optAry['regExp']) && !preg_match($optAry['regExp'],$dlData) ) { return false; }
		if ( !empty($optAry['file']) ) {
			return ( @filesize($optAry['file']) );
		}
		return $dlData;
	}

	public static function getDomain($url = '', $siteOnly = false)
	{
		if ( $pos = strpos($url,'//') ) {
			$url = substr($url,$pos+2); // cutting off http://
		}
		$url = strtok($url.'/','/');	// could be: dora.lib4ri.ch
		$tmpAry = explode('.',strtolower($url));
		$top = array_pop($tmpAry);
		$site = array_pop($tmpAry);
		if ( $top == 'uk' && in_array($site,['ac','co','gov','org']) ) {
			$top = $site . '.uk';
			$site = array_pop($tmpAry);
		}
		return ( $siteOnly ? $site : trim($site.'.'.$top,'.') );
	}

	public function locate_pdf($url = '')   // to receive $this->getUrl() resp.  https://iwaponline.com/ebooks/book/111/Mathematical-Modeling-of-Biofilms
	{
		if ( empty($url) ) {
			$url = $this->getPdfUrl();
		}
		if ( $site = PdfHamster::getDomain($url) ) {
			$this->pdf_site = $site;
			$nameFunc = 'locate_pdf_' . strtok($site.'.','.');
			if ( method_exists($this,$nameFunc) ) {
				return PdfHamster::$nameFunc();
			}
		}
		return false;
	}

	public function load_publisher($url = '', $incPath = '/var/www/html/data/work-in-progress/')
	{
		if ( empty($url) ) {
			$url = $this->getUrl();
		}
		if ( $site = PdfHamster::getDomain($url) ) {
			$this->pdf_site = $site;
			$nameInc = $incPath . 'pdf_hamster.' . $site . '.inc';
			if ( @filesize($nameInc) ) {
				include_once( $nameInc );
			}
		}
		$nameFunc = 'locate_pdf_' . strtok($site.'.','.');
		if ( method_exists($this,$nameFunc) ) {
			$this->$nameFunc('Frank');
		}
	}

}
