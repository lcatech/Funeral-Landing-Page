<?php
// Save as core/email_notification.php

class TributeNotification {
    private $adminEmails;
    private $fromEmail;
    private $siteUrl;
    
    public function __construct() {
        // Configure multiple admin email addresses
        $this->adminEmails = [
            'e.oluakinyemi@gmail.com',
            'mikeltosin@gmail.com'  // Update with actual admin emails
        ];
        
        $this->fromEmail = 'notifications@reveoakinyemi.com';
        
        // Set your site URL
        $this->siteUrl = 'https://reveoakinyemi.com';
    }
    
    public function sendNewTributeNotification($tributeData) {
        $subject = "New Tribute Submission - Ref: {$tributeData['reference']}";
        
        // Get the tribute ID from the database
        $tributeId = $this->getTributeId($tributeData['reference']);
        if (!$tributeId) {
            error_log("Failed to get tribute ID for reference: {$tributeData['reference']}");
            return false;
        }
        
        $message = $this->createEmailBody($tributeData, $tributeId);
        $headers = $this->createEmailHeaders();
        
        // Send to all admin recipients
        $to = implode(', ', $this->adminEmails);
        
        return mail($to, $subject, $message, $headers);
    }
    
    private function getTributeId($reference) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT id FROM tributes WHERE reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        }
        return null;
    }
    
    private function createEmailBody($data, $tributeId) {
        // Generate secure action tokens
        $approveToken = hash('sha256', $tributeId . time() . 'approve');
        $editToken = hash('sha256', $tributeId . time() . 'edit');
        
        // Store tokens in database
        $this->storeActionTokens($tributeId, $approveToken, $editToken);
        
        // Create action URLs
        $approveUrl = "{$this->siteUrl}/control/quick-actions.php?action=approve&id={$tributeId}&token={$approveToken}";
        $editUrl = "{$this->siteUrl}/control/quick-actions.php?action=edit&id={$tributeId}&token={$editToken}";
        $viewUrl = "{$this->siteUrl}/control/admin.php";
        
        $imageSection = '';
        if (!empty($data['image'])) {
            $imageSection = "<p><strong>Image Uploaded:</strong> Yes</p>";
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 20px auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 15px; border-radius: 5px; }
                .content { margin: 20px 0; }
                .footer { font-size: 12px; color: #666; margin-top: 30px; }
                .label { font-weight: bold; }
                .actions { margin: 20px 0; padding: 15px; background: #f1f1f1; border-radius: 5px; }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    margin: 5px 10px;
                    border-radius: 5px;
                    text-decoration: none;
                    color: white;
                }
                .approve { background-color: #28a745; }
                .edit { background-color: #007bff; }
                .view { background-color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Tribute Submission</h2>
                    <p>Reference ID: {$data['reference']}</p>
                </div>
                
                <div class='actions'>
                    <h3>Quick Actions</h3>
                    <p>Click to perform actions directly from this email:</p>
                    <a href='$approveUrl' class='button approve'>Approve Tribute</a>
                    <a href='$editUrl' class='button edit'>Edit Tribute</a>
                    <a href='$viewUrl' class='button view'>View in Admin Portal</a>
                </div>
                
                <div class='content'>
                    <p><span class='label'>Name:</span> {$data['name']}</p>
                    <p><span class='label'>Location:</span> {$data['location']}</p>
                    <p><span class='label'>Relationship:</span> {$data['relationship']}</p>
                    " . (!empty($data['church_name']) ? "<p><span class='label'>Church:</span> {$data['church_name']}</p>" : "") . "
                    <p><span class='label'>Message:</span><br>" . nl2br($data['message']) . "</p>
                    $imageSection
                    <p><span class='label'>Status:</span> {$data['status']}</p>
                    <p><span class='label'>Submitted:</span> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <div class='footer'>
                    <p>You're receiving this because you're an administrator for the tributes system.</p>
                    <p>Use the buttons above to manage this tribute or log in to the admin portal for full access.</p>
                    <p>Note: Quick action links will expire in 7 days for security.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function createEmailHeaders() {
        return implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromEmail,
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ]);
    }
    
    private function storeActionTokens($tributeId, $approveToken, $editToken) {
        global $conn;
        
        // Delete any existing tokens for this tribute
        $stmt = $conn->prepare("DELETE FROM tribute_tokens WHERE tribute_id = ?");
        $stmt->bind_param("i", $tributeId);
        $stmt->execute();
        
        // Insert new tokens
        $sql = "INSERT INTO tribute_tokens (tribute_id, approve_token, edit_token, created_at, expires_at) 
                VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $tributeId, $approveToken, $editToken);
        $stmt->execute();
    }
}
?>