<?php
require_once __DIR__ . '/../core/db_connection.php';

class TributesExport {
    private $conn;
    private $tempDir;
    private $recipients = [
        'e.oluak@gmail.com',
        'mikeltosin@gmail.com'
    ];
    private $fromEmail = 'notifications@reveoakinyemi.com';
    private $logFile = 'logs/export_error.log';
    
    public function __construct($conn) {
        $this->conn = $conn;
        
        // Ensure we have an absolute path for the temp directory
        $this->tempDir = dirname(__DIR__) . '/tmp';
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        // Create log directory if it doesn't exist
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Enable error logging
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', $this->logFile);
        
        // Set UTF-8 encoding
        mysqli_set_charset($this->conn, 'utf8mb4');
    }
    
    public function exportAndEmail() {
        try {
            $this->log("Starting export process...");
            
            // Get last exported ID
            $lastExportedId = $this->getLastExportedId();
            $this->log("Last exported ID: " . $lastExportedId);
            
            // Get new records
            $newRecords = $this->getNewRecords($lastExportedId);
            $this->log("Found " . count($newRecords) . " new records");
            
            if (empty($newRecords)) {
                $this->log("No new records - sending notification");
                $this->sendNoRecordsNotification();
                return true;
            }
            
            // Create CSV file
            $filename = 'tributes_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = $this->tempDir . '/' . $filename;
            $this->log("Creating CSV at: " . $filepath);
            
            // Open file with UTF-8 BOM for Excel compatibility
            $fp = fopen($filepath, 'w');
            if (!$fp) {
                throw new Exception("Could not create file: " . $filepath);
            }
            
            // Add UTF-8 BOM
            fwrite($fp, "\xEF\xBB\xBF");
            
            // Add headers
            fputcsv($fp, ['Name', 'Relationship', 'Location', 'Church Name', 'Message', 'Date of Submission']);
            
            // Add data with proper encoding
            foreach ($newRecords as $record) {
                $submissionDate = date('F j, Y', strtotime($record['created_at']));
                
                // Properly encode each field
                $row = array_map(function($field) {
                    // Convert HTML entities back to characters
                    $decoded = html_entity_decode($field, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    // Remove any invalid UTF-8 sequences
                    return mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
                }, [
                    $record['name'],
                    $record['relationship'],
                    $record['location'] ?? '',
                    $record['church_name'] ?? '',
                    $record['message'],
                    $submissionDate
                ]);
                
                fputcsv($fp, $row);
            }
            
            fclose($fp);
            
            // Email the file
            $success = $this->emailExport($filepath, $filename, count($newRecords));
            if (!$success) {
                throw new Exception("Failed to send email");
            }
            
            // Update last exported ID only after successful email
            $this->updateLastExportedId(end($newRecords)['id']);
            
            // Clean up
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            $this->log("Successfully exported and emailed " . count($newRecords) . " records");
            return true;
            
        } catch (Exception $e) {
            $this->log("Export Error: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        error_log($logMessage, 3, $this->logFile);
    }
    

    private function sendNoRecordsNotification() {
        $subject = "Tributes Export Status - " . date('F j, Y');
        $message = "No new tributes have been submitted since the last export.\n\n";
        $message .= "Export check time: " . date('F j, Y g:i A') . "\n";

        $headers = implode("\r\n", [
            'From: ' . $this->fromEmail,
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ]);

        $success = true;
        foreach ($this->recipients as $recipient) {
            $result = mail($recipient, $subject, $message, $headers);
            error_log("Sending no-records notification to {$recipient}: " . ($result ? "Success" : "Failed"));
            $success = $success && $result;
        }
        return $success;
    }
    
    private function getLastExportedId() {
        try {
            // Check if tracking table exists
            $checkTable = "SHOW TABLES LIKE 'export_tracking'";
            $result = $this->conn->query($checkTable);
            
            if ($result === false) {
                throw new Exception("Failed to check if table exists: " . $this->conn->error);
            }
            
            if ($result->num_rows == 0) {
                // Create tracking table if it doesn't exist
                $createTable = "CREATE TABLE IF NOT EXISTS export_tracking (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    last_exported_id INT NOT NULL,
                    last_export_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB";
                
                if (!$this->conn->query($createTable)) {
                    throw new Exception("Failed to create tracking table: " . $this->conn->error);
                }
                
                // Insert initial record
                $insertSql = "INSERT INTO export_tracking (last_exported_id) VALUES (0)";
                if (!$this->conn->query($insertSql)) {
                    throw new Exception("Failed to insert initial record: " . $this->conn->error);
                }
                
                error_log("Created export_tracking table and inserted initial record");
                return 0;
            }
            
            $result = $this->conn->query("SELECT last_exported_id FROM export_tracking ORDER BY id DESC LIMIT 1");
            if ($result === false) {
                throw new Exception("Failed to get last exported ID: " . $this->conn->error);
            }
            
            $row = $result->fetch_assoc();
            $lastId = $row ? $row['last_exported_id'] : 0;
            error_log("Retrieved last exported ID: " . $lastId);
            return $lastId;
            
        } catch (Exception $e) {
            error_log("Error in getLastExportedId: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getNewRecords($lastExportedId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, name, relationship, location, church_name, 
                       message, created_at
                FROM tributes 
                WHERE id > ?
                ORDER BY id ASC
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $lastExportedId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $records = [];
            
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            
            return $records;
            
        } catch (Exception $e) {
            error_log("Error in getNewRecords: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function updateLastExportedId($id) {
        try {
            if (!is_numeric($id)) {
                throw new Exception("Invalid ID provided for update: " . $id);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO export_tracking (last_exported_id, last_export_time) 
                VALUES (?, CURRENT_TIMESTAMP)
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update last exported ID: " . $stmt->error);
            }
            
            error_log("Successfully updated last exported ID to: " . $id);
            return true;
            
        } catch (Exception $e) {
            error_log("Error in updateLastExportedId: " . $e->getMessage());
            throw $e;
        }
    }
    
    
    
    
    
    
    private function emailExport($filepath, $filename, $recordCount) {
        try {
            $subject = "New Tributes Export - " . date('F j, Y');
            $message = "Attached is the latest tributes export containing $recordCount new records.\n\n";
            $message .= "Export time: " . date('F j, Y g:i A') . "\n";
            
            // Create boundary
            $boundary = md5(time());
            
            // Create headers with UTF-8 charset
            $headers = implode("\r\n", [
                'From: ' . $this->fromEmail,
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: multipart/mixed; boundary=' . $boundary,
                'Content-Transfer-Encoding: 8bit',
                'charset=UTF-8'
            ]);
            
            // Create message body
            $body = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $message . "\r\n\r\n";
            
            // Add attachment
            $attachment = file_get_contents($filepath);
            if ($attachment === false) {
                throw new Exception("Failed to read attachment file: " . $filepath);
            }
            
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/csv; name=\"{$filename}\"; charset=UTF-8\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($attachment)) . "\r\n";
            $body .= "--{$boundary}--";
            
            $success = true;
            foreach ($this->recipients as $recipient) {
                $result = mail($recipient, $subject, $body, $headers);
                $this->log("Sending export to {$recipient}: " . ($result ? "Success" : "Failed"));
                $success = $success && $result;
            }
            
            return $success;
            
        } catch (Exception $e) {
            $this->log("Error in emailExport: " . $e->getMessage());
            throw $e;
        }
    }
}

// Script execution
try {
    error_log("Starting tributes export script");
    
    // Ensure script can run for a while if needed
    set_time_limit(300); // 5 minutes
    
    $export = new TributesExport($conn);
    $result = $export->exportAndEmail();
    error_log("Export script completed. Result: " . ($result ? "Success" : "Failed"));
} catch (Exception $e) {
    error_log("Critical Error in export script: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
}
?>