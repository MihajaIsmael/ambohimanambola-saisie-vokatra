#!/bin/bash
sleep 3
mongoimport --host localhost --db ruko-database --collection mpivavaka --type csv --headerline --file /docker-entrypoint-initdb.d/mpivavaka.csv