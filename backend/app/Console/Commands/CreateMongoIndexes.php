<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use MongoDB\Client;

class CreateMongoIndexes extends Command
{
    protected $signature = 'mongo:create-indexes';
    protected $description = 'Create MongoDB text indexes for avis and indexes for user activities';

    public function handle()
    {
        $dsn = env('MONGO_DSN', env('MONGO_URL', 'mongodb://127.0.0.1:27017'));
        $dbName = env('MONGO_DB', 'hotel_project');
        $this->info("Connecting to MongoDB: {$dsn} (db={$dbName})");

        $client = new Client($dsn);
        $db = $client->selectDatabase($dbName);

        $this->info('Creating text index on avis.commentaire...');
        try {
            $db->avis->createIndex(['commentaire' => 'text'], ['name' => 'commentaire_text_idx']);
            $this->info('Created text index on avis.commentaire');
        } catch (\Throwable $e) {
            $this->error('Could not create text index on avis: ' . $e->getMessage());
        }

        $this->info('Creating indexes on user_activities...');
        try {
            $db->user_activities->createIndex(['user_id' => 1, 'created_at' => -1, 'action_type' => 1], ['name' => 'user_time_action_idx']);
            $db->user_activities->createIndex(['user_id' => 1, 'hotel_id' => 1, 'created_at' => -1], ['name' => 'user_hotel_time_idx']);
            $this->info('Created index user_time_action_idx on user_activities');
            $this->info('Created index user_hotel_time_idx on user_activities');
        } catch (\Throwable $e) {
            $this->error('Could not create index on user_activities: ' . $e->getMessage());
        }

        $this->info('Done.');
    }
}
