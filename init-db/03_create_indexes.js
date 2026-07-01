// 1. We position ourselves on the ruko-database database
const dbInstance = db.getSiblingDB('ruko-database');

print('--- START OF INDEX CREATION ---');

// 2. Add index on the 'barcode' and 'product_code' fields for unique key
dbInstance.vokatra.createIndex({ barcode: 1 }, { unique: true });
dbInstance.vokatra.createIndex({ product_code: 1 }, { unique: true });
dbInstance.vokatra.createIndex({ event_setting_id: 1 })
dbInstance.vokatra.createIndex({ event_setting_id: 1, printed_at: -1 })

print('--- INDEX ON "barcode" AND "product_code" CREATED SUCCESSFULLY ---');