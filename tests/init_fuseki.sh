if [ ! -f jena-fuseki-1.1.0/fuseki-server ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz http://www.nic.funet.fi/pub/mirrors/apache.org//jena/binaries/jena-fuseki-1.1.0-distribution.tar.gz
  tar -zxvf fuseki-dist.tar.gz
fi

cd jena-fuseki-1.1.0
./fuseki-server --config ../turtle/assembler.ttl &
until $(curl --output /dev/null --silent --head --fail http://localhost:3030); do
  printf '.'
  sleep 2
  done
./s-put http://localhost:3030/ds/data http://www.skosmos.skos/test/ ../turtle/search.ttl

