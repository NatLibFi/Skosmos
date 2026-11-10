# Guidance for AI coding agents

## Project overview

Skosmos is a web application for browsing controlled vocabularies (SKOS) served from a SPARQL endpoint. It is written in PHP, uses Twig templates, EasyRdf for SPARQL/RDF access, and also exposes a REST-style API. The web front-end is built with Vue and Bootstrap.

## Directory layout

Most important directories:

* `src/` (PHP application code: controllers, models, views)
* `resource/` (CSS/JS assets, front-end Vue code)
* `tests/` (PHPUnit, Cypress and test data).

## Install

    composer install
    npm install

The tests (PHPUnit and Cypress) require a running Jena Fuseki SPARQL endpoint with the test vocabularies loaded and the Skosmos app container. The containers must be rebuilt after code changes (application code is baked in to the container, not mounted) and also if test vocabulary data changes. Test-only (PHPUnit or Cypress) changes can be done without rebuilding containers. To start/rebuild:

    cd tests && ./init_containers.sh

## Code style

PHP (PSR-12 via PHP-CS-Fixer) — check and fix:

    ./vendor/bin/php-cs-fixer fix src --diff --dry-run --no-interaction
    ./vendor/bin/php-cs-fixer fix src --no-interaction

JavaScript (Standard JS) — check and fix:

    cd resource/js && npx standard *.js
    cd resource/js && npx standard --fix *.js

Both checks are enforced in CI.

## PHPUnit tests (back-end)

    ./vendor/bin/phpunit

## Cypress tests (front-end & accessibility)

    npm run cy:run

## Running checks and tests

Run the exact commands documented above directly, in order. Do not perform extra verification steps (e.g. checking for installed dependencies or running containers) before or between them — the commands are self-sufficient.