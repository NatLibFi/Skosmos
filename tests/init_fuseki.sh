# Note: This script must be sourced from within bash, e.g. ". init_fuseki.sh"

FUSEKI_VERSION=${FUSEKI_VERSION:-1.3.0}

if [ ! -f jena-fuseki1-$FUSEKI_VERSION/fuseki-server ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz http://archive.apache.org/dist/jena/binaries/jena-fuseki1-$FUSEKI_VERSION-distribution.tar.gz 
  tar -zxvf fuseki-dist.tar.gz
fi

cd jena-fuseki1-$FUSEKI_VERSION
./fuseki-server --config ../fuseki-assembler.ttl &
until $(curl --output /dev/null --silent --head --fail http://localhost:3030); do
  printf '.'
  sleep 2
done

for fn in ../test-vocab-data/*.ttl; do
  name=`basename $fn .ttl`
  ./s-put http://localhost:3030/ds/data http://www.skosmos.skos/$name/ $fn
done

cd ..
