<?php


class PdfDownloader{
    private $dom;
    private $doi;
    private $html;
    private $url;
    private $pdf_url;
    private $doi_resolver = 'http://dx.doi.org/';
    private $locator;
    private $pdf;
    
    public function __construct(){
        $this->dom = new DOMDocument('1.0', 'iso-8859-1');
    }
    
    public function setDoi($doi){
        $this->doi = $doi;
        return $this;
    }
    
    public function setDoiResolver($doi_resolver){
        $this->$doi_resolver = $doi_resolver;
        return $this;
    }

    public function getDoiResolver(){
        return $this->doi_resolver;
    }
    
    public function fetchHtml(){
        $url = $this->getDoiResolver().$this->doi;
        $ch = curl_init($url);
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
        //Tell cURL to return the output as a string.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        //Tell cURL that it should follow any redirects.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //Execute the cURL request and return the output to a string.
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        
        $this->html = curl_exec($ch);
        $this->url = curl_getinfo ($ch,  CURLINFO_EFFECTIVE_URL ); 
        curl_close($ch);
        $this->dom->loadHTML($this->html);
        return $this;
    }
    
    public function getHtml(){
        return $this->html;
    }
    
    public function getUrl(){
        return $this->url;
    }

    public function locatePdfUrl(){
        if (strpos($this->getUrl(), 'elsevier.com') !== false )
        {
            $this->locator = 'locateElsevier';   
        }

        elseif (strpos($this->getUrl(), 'wiley.com') !== false )
        {
            $this->locator = 'locateWiley';
        }
        
        elseif (strpos($this->getUrl(), 'aps.org') !== false )
        {
            $this->locator = 'locateAps';
        }
        
        elseif (strpos($this->getUrl(), 'acs.org') !== false )
        {
            $this->locator = 'locateAcs';
        }
        
        elseif (strpos($this->getUrl(), 'tandfonline.com') !== false )
        {
            $this->locator = 'locateTaylorFrancis';
        }
        
        else{
            $this->locator = 'locateMetaCitationPdfUrl';
        }
        
        $this->pdf_url = $this->{$this->locator}();
        
        return $this;
    }
        
    public function getPdfUrl(){
        return $this->pdf_url;
    }
            
    public function getLocator(){
        return $this->locator;
    }
    
    public function fetchPdf(){
        $ch = curl_init($this->pdf_url);
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        
        curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfilep);
        curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfilep);
        //Tell cURL to return the output as a string.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        //Tell cURL that it should follow any redirects.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //Execute the cURL request and return the output to a string.
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        
        $this->pdf = curl_exec($ch);
//        $this->url = curl_getinfo ($ch,  CURLINFO_EFFECTIVE_URL );
        curl_close($ch);
  
        if(preg_match("/^%PDF-/", $this->pdf)){
            // DO NOTHING
        }else{
            $this->pdf = false;
        }
        
        return $this;
    }
    
    
    public function getPdf(){
        return $this->pdf;
    }
    
    private function locateMetaCitationPdfUrl(){
        $tags = $this->dom->getElementsByTagName('meta');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('name') == 'citation_pdf_url'){
                return $tag->getAttribute('content');
            }
        }
        return false;
    }
    
    private function locateWiley(){
        $turl = $this->locateMetaCitationPdfUrl();
        if ($turl){
            return str_replace('doi/pdf', 'doi/pdfdirect', $this->getPdfUrl());
        }
        return false;
    }
    
    private function locateTaylorFrancis(){
        $tags = $this->dom->getElementsByTagName('a');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('class') == 'show-pdf'){
                return 'https://www.tandfonline.com'.$tag->getAttribute('href');
            }
        }
        return false;
    }
    
    private function locateElsevier(){
        $else_dom = new DOMDocument();
        $found = false;
        // step 1 
        $tags = $this->dom->getElementsByTagName('input');
        foreach( $tags as $tag ) {
            if (($tag->getAttribute('name') == 'redirectURL')){
                $else_url = urldecode($tag->getAttribute('value'));
                $found = true;
                break;
            }
        }
        if (!$found){
            return false;
        }
        
        //step2
        $found = false;
        $else_html = file_get_contents($else_url);
        $else_dom->loadHTML($else_html);
        
        $tags = $else_dom->getElementsByTagName('script');
        foreach( $tags as $tag ) {
            if (($tag->getAttribute('type') == 'application/json') && ($tag->getAttribute('data-iso-key') == '_0')){
                $data = json_decode($tag->nodeValue);
                $else_url = 'https://www.sciencedirect.com/'.$data->article->pdfDownload->linkToPdf;//.'&isDTMRedir=true&download=true';
                $found = true;
                break;
            }
        }
        if (!$found){
            return false;
        }
        
        //step 3
        $found = false;
        $else_html = file_get_contents($else_url);
        $else_dom->loadHTML($else_html);
        
        $tags = $else_dom->getElementsByTagName('div');
        foreach( $tags as $tag ) {
            if (($tag->getAttribute('id') == 'redirect-message')){
                return $tag->getElementsByTagName('a')->item(0)->getAttribute('href');
            }
        }
        return false;
    }
    
    private function locateAps(){
        $tags = $this->dom->getElementsByTagName('a');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('class') == 'small button'){
                return 'https://journals.aps.org'.$tag->getAttribute('href');
            }
        }
        return false;
    }
    
    private function locateAcs(){
        $tags = $this->dom->getElementsByTagName('a');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('title') == 'PDF'){
                return 'https://pubs.acs.org'.$tag->getAttribute('href');
            }
        }
        return false;
    }
    //convenient function
    public function returnPdf($doi){
        return $this->setDoi($doi)->fetchHtml()->locatePdfUrl()->fetchPdf()->getPdf();
    }
    
}