# Little YSO for Skosmos testing

One of the test vocabularies used for Skosmos tests is called `little-yso`. This is a small subset of concepts related to archaeology that have been extracted from the General Finnish Ontology YSO. YSO is copyrighted by National Library of Finland, Semantic Computing Research Group (SeCo) and The Finnish Terminology Centre TSK. It is used here according to the CC By 4.0 license.

The test data can be found in the directory tests/test-vocab-data/little-yso.ttl, and its configuration is located in the tests/testconfig.ttl file under the section ':test-with-little-yso'.

The data has been generated using the Apache Jena Fuseki s-query tool:
`[your Apache Jena Fuseki folder]/bin/s-query --service=http://api.finto.fi/sparql --query=[your Skosmos base folder]/tests/docs/generate-little-yso.rq > [your Skosmos base folder]/tests/test-vocab-data/little-yso.ttl`

After generating a new vocabulary (added the data file and configuration):

- shut down any running containers (in the tests directory): `sudo docker compose down`
- start (in the tests directory): `./init_containers.sh`
- go to: http://localhost:9090/test-with-little-yso/en/
