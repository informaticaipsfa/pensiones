#!/bin/sh

PGPASSWORD=za63qj2p psql -U postgres -h localhost -d pensiones -w -c "\i '$1.sql';" 

echo "proceso exitoso"
