#!/bin/bash

# Build and start up containers (skosmos, skosmos-cache, fuseki)
docker compose up -d --build

echo "Waiting for Fuseki to get ready"
while true; do
    if curl -fs http://localhost:9030/skosmos/ -o /dev/null; then
        echo "Fuseki is up!"
        break
    else
        echo "...waiting..."
        sleep 1
    fi
done

for fn in ../tests/test-vocab-data/*.ttl; do
    name=$(basename "${fn}" .ttl)
    echo "Loading test vocabulary $name"
    curl -I -X POST -H Content-Type:text/turtle -T "$fn" -G http://localhost:9030/skosmos/data --data-urlencode graph="http://www.skosmos.skos/$name/"
    echo
done
