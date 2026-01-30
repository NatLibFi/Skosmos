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
    curl -X PUT -H "Content-Type: text/turtle" --data-binary "@$fn" "http://localhost:9030/skosmos/data?graph=http://www.skosmos.skos/$name/"
    echo
done

# Restarting fuseki-cache to avoid issue with backend connection
# due to varnish-cache starts before fuseki is fully ready
docker compose restart fuseki-cache || { echo "Failed to restart fuseki-cache"; exit 1; }
