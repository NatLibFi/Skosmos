#!/bin/bash
# Note: This script must be sourced from within bash, e.g. ". init_fuseki.sh"

FUSEKI_VERSION=${FUSEKI_VERSION:-3.14.0}

if [ "$FUSEKI_VERSION" = "SNAPSHOT" ]; then
    # find out the latest snapshot version and its download URL by parsing Apache directory listings
    snapshotdir="https://repository.apache.org/content/repositories/snapshots/org/apache/jena/apache-jena-fuseki/"
    latestdir=$(wget -q -O- "$snapshotdir" | grep 'a href=' | cut -d '"' -f 2 | grep SNAPSHOT | tail -n 1)
    FUSEKI_VERSION=$(basename "$latestdir")
    fusekiurl=$(wget -q -O- "$latestdir" | grep 'a href=' | cut -d '"' -f 2 | grep '\.tar\.gz$' | tail -n 1)
else
    fusekiurl="https://repository.apache.org/content/repositories/releases/org/apache/jena/apache-jena-fuseki/$FUSEKI_VERSION/apache-jena-fuseki-$FUSEKI_VERSION.tar.gz"
fi

if [ ! -f "apache-jena-fuseki-$FUSEKI_VERSION/fuseki-server" ]; then
    echo "fuseki server file not found - downloading it"
    wget --quiet --output-document=fuseki-dist.tar.gz "$fusekiurl"
    tar -zxvf fuseki-dist.tar.gz
fi

cd "apache-jena-fuseki-$FUSEKI_VERSION"
chmod +x fuseki-server bin/s-put
./fuseki-server --port=13030 --config ../fuseki-assembler.ttl &
until curl --output /dev/null --silent --head --fail http://localhost:13030; do
    printf '.'
    sleep 2
done

for fn in ../test-vocab-data/*.ttl; do
    name=$(basename "${fn}" .ttl)
    $(./bin/s-put http://localhost:13030/skosmos-test/data "http://www.skosmos.skos/$name/" "$fn")
done

cd ..

