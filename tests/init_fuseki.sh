#!/bin/bash

# Build and start up containers (skosmos, skosmos-cache, fuseki)
cd ../dockerfiles
docker compose up -d --build

# FIXME: should check that it's up instead of blindly waiting 5 seconds
echo "Waiting for Fuseki to get ready"
sleep 5

for fn in ../tests/test-vocab-data/*.ttl; do
    name=$(basename "${fn}" .ttl)
    echo "Loading test vocabulary $name"
    curl -I -X POST -H Content-Type:text/turtle -T "$fn" -G http://localhost:9030/skosmos/data --data-urlencode graph="http://www.skosmos.skos/$name/"
    echo
done
