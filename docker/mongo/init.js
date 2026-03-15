// MongoDB initialization script
db = db.getSiblingDB('hotelease');

// Create application user
db.createUser({
  user: 'hotelease_app',
  pwd: 'hotelease_app_password',
  roles: [{ role: 'readWrite', db: 'hotelease' }]
});

// Create indexes
db.utilisateurs.createIndex({ email: 1 }, { unique: true });
db.hotels.createIndex({ ville: 1, etoiles: 1 });
db.hotels.createIndex({ location: '2dsphere' });
db.chambres.createIndex({ hotel_id: 1, statut: 1 });
db.reservations.createIndex({ client_id: 1, statut: 1 });
db.reservations.createIndex({ hotel_id: 1, dateArrivee: 1, dateDepart: 1 });
db.reservations.createIndex({ reference: 1 }, { unique: true, sparse: true });
db.paiements.createIndex({ reservation_id: 1 });
db.avis.createIndex({ hotel_id: 1, statut: 1 });

print('Database initialization complete!');
