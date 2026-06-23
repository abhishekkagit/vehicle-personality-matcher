<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'includes/header.php';
include 'includes/db.php';

$type = $_GET['type'] ?? 'car';
$similarId = (int) ($_GET['similar'] ?? 0);
$limit = 6;
$isSimilarMode = false;
$title = ($type === 'bike') ? 'Browse Bikes' : 'Browse Cars';
$headerText = 'Explore our collection';
$sourceVehicle = null;
$similarRows = [];
$matchLabel = ($type === 'bike') ? 'Category' : 'Body Type';
$matchValue = '';
$comparePage = ($type === 'bike') ? 'compare_bikes.php' : 'compare.php';

function getVehicleNameForList(array $row, string $type): string
{
    return $type === 'bike'
        ? trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''))
        : trim(($row['make'] ?? '') . ' ' . ($row['model'] ?? ''));
}

function getVehicleSubtitleForList(array $row, string $type): string
{
    if ($type === 'bike') {
        return trim((string) ($row['category'] ?? ''));
    }

    return trim((string) (($row['body_type'] ?? '') ?: ($row['size_class'] ?? '')));
}

function getVehicleImageForList(mysqli $conn, array $row, string $type): string
{
    if ($type === 'bike') {
        return !empty($row['image_url'])
            ? $row['image_url']
            : 'assets/images/bikes/placeholder.jpg';
    }

    $imgStmt = $conn->prepare("
        SELECT image_path
        FROM vehicle_images
        WHERE vehicle_id = ?
        ORDER BY created_at ASC
        LIMIT 1
    ");
    $imgStmt->bind_param("i", $row['id']);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    $imgRow = $imgRes->fetch_assoc();

    return $imgRow['image_path'] ?? 'assets/images/vehicles/default.jpg';
}

function formatCarPriceRange(array $row): string
{
    if (($row['price_min'] ?? null) !== null && ($row['price_max'] ?? null) !== null) {
        return 'Rs ' . number_format((float) $row['price_min']) . ' - Rs ' . number_format((float) $row['price_max']);
    }

    if (!empty($row['budget_range'])) {
        return (string) $row['budget_range'];
    }

    return 'Price not available';
}

function buildSimilarStats(array $row, string $type): array
{
    $stats = [];

    if ($type === 'bike') {
        if (!empty($row['price_range'])) {
            $stats['Price Range'] = (string) $row['price_range'];
        }
        if (!empty($row['displacement_cc'])) {
            $stats['Engine'] = $row['displacement_cc'] . ' cc';
        }
        if (!empty($row['power_hp'])) {
            $stats['Power'] = round((float) $row['power_hp']) . ' HP';
        }

        return array_slice($stats, 0, 3, true);
    }

    $stats['Budget'] = formatCarPriceRange($row);

    if (!empty($row['city_mpg'])) {
        $stats['City MPG'] = $row['city_mpg'] . ' MPG';
    } elseif (!empty($row['size_class'])) {
        $stats['Size Class'] = (string) $row['size_class'];
    }

    if (!empty($row['seating_capacity'])) {
        $stats['Seats'] = (string) $row['seating_capacity'];
    }

    return array_slice($stats, 0, 3, true);
}

if ($similarId > 0) {
    if ($type === 'bike') {
        $stmt = $conn->prepare("
            SELECT *
            FROM bikes
            WHERE id = ?
        ");
        $stmt->bind_param("i", $similarId);
        $stmt->execute();
        $sourceVehicle = $stmt->get_result()->fetch_assoc();

        if ($sourceVehicle) {
            $matchValue = (string) ($sourceVehicle['category'] ?? '');

            $stmt = $conn->prepare("
                SELECT *
                FROM bikes
                WHERE category = ? AND id != ?
                ORDER BY id DESC
                LIMIT ?
            ");
            $stmt->bind_param("sii", $matchValue, $similarId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $similarRows[] = $row;
            }
        }
    } else {
        $stmt = $conn->prepare("
            SELECT *
            FROM vehicle
            WHERE id = ?
        ");
        $stmt->bind_param("i", $similarId);
        $stmt->execute();
        $sourceVehicle = $stmt->get_result()->fetch_assoc();

        if ($sourceVehicle) {
            $matchValue = (string) ($sourceVehicle['body_type'] ?? '');

            $stmt = $conn->prepare("
                SELECT *
                FROM vehicle
                WHERE body_type = ? AND id != ?
                ORDER BY id DESC
                LIMIT ?
            ");
            $stmt->bind_param("sii", $matchValue, $similarId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $similarRows[] = $row;
            }
        }
    }

    if ($sourceVehicle) {
        $isSimilarMode = true;
        $title = ($type === 'bike') ? 'Similar Bikes' : 'Similar Cars';
        $headerText = ($type === 'bike')
            ? 'More options from the same category so your shortlist stays focused.'
            : 'More options with the same body style so the recommendations stay relevant.';
    }
}
?>

<div class="container vehicles-page<?= $isSimilarMode ? ' similar-mode-page' : '' ?>">

  <div class="vehicles-header<?= $isSimilarMode ? ' similar-results-header' : '' ?>">
    <?php if ($isSimilarMode): ?>
      <span class="section-kicker">Curated Similar Picks</span>
    <?php endif; ?>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($headerText) ?></p>
  </div>

  <?php if ($isSimilarMode && $sourceVehicle): ?>
    <div class="similar-context-card">
      <div class="similar-context-copy">
        <span class="similar-context-label">Based on your current selection</span>
        <h2><?= htmlspecialchars(getVehicleNameForList($sourceVehicle, $type)) ?></h2>
        <p>
          These recommendations stay close to what you just viewed by matching the same
          <?= htmlspecialchars(strtolower($matchLabel)) ?>.
        </p>
      </div>

      <div class="similar-context-side">
        <span class="similar-context-chip"><?= htmlspecialchars($matchLabel) ?>: <?= htmlspecialchars($matchValue ?: 'Related') ?></span>
        <a
          href="vehicle-details.php?type=<?= urlencode($type) ?>&id=<?= $similarId ?>"
          class="similar-back-link"
        >
          Back to current vehicle
        </a>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$isSimilarMode): ?>
    <div class="search-filter">
      <input id="brandBox" class="search-box" placeholder="Brand">
      <input id="modelBox" class="search-box" placeholder="Model">
      <button id="searchBtn" class="btn btn-primary">Search</button>
    </div>
  <?php endif; ?>

  <div class="vehicle-grid<?= $isSimilarMode ? ' similar-results-grid' : '' ?>" id="vehicleGrid">

    <?php if ($isSimilarMode): ?>
      <?php if (empty($similarRows)): ?>
        <div class="similar-empty-state">
          <h3>No close matches yet</h3>
          <p>We could not find more vehicles in the same <?= htmlspecialchars(strtolower($matchLabel)) ?> right now.</p>
        </div>
      <?php else: ?>
        <?php foreach ($similarRows as $row): ?>
          <?php
          $vehicleName = getVehicleNameForList($row, $type);
          $vehicleSubtitle = getVehicleSubtitleForList($row, $type);
          $vehicleImage = getVehicleImageForList($conn, $row, $type);
          $stats = buildSimilarStats($row, $type);
          ?>

          <article class="similar-vehicle-card">
            <a
              href="vehicle-details.php?type=<?= urlencode($type) ?>&id=<?= (int) $row['id'] ?>"
              class="similar-card-media"
            >
              <img
                src="<?= htmlspecialchars($vehicleImage) ?>"
                alt="<?= htmlspecialchars($vehicleName) ?>"
                class="similar-card-image"
                loading="lazy"
              >
            </a>

            <div class="similar-card-body">
              <div class="similar-card-top">
                <span class="similar-match-badge">Same <?= htmlspecialchars(strtolower($matchLabel)) ?></span>
                <?php if ($matchValue !== ''): ?>
                  <span class="similar-value-badge"><?= htmlspecialchars($matchValue) ?></span>
                <?php endif; ?>
              </div>

              <h3><?= htmlspecialchars($vehicleName) ?></h3>

              <?php if ($vehicleSubtitle !== ''): ?>
                <p class="similar-card-subtitle"><?= htmlspecialchars($vehicleSubtitle) ?></p>
              <?php endif; ?>

              <?php if (!empty($stats)): ?>
                <div class="similar-stats">
                  <?php foreach ($stats as $label => $value): ?>
                    <div class="similar-stat">
                      <span><?= htmlspecialchars($label) ?></span>
                      <strong><?= htmlspecialchars((string) $value) ?></strong>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="similar-card-actions">
                <a
                  href="vehicle-details.php?type=<?= urlencode($type) ?>&id=<?= (int) $row['id'] ?>"
                  class="similar-btn similar-btn-primary"
                >
                  View Details
                </a>
                <a
                  href="<?= htmlspecialchars($comparePage) ?>?v1=<?= $similarId ?>&v2=<?= (int) $row['id'] ?>"
                  class="similar-btn similar-btn-secondary"
                >
                  Compare
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>

  </div>

  <?php if (!$isSimilarMode): ?>
    <div style="text-align:center;margin-top:40px">
      <button id="loadMoreBtn" class="btn btn-outline">Load More</button>
    </div>
  <?php endif; ?>

</div>

<?php if (!$isSimilarMode): ?>
<script>
const grid = document.getElementById("vehicleGrid");
const loadBtn = document.getElementById("loadMoreBtn");
const brandBox = document.getElementById("brandBox");
const modelBox = document.getElementById("modelBox");
const searchBtn = document.getElementById("searchBtn");

let offset = 0;
let mode = "browse";
let previewTimer = null;
let wasTyping = false;

function resetLoadMore() {
  loadBtn.style.display = "inline-block";
  loadBtn.disabled = false;
  loadBtn.innerText = "Load More";
}

function loadBrowse(reset = false) {
  mode = "browse";
  resetLoadMore();

  if (reset) {
    offset = 0;
    grid.innerHTML = "";
  }

  fetch("load-more.php", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: `offset=${offset}&type=<?= $type ?>`
  })
  .then(r => r.text())
  .then(html => {
    if (!html.trim()) {
      loadBtn.disabled = true;
      loadBtn.innerText = "No more vehicles";
      return;
    }

    grid.insertAdjacentHTML("beforeend", html);
    offset += <?= $limit ?>;
  });
}

function previewSearch() {
  const brand = brandBox.value.trim();
  const model = modelBox.value.trim();

  if (!brand && !model) {
    loadBrowse(true);
    return;
  }

  clearTimeout(previewTimer);
  previewTimer = setTimeout(() => {
    mode = "preview";
    loadBtn.style.display = "none";

    fetch("search_preview.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: `brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}&type=<?= $type ?>`
    })
    .then(r => r.text())
    .then(html => {
      if (html.trim()) {
        grid.innerHTML = html;
      }
    });
  }, 300);
}

function forceReloadIfCleared() {
  const brand = brandBox.value.trim();
  const model = modelBox.value.trim();

  if (brand || model) {
    wasTyping = true;
    return;
  }

  if (wasTyping) {
    wasTyping = false;
    location.reload();
  }
}

loadBrowse(true);

loadBtn.addEventListener("click", () => {
  if (mode !== "browse") return;
  loadBrowse();
});

brandBox.addEventListener("input", previewSearch);
modelBox.addEventListener("input", previewSearch);

searchBtn.addEventListener("click", () => {
  const brand = brandBox.value.trim();
  const model = modelBox.value.trim();

  if (!brand && !model) {
    loadBrowse(true);
    return;
  }

  mode = "search";
  grid.innerHTML = "<p>Searching...</p>";
  loadBtn.style.display = "none";

  fetch("search_vehicles.php", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: `brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}&type=<?= $type ?>`
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === "fetching") {
      grid.innerHTML = "<p>Fetching from API...</p>";
      setTimeout(() => searchBtn.click(), 2000);
      return;
    }

    if (data.status === "found") {
      grid.innerHTML = data.html;
    } else {
      grid.innerHTML = "<p>No results found</p>";
    }
  });
});

brandBox.addEventListener("input", forceReloadIfCleared);
modelBox.addEventListener("input", forceReloadIfCleared);
brandBox.addEventListener("keyup", forceReloadIfCleared);
modelBox.addEventListener("keyup", forceReloadIfCleared);
brandBox.addEventListener("change", forceReloadIfCleared);
modelBox.addEventListener("change", forceReloadIfCleared);
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
