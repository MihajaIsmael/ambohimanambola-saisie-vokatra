#!/bin/bash
# 01_import_mpivavaka.sh

# Wait for Mongo instance
sleep 3

# Step 1 : Importation
echo "Begining of automatic import..."
mongoimport --host localhost \
            --db ruko-database \
            --collection mpivavaka \
            --type csv \
            --fields id,name,address \
            --file /docker-entrypoint-initdb.d/mpivavaka.csv


# Step 2 : Cleaning script
echo "Data cleaning..."
mongosh ruko-database --eval '
    db.mpivavaka.find().forEach(function(doc) {
        if (doc.id) {
            var cleanIdString = doc.id.toString().replace(/\D/g, "");
            var finalId = parseInt(cleanIdString, 10);

            if (!isNaN(finalId)) {
                db.mpivavaka.updateOne(
                    { _id: doc._id },
                    { $set: { id: finalId } }
                );
            } else {
                db.mpivavaka.deleteOne({ _id: doc._id });
            }
        } else {
            db.mpivavaka.deleteOne({ _id: doc._id });
        }
    });
'

echo "Importation completed successfully!"