<?php
require_once __DIR__ . '/../core/db_connection.php';
require_once __DIR__ . '/../core/email_notification.php';

class EmailTributeProcessor {
    private $mailbox;
    private $conn;
    private $uploadDir = 'uploads/tributes/';
    private $debug = true;
    private $log_file = 'logs/email_tributes.log';
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const DEFAULT_RELATIONSHIP = 'Other';
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Ensure upload directory exists with correct permissions
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Initialize database table if needed
        $this->initializeDatabase();
        
        // Get credentials and connect to mailbox
        $credentials = $this->getCredentials();
        $this->connectToMailbox($credentials);
    }
    
    private function getCredentials() {
        $stmt = $this->conn->prepare("
            SELECT host, port, username, password 
            FROM email_credentials 
            WHERE is_active = 1 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('No active email credentials found');
        }
        
        return $result->fetch_assoc();
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] " . htmlspecialchars($message) . "\n";
        file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        if ($this->debug) {
            echo htmlspecialchars($log_message);
        }
    }
    
    private function initializeDatabase() {
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS processed_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id VARCHAR(255) NOT NULL,
                reference VARCHAR(255) NOT NULL,
                processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_message (message_id),
                FOREIGN KEY (reference) REFERENCES tributes(reference)
            ) ENGINE=InnoDB
        ");

        // Create credentials table if it doesn't exist
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS email_credentials (
                id INT AUTO_INCREMENT PRIMARY KEY,
                host VARCHAR(255) NOT NULL,
                port INT NOT NULL,
                username VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
    }
    
    private function connectToMailbox($credentials) {
        $connection_string = '{' . $credentials['host'] . ':' . $credentials['port'] . '/imap/ssl/novalidate-cert}INBOX';
        $this->log("Attempting connection to: " . $credentials['host']);
        
        $this->mailbox = @imap_open(
            $connection_string,
            $credentials['username'],
            $credentials['password']
        );
        
        if (!$this->mailbox) {
            $error = imap_last_error();
            $this->log("Connection failed: " . $error);
            throw new Exception("Failed to connect to email server: " . $error);
        }
        
        $this->log("Successfully connected to mailbox");
    }
    
    public function processNewTributes() {
        $emails = imap_search($this->mailbox, 'UNSEEN');
        
        if (!$emails) {
            $this->log("No new messages found in mailbox");
            return [];
        }
        
        $this->log("Found " . count($emails) . " new messages");
        $processedTributes = [];
        
        foreach ($emails as $email_number) {
            try {
                $this->conn->begin_transaction();
                
                $tribute = $this->processEmail($email_number);
                if ($tribute) {
                    $processedTributes[] = $tribute;
                    $this->conn->commit();
                }
            } catch (Exception $e) {
                $this->conn->rollback();
                $this->log("Error processing email $email_number: " . $e->getMessage());
            }
        }
        
        $this->log("Successfully processed " . count($processedTributes) . " new tributes");
        return $processedTributes;
    }
    
        private function processEmail($email_number) {
        $header = imap_headerinfo($this->mailbox, $email_number);
        $message_id = $header->message_id ?? imap_uid($this->mailbox, $email_number);
        
        // Check if already processed
        $stmt = $this->conn->prepare("SELECT reference FROM processed_emails WHERE message_id = ?");
        $stmt->bind_param("s", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $this->log("Skipping already processed email: " . $message_id);
            return null;
        }
        
        // Generate reference ID
        $reference = 'T' . bin2hex(random_bytes(4));
        
        // Extract sender information
        $from = $header->from[0];
        $senderName = $this->sanitizeInput($from->personal ?? $from->mailbox);
        $senderEmail = $this->sanitizeInput($from->mailbox . '@' . $from->host);
        
        $this->log("Processing email from: $senderName <$senderEmail>");
        
        // Get message content
        $structure = imap_fetchstructure($this->mailbox, $email_number);
        $body = $this->getEmailBody($email_number, $structure);
        
        // Process attachments
        $imagePath = $this->processAttachments($email_number, $structure, $reference);
        
        // Set default values for optional fields
        $location = null;
        $church_name = null;
        $relationship = self::DEFAULT_RELATIONSHIP; // Use default relationship if not specified
        
        // Try to extract relationship from email body if possible
        // This is a placeholder - you might want to implement more sophisticated parsing
        $relationship_keywords = ['family', 'friend', 'colleague', 'church member', 'pastor'];
        foreach ($relationship_keywords as $keyword) {
            if (stripos($body, $keyword) !== false) {
                $relationship = ucfirst($keyword);
                break;
            }
        }
        
        // Insert tribute with default relationship if none found
        $stmt = $this->conn->prepare("
            INSERT INTO tributes 
            (reference, name, location, church_name, relationship, message, image, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->bind_param("sssssss",
            $reference, 
            $senderName,
            $location,
            $church_name, 
            $relationship,
            $body,
            $imagePath  
        );
        
        if ($stmt->execute()) {
            // Record as processed
            $stmt = $this->conn->prepare("INSERT INTO processed_emails (message_id, reference) VALUES (?, ?)");
            $stmt->bind_param("ss", $message_id, $reference);
            $stmt->execute();
            
            // Prepare tribute data
            $tributeData = [
                'reference' => $reference,
                'name' => $senderName,
                'location' => $location,
                'church_name' => $church_name,
                'relationship' => $relationship,
                'message' => $body,
                'image' => $imagePath,
                'status' => 'pending'
            ];
            
            // Send notification
            try {
                $notification = new TributeNotification();
                $notification->sendNewTributeNotification($tributeData);
            } catch (Exception $e) {
                $this->log("Notification error for reference $reference: " . $e->getMessage());
            }
            
            $this->log("Successfully processed tribute with reference: $reference");
            return $tributeData;
            
        } else {
            $this->log("Failed to insert tribute into database: " . $this->conn->error);
            throw new Exception("Database insert failed");
        }
    }
    
    private function getEmailBody($email_number, $structure) {
        $body = '';
        
        if (!isset($structure->parts)) {
            $body = imap_body($this->mailbox, $email_number);
            $body = $this->decodeBody($body, $structure->encoding);
        } else {
            $body = $this->getTextPart($email_number, $structure);
        }
        
        // Convert to UTF-8 if needed
        $charset = $this->detectCharset($structure);
        if ($charset && strtoupper($charset) !== 'UTF-8') {
            $body = @iconv($charset, 'UTF-8//IGNORE', $body);
        }
        
        return $this->cleanMessageBody($body);
    }
    
    private function getTextPart($email_number, $structure, $part_number = "") {
        $body = "";
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $index => $sub_structure) {
                $prefix = $part_number ? $part_number . "." : "";
                $current_part_number = $prefix . ($index + 1);
                
                if ($sub_structure->subtype === 'PLAIN') {
                    $data = imap_fetchbody($this->mailbox, $email_number, $current_part_number);
                    $body .= $this->decodeBody($data, $sub_structure->encoding);
                    break;
                } elseif ($sub_structure->subtype === 'HTML' && empty($body)) {
                    $data = imap_fetchbody($this->mailbox, $email_number, $current_part_number);
                    $body = $this->decodeBody($data, $sub_structure->encoding);
                    $body = strip_tags($body);
                } elseif (!empty($sub_structure->parts)) {
                    $body .= $this->getTextPart($email_number, $sub_structure, $current_part_number);
                }
            }
        }
        
        return $body;
    }
    
    private function decodeBody($body, $encoding) {
        switch ($encoding) {
            case 3: // BASE64
                $body = base64_decode($body);
                break;
            case 4: // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
                break;
        }
        return $body;
    }
    
    private function cleanMessageBody($body) {
        // Remove email signatures and common delimiters
        $body = preg_replace('/^>+/m', '', $body);
        $body = preg_replace('/\s*--\s*\n.*$/s', '', $body);
        $body = preg_replace('/\s*Sent from .*$/s', '', $body);
        
        // Clean up whitespace
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        
        return $this->sanitizeInput($body);
    }
    
    private function detectCharset($structure) {
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $param) {
                if (strtolower($param->attribute) === 'charset') {
                    return $param->value;
                }
            }
        }
        return 'UTF-8';
    }
    
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function processAttachments($email_number, $structure, $reference) {
        if (!isset($structure->parts)) {
            return null;
        }
        
        foreach ($structure->parts as $part_number => $part) {
            if ($this->isImageAttachment($part)) {
                $attachment = imap_fetchbody($this->mailbox, $email_number, $part_number + 1);
                
                if ($part->encoding === 3) {  // BASE64
                    $attachment = base64_decode($attachment);
                }
                
                if (strlen($attachment) > self::MAX_FILE_SIZE) {
                    $this->log("Attachment exceeds maximum file size");
                    continue;
                }
                
                $tmp_file = tempnam(sys_get_temp_dir(), 'tribute_');
                if (!$tmp_file) {
                    throw new RuntimeException("Failed to create temporary file");
                }
                
                try {
                    file_put_contents($tmp_file, $attachment);
                    
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $finfo->file($tmp_file);
                    
                    if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
                        throw new RuntimeException("Invalid file type: {$mime_type}");
                    }
                    
                    $extension = $this->getExtensionFromMimeType($mime_type);
                    $filename = $reference . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $filepath = $this->uploadDir . $filename;
                    
                    if (rename($tmp_file, $filepath)) {
                        chmod($filepath, 0644);
                        $this->log("Saved attachment: $filepath");
                        return $filepath;
                    } else {
                        throw new RuntimeException("Failed to move uploaded file");
                    }
                } finally {
                    if (file_exists($tmp_file)) {
                        unlink($tmp_file);
                    }
                }
            }
        }
        
        return null;
    }
    
    private function getExtensionFromMimeType($mime_type) {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        
        return $extensions[$mime_type] ?? 'jpg';
    }
    
    private function isImageAttachment($part) {
        return (
            isset($part->disposition) &&
            strtolower($part->disposition) === 'attachment' &&
            isset($part->subtype) &&
            in_array(strtoupper($part->subtype), ['JPEG', 'JPG', 'PNG', 'GIF'])
        );
    }
    
    public function __destruct() {
        if ($this->mailbox) {
            imap_close($this->mailbox);
        }
    }
}

// Process tributes
try {
    $processor = new EmailTributeProcessor();
    $newTributes = $processor->processNewTributes();
    
} catch (Exception $e) {
    error_log("Fatal error in tribute processing: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}
?>