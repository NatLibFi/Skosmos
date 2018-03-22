#!/bin/bash
# Note: This script must be sourced from within bash, e.g. ". init_fuseki.sh"

FUSEKI_VERSION=${FUSEKI_VERSION:-3.8.0}
fusekiurl="http://mirror.netinch.com/pub/apache/jena/binaries/apache-jena-fuseki-$FUSEKI_VERSION.tar.gz"

if [ ! -f "apache-jena-fuseki-$FUSEKI_VERSION/fuseki-server" ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz "$fusekiurl"
  tar -zxvf fuseki-dist.tar.gz
fi

cd "apache-jena-fuseki-$FUSEKI_VERSION"
./fuseki-server --config ../fuseki-assembler.ttl &
until curl --output /dev/null --silent --head --fail http://localhost:3030; do
  printf '.'
  sleep 2
done

for fn in ../test-vocab-data/*.ttl; do
  name=$(basename "${fn}" .ttl)
  $(./bin/s-put http://localhost:3030/ds/data "http://www.skosmos.skos/$name/" "$fn")
done

cd ..
