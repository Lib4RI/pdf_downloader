<?php
if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');


/*
	Elsevier - example page:
	https://www.sciencedirect.com/science/article/pii/S0304380005004965
	
	NOTE:
	In the 'PDF Downloader' there is support for Elsevier already.
	This here has a revised 'step 2', however step 3 awill still fail (since now
	we get redirected to a page running JavaScript to download the PDF).
	
	New approach is to use the official API, see
	https://dev.elsevier.com/documentation/FullTextRetrievalAPI.wadl
	Example: https://api.elsevier.com/content/article/doi/10.1016/j.scitotenv.2023.166767?APIKey=f3e6378c22cd0a118af789071b0ea6d7&httpAccept=application/pdf 
	User is 'eresources@lib4ri.ch' (the pasword is in the password manager), project label for the API-Key is 'DORA'.
	Corrent issue is that it only works for OA PFDs despite of being authorized (Dimitris is in touch with Elsevier).

*/


class pdf_dl_elsevier /* extends PdfDownloader */
{
	use publisherTraits;
	
	public $link;	// = link to (publisher's journal) webpage
	public $file;	// = local file to dump HTML code (used only if $optAry['html'] is empty).
	public $htmlCode;
	public $htmlDom;
	
	public function __construct( &$optAry = [] )	// handled keys: 'link', 'html', 'file'
	{
		/* parent::__construct();	*/
		$this->setup( $optAry );
	}

	private function locateElsevier()
	{
		// step 1 
		$else_url = '';
		$tags = $this->htmlDom->getElementsByTagName('input');
		foreach( $tags as $tag ) {
			if (($tag->getAttribute('name') == 'redirectURL')){
				if ( $else_url = @trim(urldecode($tag->getAttribute('value'))) ) {
					break;
				}
			}
		}
//	die( "else_url: " . $else_url );
		if ( empty($else_url) ) { return false; }

		//step2
		$pdf_url = '';		// e.g. https://www.sciencedirect.com/science/article/pii/S0304380005004965/pdf
		$optAry = array( 'link' => $else_url );
		$this->setup( $optAry );		// will update $this->htmlDom
		$tags = $this->htmlDom->getElementsByTagName('script');
		foreach( $tags as $tag ) {
			if ( ($tag->getAttribute('type') == 'application/json') && ($tag->getAttribute('data-iso-key') == '_0') ) {
				$data = json_decode($tag->nodeValue);
				if ( $pdf_url = @trim($data->article->pdfDownload->linkToPdf) ) {
					$pdf_url = 'https://www.sciencedirect.com/' . $pdf_url;		// not the real/direct PDF URL!
					break;
				}
			}
		}
		if ( empty($pdf_url) ) { // making a DOM from (this) HTML may fail for some reasons!(?)
			if( $pos = strpos($this->htmlCode,'<script type="application/json" data-iso-key="_0">') ) {
				if( $posZ = strpos($this->htmlCode,'</script',$pos) ) {
					$pdf_url = implode('',$urlAry['link']) . '?' . http_build_query($urlAry['opt'],'','&',PHP_QUERY_RFC3986);
					$json = substr(strchr( substr($this->htmlCode,$pos,$posZ-$pos) , '>' ),1);	// = JSON
				//	echo print_r( json_decode($json,1), 1 );
					$data = json_decode(trim($json),true);	// array!
				/*	we finally need a link like:
					https://www.sciencedirect.com/science/article/pii/S0304380005004965/pdfft?md5=2bf5a10571a5909ade0a281eec5fb752&pid=1-s2.0-S0304380005004965-main.pdf
					// we have:
					$data['article']['pdfDownload'] => Array
					(
						[urlMetadata] => Array
						(
							[queryParams] => Array
								(
									[md5] => 2bf5a10571a5909ade0a281eec5fb752
									[pid] => 1-s2.0-S0304380005004965-main.pdf
								)
							[pii] => S0304380005004965
							[pdfExtension] => /pdfft
							[path] => science/article/pii
						)
					)		*/
					$urlAry = array(
						'link' => array(
							'https://www.sciencedirect.com/',
							$data['article']['pdfDownload']['urlMetadata']['path'] . '/',
							$data['article']['pdfDownload']['urlMetadata']['pii'],
							$data['article']['pdfDownload']['urlMetadata']['pdfExtension'],
						),
						'opt' => $data['article']['pdfDownload']['urlMetadata']['queryParams'],
					);
					$pdf_url = implode('',$urlAry['link']) . '?' . http_build_query($urlAry['opt'],'','&',PHP_QUERY_RFC3986);
				}
			}
		}
		if ( empty($pdf_url) ) { return false; }
		
	//	echo "PDF URL: " . $pdf_url . "\r\n";
		//step 3
		$optAry = array( 'link' => $pdf_url );
		$this->setup( $optAry );		// will update $this->htmlDom
		$tags = $this->htmlDom->getElementsByTagName('div');
		foreach( $tags as $tag ) {
			if ( $tag->getAttribute('id') == 'redirect-message' ) {
				if ( $href = @trim( $tag->getElementsByTagName('a')->item(0)->getAttribute('href') ) ) {
					return $href;	// real PDF URL!(?)
				}
			}
		}
		return false;
	}

	public function getPdfUrl()		// identical with locateMetaCitationPdfUrl()
	{
		return $this->locateElsevier();
	}
}
