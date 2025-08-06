<?php

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    
    $db->exec('DELETE FROM statute_provisions');
    echo "Deleted all statute provisions\n";
    
    $db->exec('DELETE FROM statute_divisions');
    echo "Deleted all statute divisions\n";
    
    $db->exec('DELETE FROM statutes');
    echo "Deleted all statutes\n";
    
    echo "Database cleared successfully.\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

?>