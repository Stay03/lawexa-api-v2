<?php

// Test script to verify similar cases functionality
$pdo = new PDO('sqlite:database/database.sqlite');

// Get total count
$count = $pdo->query('SELECT COUNT(*) FROM similar_cases')->fetchColumn();
echo "Total similar case relationships: $count\n";

// Get sample relationships
$sample = $pdo->query('
    SELECT sc.case_id, sc.similar_case_id, cc1.title as case_title, cc2.title as similar_case_title
    FROM similar_cases sc
    JOIN court_cases cc1 ON sc.case_id = cc1.id
    JOIN court_cases cc2 ON sc.similar_case_id = cc2.id
    LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);

echo "\nSample relationships:\n";
foreach($sample as $row) {
    echo "  Case {$row['case_id']}: '{$row['case_title']}'\n";
    echo "    <-> Case {$row['similar_case_id']}: '{$row['similar_case_title']}'\n\n";
}

// Test bidirectional relationships
echo "Testing bidirectional relationships for case ID 1:\n";
$bidirectional = $pdo->query('
    SELECT 
        CASE 
            WHEN sc.case_id = 1 THEN sc.similar_case_id
            WHEN sc.similar_case_id = 1 THEN sc.case_id
        END as related_case_id,
        cc.title
    FROM similar_cases sc
    JOIN court_cases cc ON (
        (sc.case_id = 1 AND cc.id = sc.similar_case_id) OR
        (sc.similar_case_id = 1 AND cc.id = sc.case_id)
    )
    WHERE sc.case_id = 1 OR sc.similar_case_id = 1
    LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);

foreach($bidirectional as $row) {
    echo "  Case 1 <-> Case {$row['related_case_id']}: '{$row['title']}'\n";
}

?>