if [ ! -f jena-fuseki-1.1.0/fuseki-server ]; then
  echo "fuseki server file not found - downloading it"
  wget --output-document=fuseki-dist.tar.gz http://www.nic.funet.fi/pub/mirrors/apache.org//jena/binaries/jena-fuseki-1.1.0-distribution.tar.gz
  tar -zxvf fuseki-dist.tar.gz
fi

cd jena-fuseki-1.1.0
./fuseki-server --file ../turtle/search.ttl /ds

