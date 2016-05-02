[![Build Status](https://travis-ci.org/NatLibFi/Skosmos.svg?branch=master)](https://travis-ci.org/NatLibFi/Skosmos)
[![Test Coverage](https://codeclimate.com/github/NatLibFi/Skosmos/badges/coverage.svg)](https://codeclimate.com/github/NatLibFi/Skosmos/coverage)
[![Code Climate](https://codeclimate.com/github/NatLibFi/Skosmos/badges/gpa.svg)](https://codeclimate.com/github/NatLibFi/Skosmos)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/NatLibFi/Skosmos/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/ca1939e4-919d-4429-95e7-d63e0a35f9a1/mini.png)](https://insight.sensiolabs.com/projects/ca1939e4-919d-4429-95e7-d63e0a35f9a1)
[![Codacy Badge](https://api.codacy.com/project/badge/grade/c41550cd00fe4962ba766f8b5fe4a5ff)](https://www.codacy.com/app/NatLibFi/Skosmos)

Skosmos
=======

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

Skosmos is implemented using PHP, with Twig templates and e.g. jQuery and
jsTree used to build the web interface, and EasyRdf for SPARQL and RDF data
access. We use BrowserStack for making sure Skosmos works consistently with different browsers.

The code is open source under the MIT license. See 
[Installation](https://github.com/NatLibFi/Skosmos/wiki/Installation) in the 
wiki for details on obtaining the source and running your own instance of 
Skosmos.

For information about released versions, see 
[Release Notes](https://github.com/NatLibFi/Skosmos/wiki/Release-Notes) in 
the wiki.

Skosmos was formerly known as ONKI Light.

## Reporting issues

If you have found a bug please use the provided template and create a new issue:
[Report an issue](https://github.com/NatLibFi/Skosmos/issues/new?body=%23%23%20At%20which%20URL%20did%20you%20encounter%20the%20problem%3F%0A%0A%23%23%20What%20steps%20will%20reproduce%20the%20problem%3F%0A1.%0A2.%0A3.%0A%0A%23%23%20What%20is%20the%20expected%20output%3F%20What%20do%20you%20see%20instead%3F%0A%0A%23%23%20What%20browser%20did%20you%20use%3F%20(eg.%20Firefox%2C%20Chrome%2C%20Safari%2C%20Internet%20explorer)%0A)
