<?php

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $count = $db->query('SELECT COUNT(*) FROM statutes')->fetchColumn();
    echo "Current statutes in database: $count\n";
    
    // Check if there are records with properly generated slugs
    $slugCheck = $db->query('SELECT id, title, slug FROM statutes LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($slugCheck as $row) {
        echo "ID: {$row['id']}, Title: {$row['title']}, Slug: {$row['slug']}\n";
    }
    
    // Check divisions
    $divCount = $db->query('SELECT COUNT(*) FROM statute_divisions')->fetchColumn();
    echo "Current divisions in database: $divCount\n";
    
    // Check provisions  
    $provCount = $db->query('SELECT COUNT(*) FROM statute_provisions')->fetchColumn();
    echo "Current provisions in database: $provCount\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}

?>