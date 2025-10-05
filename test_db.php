<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Check if user_tasks table exists
    $tables = \DB::select("SHOW TABLES LIKE 'user_tasks'");
    echo "Table exists: " . (count($tables) > 0 ? 'YES' : 'NO') . "\n";

    if (count($tables) > 0) {
        // Check table structure
        $columns = \DB::select("DESCRIBE user_tasks");
        echo "Table columns:\n";
        foreach ($columns as $column) {
            echo "- " . $column->Field . " (" . $column->Type . ")\n";
        }

        // Check if there are any records
        $count = \DB::table('user_tasks')->count();
        echo "Records in table: " . $count . "\n";

        if ($count > 0) {
            $tasks = \DB::table('user_tasks')->limit(5)->get();
            echo "Sample records:\n";
            foreach ($tasks as $task) {
                echo "- Task ID: " . $task->task_id . ", User: " . $task->user_id . ", Status: " . $task->status . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
