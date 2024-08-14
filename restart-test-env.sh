#!/bin/bash

echo "Restarts the test environment and reads the data from the host system"
echo ""

cd tests
docker compose down
docker system prune -f
./init_containers.sh 
cd ..
npx cypress open


