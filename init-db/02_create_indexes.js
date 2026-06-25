// 1. We position ourselves on the ruko-database database
const dbInstance = db.getSiblingDB('ruko-database');

print('--- START OF INDEX CREATION ---');

// 2. Creation of the index on the 'name' field for autocompletion
dbInstance.mpivavaka.createIndex({ name: 1 });

print('--- INDEX ON "name" CREATED SUCCESSFULLY ---');