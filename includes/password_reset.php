<?php

function ensurePasswordResetTable(mysqli $conn): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_user_id (user_id),
            INDEX idx_password_resets_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new RuntimeException('Unable to initialize password reset storage.');
    }

    $initialized = true;
}

function passwordResetColumnExists(mysqli $conn, string $columnName): bool
{
    $safeColumnName = $conn->real_escape_string($columnName);
    $result = $conn->query("SHOW COLUMNS FROM password_resets LIKE '{$safeColumnName}'");

    return $result && $result->num_rows > 0;
}

function getPasswordResetTokenColumn(mysqli $conn): string
{
    ensurePasswordResetTable($conn);

    if (passwordResetColumnExists($conn, 'token_hash')) {
        return 'token_hash';
    }

    if (passwordResetColumnExists($conn, 'token')) {
        return 'token';
    }

    throw new RuntimeException('Password reset table is missing a token column.');
}

function hasPasswordResetUsedColumn(mysqli $conn): bool
{
    ensurePasswordResetTable($conn);

    return passwordResetColumnExists($conn, 'used');
}

function buildAppBaseUrl(): string
{
    $httpsEnabled = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $httpsEnabled ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/'));

    if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
        $basePath = '';
    }

    return $scheme . '://' . $host . rtrim($basePath, '/');
}

function isLocalDevelopmentHost(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = explode(':', $host)[0];

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function createPasswordResetRequest(mysqli $conn, int $userId): array
{
    ensurePasswordResetTable($conn);
    $tokenColumn = getPasswordResetTokenColumn($conn);

    $delete = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $delete->bind_param("i", $userId);
    $delete->execute();

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAtResult = $conn->query("SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) AS expires_at");

    if (!$expiresAtResult) {
        throw new RuntimeException('Unable to calculate reset expiration time.');
    }

    $expiresAtRow = $expiresAtResult->fetch_assoc();
    $expiresAt = $expiresAtRow['expires_at'] ?? null;

    if (!$expiresAt) {
        throw new RuntimeException('Unable to read reset expiration time.');
    }

    $insert = $conn->prepare(
        "INSERT INTO password_resets (user_id, {$tokenColumn}, expires_at) VALUES (?, ?, ?)"
    );
    $insert->bind_param("iss", $userId, $tokenHash, $expiresAt);

    if (!$insert->execute()) {
        throw new RuntimeException('Unable to create password reset request.');
    }

    return [
        'token' => $token,
        'expires_at' => $expiresAt,
    ];
}

function findPasswordResetRequest(mysqli $conn, string $token): ?array
{
    ensurePasswordResetTable($conn);
    $tokenColumn = getPasswordResetTokenColumn($conn);
    $hasUsedColumn = hasPasswordResetUsedColumn($conn);

    $tokenHash = hash('sha256', $token);
    $usedFilter = $hasUsedColumn ? " AND pr.used = 0" : "";
    $stmt = $conn->prepare(
        "SELECT pr.id, pr.user_id, pr.expires_at, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.user_id = pr.user_id
         WHERE pr.{$tokenColumn} = ? AND pr.expires_at >= NOW(){$usedFilter}
         LIMIT 1"
    );
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset = $result->fetch_assoc();

    return $reset ?: null;
}

function deletePasswordResetRequestsForUser(mysqli $conn, int $userId): void
{
    ensurePasswordResetTable($conn);

    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
