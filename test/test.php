<?php
error_reporting(E_ERROR | E_PARSE);

include '../lib/pdf_downloader.php';

//$doi = '10.1039/c9lc01127g';
$doi = '10.1016/j.combustflame.2020.08.040';

$down = new PdfDownloader();

$down->setDoi($doi)->fetchHtml()->locatePdfUrl();