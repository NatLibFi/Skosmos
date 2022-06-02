[![CI tests](https://github.com/NatLibFi/Skosmos/actions/workflows/ci.yml/badge.svg)](https://github.com/NatLibFi/Skosmos/actions/workflows/ci.yml)
[![Test Coverage](https://codeclimate.com/github/NatLibFi/Skosmos/badges/coverage.svg)](https://codeclimate.com/github/NatLibFi/Skosmos/coverage)
[![Code Climate](https://codeclimate.com/github/NatLibFi/Skosmos/badges/gpa.svg)](https://codeclimate.com/github/NatLibFi/Skosmos)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/?branch=master)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=NatLibFi_Skosmos&metric=alert_status)](https://sonarcloud.io/dashboard?id=NatLibFi_Skosmos)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/NatLibFi/Skosmos.svg)](http://isitmaintained.com/project/NatLibFi/Skosmos "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/NatLibFi/Skosmos.svg)](http://isitmaintained.com/project/NatLibFi/Skosmos "Percentage of issues still open")

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
[Report an issue](https://github.com/NatLibFi/Skosmos/issues/new)
