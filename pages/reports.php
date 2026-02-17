<?php
ob_start();

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbPath = __DIR__ . '/../db/routers.db';
?>

<div class="content-wrapper">
<section class="content">
<div class="container-fluid">

<h1 class="mt-4 mb-4 text-center">SQLite Database Inspector</h1>

<?php
if (!file_exists($dbPath)) {
    echo "<div class='alert alert-danger'>Database file not found at: $dbPath</div>";
    include __DIR__ . '/../components/footer.php';
    ob_end_flush();
    exit;
}

echo "<div class='card shadow mb-4'>
        <div class='card-body'>
            <strong>Database Path:</strong> $dbPath<br>
            <strong>File Size:</strong> " . round(filesize($dbPath) / 1024, 2) . " KB
        </div>
      </div>";

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $version = $db->query("SELECT sqlite_version()")->fetchColumn();

    echo "<div class='card shadow mb-4'>
            <div class='card-body'>
                <strong class='text-success'>Connection Status:</strong> Connected Successfully<br>
                <strong>SQLite Version:</strong> $version
            </div>
          </div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Connection Failed: " . $e->getMessage() . "</div>";
    include __DIR__ . '/../components/footer.php';
    ob_end_flush();
    exit;
}

// Get tables
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
             ->fetchAll(PDO::FETCH_ASSOC);

if (!$tables) {
    echo "<div class='alert alert-warning'>No tables found in this database.</div>";
}

foreach ($tables as $table) {

    $tableName = $table['name'];
    $rowCount = $db->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();

    echo "<div class='card shadow mb-4'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'>Table: $tableName</h5>
            </div>
            <div class='card-body'>
                <p><strong>Row Count:</strong> $rowCount</p>";

    // Columns
    $columns = $db->query("PRAGMA table_info($tableName)")
                  ->fetchAll(PDO::FETCH_ASSOC);

    echo "<h6>Columns</h6>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered table-striped'>";
    echo "<thead class='thead-dark'>
            <tr>
                <th>Column</th>
                <th>Type</th>
                <th>Not Null</th>
                <th>Default</th>
                <th>Primary Key</th>
            </tr>
          </thead><tbody>";

    foreach ($columns as $col) {
        echo "<tr>
                <td>{$col['name']}</td>
                <td>{$col['type']}</td>
                <td>" . ($col['notnull'] ? 'YES' : 'NO') . "</td>
                <td>{$col['dflt_value']}</td>
                <td>" . ($col['pk'] ? 'YES' : 'NO') . "</td>
              </tr>";
    }

    echo "</tbody></table></div>";

    // Sample Data
    if ($rowCount > 0) {
        $rows = $db->query("SELECT * FROM $tableName LIMIT 5")
                   ->fetchAll(PDO::FETCH_ASSOC);

        echo "<h6 class='mt-4'>Sample Data (First 5 Rows)</h6>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-bordered table-striped'><thead><tr>";

        foreach (array_keys($rows[0]) as $header) {
            echo "<th>$header</th>";
        }

        echo "</tr></thead><tbody>";

        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table></div>";
    }

    echo "</div></div>";
}
?>

</div>
</section>
</div>

<?php
include __DIR__ . '/../components/footer.php';
ob_end_flush();
?>
