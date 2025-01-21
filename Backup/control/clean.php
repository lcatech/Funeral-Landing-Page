<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Review Tributes by Name</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; }
        .success { color: green; }
        .group { margin: 20px 0; padding: 10px; border: 1px solid #ddd; }
        .group h3 { margin-top: 0; background: #f5f5f5; padding: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        pre { white-space: pre-wrap; margin: 0; }
        .actions { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Review Tributes by Name</h1>";

try {
    require_once __DIR__ . '/../core/db_connection.php';
    echo "<p class='success'>Successfully connected to database.</p>";

    global $conn;
    if ($conn->ping()) {
        echo "<p class='success'>Database connection is working.</p>";

        // First, get all names that have multiple entries
        $query = "
            SELECT name, COUNT(*) as count
            FROM tributes
            GROUP BY name
            HAVING COUNT(*) > 1
            ORDER BY name ASC
        ";
        
        $result = $conn->query($query);
        
        if ($result === false) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $duplicateCount = $result->num_rows;
        echo "<h2>Found {$duplicateCount} names with multiple entries</h2>";
        
        if ($duplicateCount > 0) {
            // Process cleanup if requested
            if (isset($_POST['delete']) && isset($_POST['entries'])) {
                $conn->begin_transaction();
                
                try {
                    foreach ($_POST['entries'] as $id) {
                        // Get reference before deletion
                        $stmt = $conn->prepare("SELECT reference FROM tributes WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        $stmt->execute();
                        $ref_result = $stmt->get_result();
                        $ref_row = $ref_result->fetch_assoc();
                        
                        if ($ref_row) {
                            // Delete from processed_emails first
                            $deleteEmail = $conn->prepare("DELETE FROM processed_emails WHERE reference = ?");
                            $deleteEmail->bind_param("s", $ref_row['reference']);
                            $deleteEmail->execute();
                            
                            // Then delete from tributes
                            $deleteTribute = $conn->prepare("DELETE FROM tributes WHERE id = ?");
                            $deleteTribute->bind_param("i", $id);
                            $deleteTribute->execute();
                            
                            echo "<p class='success'>Deleted tribute ID: " . htmlspecialchars($id) . "</p>";
                        }
                    }
                    
                    $conn->commit();
                    echo "<p class='success'>Selected entries have been deleted.</p>";
                    echo "<p><a href=''>Refresh to see updated list</a></p>";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    throw new Exception("Cleanup failed: " . $e->getMessage());
                }
            } else {
                // Show entries for review
                echo "<form method='post' onsubmit='return confirm(\"Are you sure you want to delete the selected entries?\");'>";
                
                while ($nameRow = $result->fetch_assoc()) {
                    echo "<div class='group'>";
                    echo "<h3>Name: " . htmlspecialchars($nameRow['name']) . 
                         " (" . $nameRow['count'] . " entries)</h3>";
                    
                    // Get all entries for this name
                    $detailQuery = "
                        SELECT id, reference, name, location, church_name, relationship,
                               message, status, created_at
                        FROM tributes
                        WHERE name = ?
                        ORDER BY created_at DESC
                    ";
                    
                    $stmt = $conn->prepare($detailQuery);
                    $stmt->bind_param("s", $nameRow['name']);
                    $stmt->execute();
                    $details = $stmt->get_result();
                    
                    echo "<table>
                            <tr>
                                <th>Select</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Church</th>
                                <th>Relationship</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>";
                    
                    while ($detail = $details->fetch_assoc()) {
                        echo "<tr>
                                <td><input type='checkbox' name='entries[]' value='" . 
                                htmlspecialchars($detail['id']) . "'></td>
                                <td>" . htmlspecialchars($detail['created_at']) . "</td>
                                <td>" . htmlspecialchars($detail['location']) . "</td>
                                <td>" . htmlspecialchars($detail['church_name']) . "</td>
                                <td>" . htmlspecialchars($detail['relationship']) . "</td>
                                <td><pre>" . htmlspecialchars($detail['message']) . "</pre></td>
                                <td>" . htmlspecialchars($detail['status']) . "</td>
                              </tr>";
                    }
                    
                    echo "</table>";
                    echo "</div>";
                }
                
                echo "<div class='actions'>
                        <p>Select the entries you want to delete and click the button below:</p>
                        <button type='submit' name='delete'>Delete Selected Entries</button>
                      </div>";
                echo "</form>";
            }
        }
        
    } else {
        echo "<p class='error'>Database connection is not responding.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>