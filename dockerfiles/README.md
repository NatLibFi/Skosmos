Dockerfiles for Skosmos.

## Prerequisites

The following software versions were tested successfully with
the docker configuration files used in this document.

- Ubuntu Linux jammy 22.04.3 LTS
- Docker version 24.0.5, build ced0996
- Docker Compose version v2.20.2
- Internet connection to download base images and other dependencies
- At least 1G of storage space for the images (~530M for Skosmos,
  ~140M for Jena Fuseki, and ~260M for Varnish cache), or more
  depending on your use of vocabularies and data

## Running with Docker

The following commands will build and tag the image it with `skosmos:test`,
and run the container. The container name is `skosmos-web`, but you can customize
the name, and other flags as necessary. The container will listen to port
`80` at both the host and container since we are using `--net=host` to allow the
Skosmos application to access Fuseki at `http://localhost:3030`. You are free to
modify the command line and the `Dockerfile.ubuntu` and configuration files if you
would like to deploy it differently.

    # NOTE: the container copies the project sources during build, so the
    # context must be the parent directory, i.e. you MUST build the image
    # from the Skosmos source directory, not from $sources/dockerfiles/
    docker build -t skosmos:test . -f dockerfiles/Dockerfile.ubuntu
    docker run -d --rm --name skosmos-web --net=host skosmos:test

Now Skosmos should be available at `http://localhost/`. See the
[section below](#loading-vocabulary-data) to load vocabulary data.

**NOTE**: the Skosmos instance configured in this example setup expects the Fuseki
backend to support the "JenaText" dialect, to have the dataset "skosmos" created
with the vocabulary data, and to be available at `http://localhost:3030`.
For this last requisite you must create a
[Docker network](https://docs.docker.com/network/network-tutorial-standalone/),
use [`--net=host`](https://docs.docker.com/network/host/) or other mechanisms for
that. See the section [Running with docker compose](#running-with-docker-compose)
if you would like to use Docker Compose.

To stop the container:

    docker stop skosmos-web

The container created is based on the project
[Install Tutorial](https://github.com/NatLibFi/Skosmos/wiki/InstallTutorial).
So it will create a container with Ubuntu, Apache2, PHP, composer, and a version
of Skosmos.

The Apache virtual host configuration is located at `config/000-default.conf`. And
the configuration file used for Skosmos is at `config/config.ttl`. Customize these
two files as necessary.

**NOTE**: If you would like to start a Fuseki container to test with Docker only,
without Docker Compose, you can try the following command before loading your
vocabulary data. It starts a container in the same way as our other example with
the `docker compose` command.

    export JENA_4_VERSION=4.8.0

    docker build -t jena-fuseki:$JENA_4_VERSION \
        --build-arg JENA_VERSION=$JENA_4_VERSION \
        --no-cache dockerfiles/jena-fuseki2-docker

    docker run --name fuseki --rm -ti \
        -v $(pwd)/dockerfiles/config/skosmos.ttl:/fuseki/skosmos.ttl \
        -e "JAVA_OPTIONS=-Xmx2g -Xms1g" \
        -p 3030:3030 \
        jena-fuseki:$JENA_4_VERSION \
        --config=/fuseki/skosmos.ttl

    curl -XPOST  http://localhost:3030/skosmos/query -d "query=SELECT ?a WHERE { ?a ?b ?c }"

## Running with docker compose

The `docker compose` provided configuration will prepare three containers.
The first one called `skosmos-fuseki`, which uses the Apache Jena
image for Fuseki, and starts a container with 2 GB of memory. The
`docker compose` service name of this container is `fuseki`.

The second container is the `fuseki-cache`, a Varnish Cache container. It sits
between the `skosmos-fuseki` and the `skosmos-web` (more on this below). The
Varnish Cache container is pre-configured to intercept queries to `fuseki:3030`
keeping the results `gzipped` in the cache for one week.

The last container created is `skosmos-web`, using the same image mentioned
in the [previous section](#running-with-docker). The only difference being
that we bind a new Skosmos configuration `config/config-docker-compose.ttl`
on `/var/www/html/config.ttl`.

This `config-docker-compose.ttl` file uses `http://fuseki-cache:80/skosmos/sparql`
as `skosmos:sparqlEndpoint`, forcing `skosmos-web` to go through the `fuseki-cache`
for a better performance. You can customize this example setup to start Skosmos
pointing to any other existing Apache Jena server, preferably with the Jena Text
extension.

**NOTE**: `fuseki:3030` and `fuseki-cache:80` are from the internal Docker network.
To the host machine Docker Compose is exposing these values as `localhost:3030`
and `localhost:9031` respectively.

To create the containers in this example setup, you can use this command
from the `./dockerfiles/` directory:

    docker compose up -d

Now Skosmos should be available at `http://localhost:9090/` from your
host. See the [section below](#loading-vocabulary-data) to load vocabulary data.

To stop:

    docker compose down

## Loading vocabulary data

After you have your container running, with either Docker or `docker compose`,
you will need to load your vocabulary data.

**NOTE**: In the example below, we use the Fuseki URL `localhost:3030`, which
should work for the Docker setup. If you used `docker compose`, you will have
to use `localhost:9030` instead.

    # load STW vocabulary data
    curl -L -o stw.ttl.zip http://zbw.eu/stw/version/latest/download/stw.ttl.zip
    unzip stw.ttl.zip
    curl -I -X POST -H Content-Type:text/turtle -T stw.ttl -G http://localhost:3030/skosmos/data --data-urlencode graph=http://zbw.eu/stw/
    # load UNESCO vocabulary data
    curl -L -o unescothes.ttl http://skos.um.es/unescothes/unescothes.ttl
    curl -I -X POST -H Content-Type:text/turtle -T unescothes.ttl -G http://localhost:3030/skosmos/data --data-urlencode graph=http://skos.um.es/unescothes/

After you execute these commands successfully, you should be able to use all the
features of Skosmos, such as browsing vocabularies and concepts.
