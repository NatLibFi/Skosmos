#!/bin/bash
# Note: This script must be sourced from within bash, e.g. ". init_fuseki.sh"

FUSEKI_VERSION=${FUSEKI_VERSION:-3.7.0}

if [ "$FUSEKI_VERSION" = "SNAPSHOT" ]; then
	# find out the latest snapshot version and its download URL by parsing Apache directory listings
	snapshotdir="https://repository.apache.org/content/repositories/snapshots/org/apache/jena/jena-fuseki1/"
	latestdir=$(wget -q -O- "$snapshotdir" | grep 'a href=' | cut -d '"' -f 2 | grep SNAPSHOT | tail -n 1)
	FUSEKI_VERSION=$(basename "$latestdir")
	fusekiurl=$(wget -q -O- "$latestdir" | grep 'a href=' | cut -d '"' -f 2 | grep 'distribution\.tar\.gz$' | tail -n 1)
else
	fusekiurl="https://repository.apache.org/content/repositories/releases/org/apache/jena/jena-fuseki1/$FUSEKI_VERSION/jena-fuseki1-$FUSEKI_VERSION-distribution.tar.gz"
fi

if [ ! -f "jena-fuseki1-$FUSEKI_VERSION/fuseki-server" ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz "$fusekiurl"
  tar -zxvf fuseki-dist.tar.gz
fi

cd "jena-fuseki1-$FUSEKI_VERSION"
./fuseki-server --port=13030 --config ../fuseki-assembler.ttl &
until curl --output /dev/null --silent --head --fail http://localhost:13030; do
  printf '.'
  sleep 2
done

for fn in ../test-vocab-data/*.ttl; do
  name=$(basename "${fn}" .ttl)
  $(./s-put http://localhost:13030/ds/data "http://www.skosmos.skos/$name/" "$fn")
done

cd ..
