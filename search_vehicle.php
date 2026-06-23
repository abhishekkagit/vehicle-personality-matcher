<?php

include 'includes/db.php';

$q = $_GET['q'] ?? '';

$stmt = $conn->prepare("
SELECT id, make, model 
FROM vehicle 
WHERE make LIKE ? OR model LIKE ?
LIMIT 5
");

$search = "%$q%";

$stmt->bind_param("ss",$search,$search);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
echo "<div class='search-item' data-id='{$row['id']}'>
{$row['make']} {$row['model']}
</div>";
}