
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

include 'includes/db.php';

$q = $_GET['q'] ?? '';

if(strlen($q) < 2){
exit;
}

$stmt = $conn->prepare("
SELECT id, brand, model
FROM bikes
WHERE brand LIKE ? OR model LIKE ?
LIMIT 10
");

$search = "%".$q."%";

$stmt->bind_param("ss",$search,$search);
$stmt->execute();

$res = $stmt->get_result();

while($row = $res->fetch_assoc()){

$name = $row['brand']." ".$row['model'];
$id = $row['id'];

echo "<div class='search-item' data-id='{$id}'>";
echo htmlspecialchars($name);
echo "</div>";

}
?>
