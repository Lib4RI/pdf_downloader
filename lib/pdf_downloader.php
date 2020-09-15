<?php


class PdfDownloader{
    private $dom;
    private $doi;
    private $html;
    private $pdf_url;
    private $doi_resolver = 'http://dx.doi.org/';
    private $locators = ['locateFromMetaCitationPdfUrl', 'locateFromJsonElsevier'];
    private $pdf;
    
    public function __construct(){
        $this->dom = new DOMDocument();
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
        $this->html = file_get_contents($url);
        $this->dom->loadHTML($this->html);
        return $this;
    }
    
    public function getHtml(){
        return $this->html;
    }
    
    public function locatePdfUrl(){
        
        foreach ($this->locators as $locator){
            if ($this->{$locator}()){
                break;
            }
        }
        return $this;
    }
    
    public function getPdfUrl(){
        return $this->pdf_url;
    }
            
    public function fetchPdf(){
        $this->pdf = file_get_contents($this->pdf_url);
        return $this;
    }
    
    public function getPdf(){
        return $this->pdf;
    }
    
    private function locateFromMetaCitationPdfUrl(){
        $tags = $this->dom->getElementsByTagName('meta');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('name') == 'citation_pdf_url'){
                $this->pdf_url = $tag->getAttribute('content');
                return true;
            }
        }
        return false;
    }
    
    private function locateFromJsonElsevier(){
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
                $this->pdf_url = $tag->getElementsByTagName('a')->item(0)->getAttribute('href');
                return true;
            }
        }
        return false;
    }
    
    //convenient function
    public function returnPdf($doi){
        return $this->setDoi($doi)->fetchHtml()->locatePdfUrl()->fetchPdf()->getPdf();
    }
    
}