if [ ! -f jena-fuseki-1.1.1/fuseki-server ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz http://www.nic.funet.fi/pub/mirrors/apache.org//jena/binaries/jena-fuseki-1.1.1-distribution.tar.gz
  tar -zxvf fuseki-dist.tar.gz
fi

cd jena-fuseki-1.1.1
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
