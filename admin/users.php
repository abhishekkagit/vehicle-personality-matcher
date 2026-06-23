<?php

require 'includes/auth.php';
require 'includes/db.php';
require 'includes/header.php';
?>
<div class="admin-wrapper">

<?php require 'includes/sidebar.php'; ?>

<main class="main-area">
<?php

/* =========================
   SEARCH
========================= */

$emailSearch = $_GET['email'] ?? '';

$where = "WHERE 1=1";

if($emailSearch){

$emailSearch = $conn->real_escape_string($emailSearch);

$where .= " AND email LIKE '%$emailSearch%'";

}
/* =========================
   CHANGE ROLE
========================= */

if(isset($_GET['promote'])){

$id = (int)$_GET['promote'];

$conn->query("UPDATE users SET role='admin' WHERE user_id=$id");

header("Location: users.php");
exit;

}

if(isset($_GET['demote'])){

$id = (int)$_GET['demote'];

$conn->query("UPDATE users SET role='user' WHERE user_id=$id");

header("Location: users.php");
exit;

}
/* =========================
DELETE USER
========================= */

if(isset($_GET['delete'])){

$id = (int)$_GET['delete'];

$conn->query("DELETE FROM users WHERE user_id=$id");

header("Location: users.php");
exit;

}


/* =========================
FETCH USERS
========================= */

$result = $conn->query("
SELECT user_id,name,email,role,is_verified,created_at
FROM users
$where
ORDER BY user_id DESC
");
?>
<div class="card">

<h3>Users</h3>
<div class="filter-bar">

<form method="GET" style="display:flex; gap:12px;">

<input 
type="text"
name="email"
value="<?php echo htmlspecialchars($emailSearch); ?>"
placeholder="Search by email"
class="filter-input"
>

<button type="submit" class="btn-primary">
Search
</button>

</form>

</div>

<div class="table-wrapper">

<table class="data-table">

<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Role</th>

<th>Joined</th>

</tr>
</thead>

<tbody>

<?php while($user = $result->fetch_assoc()): ?>

<tr>

<td><?php echo $user['user_id']; ?></td>

<td><?php echo htmlspecialchars($user['name']); ?></td>

<td><?php echo htmlspecialchars($user['email']); ?></td>

<td>
<span class="type-tag <?php echo $user['role']; ?>">
<?php echo ucfirst($user['role']); ?>
</span>
</td>



<td><?php echo $user['created_at']; ?></td>

<td>




</td>
</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>
</main>
</div>

<?php include 'includes/footer.php'; ?>