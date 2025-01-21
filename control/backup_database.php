<?php
require_once '../core/db_connection.php';

class DatabaseBackup {
    private $host;
    private $username;
    private $password;
    private $database;
    private $backupDir;
    
    public function __construct($conn) {
        // Get database credentials from the connection
        $this->host = $conn->host_info;
        $this->username = getenv('MYSQL_USER') ?: 'your_db_user';
        $this->password = getenv('MYSQL_PASSWORD') ?: 'your_db_password';
        $this->database = $conn->database;
        
        // Set backup directory
        $this->backupDir = dirname(__DIR__) . '/backups/database';
        
        // Enable error logging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', 'backup_error.log');
    }
    
    public function createBackup() {
        try {
            // Create backup directory if it doesn't exist
            if (!file_exists($this->backupDir)) {
                if (!mkdir($this->backupDir, 0755, true)) {
                    throw new Exception("Failed to create backup directory");
                }
            }
            
            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "{$this->database}_backup_{$timestamp}.sql";
            $filepath = "{$this->backupDir}/{$filename}";
            
            // Construct mysqldump command
            $command = sprintf(
                'mysqldump --opt -h %s -u %s --password=%s %s > %s',
                escapeshellarg($this->host),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->database),
                escapeshellarg($filepath)
            );
            
            // Execute backup command
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new Exception("Database backup failed");
            }
            
            // Compress the backup file
            $zipFilepath = $filepath . '.gz';
            $this->compressFile($filepath, $zipFilepath);
            
            // Remove original .sql file after compression
            unlink($filepath);
            
            // Clean up old backups (keep last 7 days)
            $this->cleanOldBackups();
            
            error_log("Backup completed successfully: {$zipFilepath}");
            return true;
            
        } catch (Exception $e) {
            error_log("Backup Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function compressFile($source, $destination) {
        $mode = 'wb9'; // Highest compression
        $error = false;
        
        if ($fp_out = gzopen($destination, $mode)) {
            if ($fp_in = fopen($source, 'rb')) {
                while (!feof($fp_in)) {
                    gzwrite($fp_out, fread($fp_in, 1024 * 512));
                }
                fclose($fp_in);
            } else {
                $error = true;
            }
            gzclose($fp_out);
        } else {
            $error = true;
        }
        
        if ($error) {
            throw new Exception("Error compressing backup file");
        }
        
        return true;
    }
    
    private function cleanOldBackups() {
        // Keep backups for the last 7 days
        $cutoff = strtotime('-7 days');
        
        foreach (glob("{$this->backupDir}/*.sql.gz") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                error_log("Deleted old backup: {$file}");
            }
        }
    }
}

// Execute backup
try {
    error_log("Starting database backup...");
    $backup = new DatabaseBackup($conn);
    $result = $backup->createBackup();
    error_log("Backup process completed. Result: " . ($result ? "Success" : "Failed"));
} catch (Exception $e) {
    error_log("Critical Error: " . $e->getMessage());
}
?>