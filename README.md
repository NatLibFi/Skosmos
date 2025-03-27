[![CI tests](https://github.com/NatLibFi/Skosmos/actions/workflows/ci.yml/badge.svg)](https://github.com/NatLibFi/Skosmos/actions/workflows/ci.yml)
[![Test Coverage](https://codeclimate.com/github/NatLibFi/Skosmos/badges/coverage.svg)](https://codeclimate.com/github/NatLibFi/Skosmos/coverage)
[![Code Climate](https://codeclimate.com/github/NatLibFi/Skosmos/badges/gpa.svg)](https://codeclimate.com/github/NatLibFi/Skosmos)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/?branch=master)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=NatLibFi_Skosmos&metric=alert_status)](https://sonarcloud.io/dashboard?id=NatLibFi_Skosmos)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/NatLibFi/Skosmos.svg)](http://isitmaintained.com/project/NatLibFi/Skosmos "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/NatLibFi/Skosmos.svg)](http://isitmaintained.com/project/NatLibFi/Skosmos "Percentage of issues still open")

# Introduction
This project is aimed at making reports that have been converted to Linked Open Data based on the [IPBES ontology](https://github.com/IPBES-Data/IPBES_Ontology) accessible and searchable from a central server. The reports have been converted by the IPBES data management task force.

The project has 2 functions
1. Vocabulary: The project allows navigating and visualising the vocabularies used in the production of IPBES assessment reports and other IPBES products using Simple Knowledge Organization System (SKOS). SKOS is a W3C recommendation designed for representation of thesauri, classification schemes, taxonomies, subject-heading systems, or any other type of structured controlled vocabulary. 
2. Representation of IPBES assessment reports in Linked Open Data: The project applies the SKOS vocabularies to represent IPBES assessment reports that have been converted in linked open data with their structure of chapters, references, authors, knowledge gaps and background messages and other features described in the IPBES ontology.


# Installation
Instructions on how to set up a similar server can be found on [Set up instructions](https://github.com/NatLibFi/Skosmos/wiki/InstallTutorial). You will need to Ccnfigure cache to improve performance. Instructions on how to improve cache can be found on the same set up page.

# Loading data using the Fuseki web interface
For Vocabularies: Go to the Fuseki web interface (http://localhost:3030/#/), click on "datasets" tab at the top and click on "add data", enter "Dataset graph name" as http://ontology.ipbes.net/ipbes/, click on "select files", browse your computer to upload .ttl file "sample.ttl" and click on "upload now".
For Linked Open Data: Go to the Fuseki web interface (http://localhost:3030/#/), click on "manage" tab at the top and click on "new dataset", enter "Dataset name" as 3 letter assessment abbreviation in small letters e.g. "ias", check Dataset type as "Persistent (TDB2) – dataset will persist across Fuseki restarts", click "create dataset". Once dataset is created you can upload .ttl file by clicking on "add data" then browse your computer to upload .ttl file and click on "upload now".

# Deleting data using the Fuseki web interface
Go to the Fuseki web interface (http://localhost:3030/#/), click on "manage" tab at the top and select "remove". You may need to restart apache for changes to be applied

# View Data
Vocabularies can be viewed on http://localhost/Skosmos and clicking on specific Categories that have been made available after configuring config.ttl
Linked Open Data can be viewed on files uploaded under www e.g. http://localhost/Skosmos/www/ias.php

# Housekeeping
Upload all datasets to _dataset
1. vocabularies: short descriptive name in small letters e.g. glossary.ttl, ontology.ttl, ipbes.ttl
2. Linked open data: name them using 3 letter assessment abbreviation in small letters e.g. ias.ttl 

# Skosmos
Skosmos is a web-based tool providing services for accessing controlled
vocabularies, which are used by indexers describing documents and searchers
looking for suitable keywords. Vocabularies are accessed via SPARQL
endpoints containing SKOS vocabularies. See
[skosmos.org](http://skosmos.org) for more general information about
Skosmos including use cases, current users and publications.

In addition to a modern web user interface for humans, Skosmos provides a
[REST-style API](https://github.com/NatLibFi/Skosmos/wiki/REST-API) and Linked 
Data access to the underlying vocabulary data.

Skosmos is used as a basis for the [Finto](http://finto.fi) vocabulary service. 
The latest development version is also available at 
[dev.finto.fi](http://dev.finto.fi).

Skosmos is implemented using PHP (supported versions: 7.3, 7.4 and 8.0), with 
Twig templates and e.g. jQuery and jsTree used to build the web interface, and 
EasyRdf for SPARQL and RDF data access. 

The code is open source under the MIT license. See 
[Installation](https://github.com/NatLibFi/Skosmos/wiki/Installation) in the 
wiki for details on obtaining the source and running your own instance of Skosmos.

For information about released versions, see 
[Release Notes](https://github.com/NatLibFi/Skosmos/releases).


## Reporting issues

If you have found a bug please create a new issue using the provided template:
[Report an issue](https://github.com/NatLibFi/Skosmos/issues/new/choose)
