Dockerfiles for Skosmos.

## Running with Docker

The following commands will build and tag the image it with `skosmos:test`,
and run the container. The container name is `skosmos-web`, but you can customize
the name, port, and other flags as necessary.

```bash
$ docker build -t skosmos:test . -f Dockerfile.ubuntu
$ docker run -d --rm --name skosmos-web -p 9090:80 skosmos:test
```

Now Skosmos should be available at http://localhost:9090/.

To stop the container:

```bash
$ docker stop skosmos-web
```

The container created is based on the project
[Install Tutorial](https://github.com/NatLibFi/Skosmos/wiki/InstallTutorial).
So it will create a container with Ubuntu, Apache2, PHP, composer, and a version
of Skosmos.

The Apache virtual host configuration is located at `config/000-default.conf`. And
the configuration file used for Skosmos is at `config/config.ttl`. Customize these
two files as necessary.


## Running with docker-compose

The `docker-compose` provided configuration will prepare two containers.
The first one called `skosmos-fuseki`, which uses the `stain/jena-fuseki`
image for Jena, and starts a container with 2GB of memory and `admin`
as the password. The `docker-compose` service name of this container is
`fuseki`.

The second container created is `skosmos-web`, using the same image mentioned
in the previous section. The only difference is that we bind a new Skosmos
configuration `config/config-docker-compose.ttl` on `/var/www/html/config.ttl`.

This `config-docker-compose.ttl` file uses `http://fuseki:3030/ds/sparql` for
`skosmos:sparqlEndpoint`. You can use this example to start Skosmos pointing
to any existing Apache Jena, preferably with the Jena Text extension.

Note that `fuseki:3030` is the internal Docker network socket. To the host
machine, Docker Compose is exposing it as `fuseki:9030` to avoid conflicts
with another existing Apache Jena instance.

```bash
$ docker-compose up -d
```

Now Skosmos should be available at http://localhost:9090/.

To stop:

```bash
$ docker-compose down
```

## License

MIT License
