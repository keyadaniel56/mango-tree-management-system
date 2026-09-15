#!/usr/bin/env bash
set -euo pipefail

echo "=== Mango Tree School Management System ==="

if [ ! -f .env ]; then
    cp .env.example .env
fi

sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=schoolapi/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=school/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=school/' .env

docker compose up --build
