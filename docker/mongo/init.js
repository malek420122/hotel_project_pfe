// MongoDB initialization script
db = db.getSiblingDB('hotelease');

// Create application user
db.createUser({
  user: 'hotelease_app',
  pwd: 'hotelease_app_password',
  roles: [{ role: 'readWrite', db: 'hotelease' }]
});

// Create indexes
db.users.createIndex({ email: 1 }, { unique: true });
db.users.createIndex({ role: 1 });
db.hotels.createIndex({ ville: 1, etoiles: 1 });
db.hotels.createIndex({ latitude: 1, longitude: 1 });
db.chambres.createIndex({ hotelId: 1, estDisponible: 1 });
db.reservations.createIndex({ clientId: 1, statut: 1 });
db.reservations.createIndex({ hotelId: 1, dateArrivee: 1, dateDepart: 1 });
db.reservations.createIndex({ reference: 1 }, { unique: true, sparse: true });
db.paiements.createIndex({ reservationId: 1 });
db.avis.createIndex({ hotelId: 1, statut: 1 });

print('Database initialization complete!');
