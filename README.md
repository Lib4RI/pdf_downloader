# Lib4RI PDF Download Test


## Introduction

This repository branch is part of the task in [Redmine ticket #318](http://lib-dora-dev1.emp-eaw.ch:3000/issues/318).
Only a portion of publications in DORA are affected by this task/ticket, please see [info.MAIL-PublicationSelection.html](./info.MAIL-PublicationSelection.html) for the corresponding Solr query.
```
Expected cover-page status and amount of affected DORA publications per publisher:
(
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

) // by console.Publisher.Amount-Publication.php
```
First it will be necessary to get the possibly damaged PDF files once again, so goal here was a test how for it is possible to download PDF files from publisher websites by HTML meta-tag evaluation or even more intensive web scarping (since - so far - only a (limited) [API from Elevier](https://dev.elsevier.com/documentation/ArticleRetrievalAPI.wadl) is available to download PDFs).
The result of this test you will find in the ['pdf_dl_test' directory](./pdf_dl_test) containing the downloaded PDF (where possible) and debug information for each publisher treated (there is also a hint in the name which code/tool used).


## Requirements

* PHP *console* environement with cUrl + Wget installed.
* Publication data is intended to be retrieved via our [Solr web interface](http://lib-dora-prod1.emp-eaw.ch:8080/solr/).


# License
Not really intended to be published!(?) However if licensed then probably under [GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.en.html)


# Technical
There is new class called 'PdfHamster', extending 'PdfDownloader' class object.
Major features are download support with 'Wget' and PHP itself incl. random (brwoser) user-agent string (approximated or scrapped from the web to be up-to-date).


# To Do
* Publications from SPIE were handled by Sarah already! :=)
* Bascially no 'bulk download' was set up so far, please check the ['pdf_dl_test' directory](./pdf_dl_test) there this is still possible at all.
* IOP seems to have a sophisticated browser handling - it may be possible to download one PDF, then however they block the download approaches used here for several minutes.
* Negitiaions with Elevier are in process (ask Dimitris). So far it was only possible to retrieve full PDFs if they were OA, othewise we just got the first page.
* There is a severe problem to download automatically ~700 PDFs from Wiley with existing approaches. However perhaps there is possibility to do this via [Wiley's official data-mining API](https://onlinelibrary.wiley.com/library-info/resources/text-and-datamining).

