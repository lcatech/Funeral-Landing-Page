<?php
session_start();
require_once 'core/db_connection.php';
?>

<?php include 'nav/header.php'; ?>

<head>
    <title>Submit Your Tribute</title>
    <style>
       .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .tribute-textarea {
            width: 100%;
            min-height: 200px;
            line-height: 1.6;
            padding: 12px;
            font-size: 16px;
            color: #333;
            white-space: pre-wrap;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            resize: vertical;
        }

        .formatting-tips {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }

        .formatting-tips ul {
            margin: 5px 0;
            padding-left: 20px;
        }

        .formatting-tips li {
            margin: 5px 0;
        }

        .preview-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            white-space: pre-wrap;
            display: none;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        input[type="text"],
        select,
        textarea {
            font-size: 16px;
            color: #333;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
        }
        fieldset {
            border: none;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 16px;
            color: #333;
        }

        .form-preview-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    @media (max-width: 1100px) {
        .form-preview-container {
            grid-template-columns: 1fr;
        }
    }
    
    .preview-section {
        position: sticky;
        top: 2rem;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-radius: 1.6rem;
        padding: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .preview-section h3 {
        color: #fad14b;
        margin-bottom: 1rem;
    }
    
    .live-preview {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .preview-content {
        color: rgba(255, 255, 255, 0.95);
        font-size: 1.5rem;
        line-height: 1.8;
        white-space: pre-line;
        word-break: break-word;
        hyphens: auto;
    }
    
    .preview-meta {
        margin-bottom: 1rem;
    }
    
    .preview-name {
        font-size: 2rem;
        color: #fad14b;
        margin-bottom: 0.5rem;
    }
    
    .preview-relationship {
        font-size: 1.4rem;
        color: rgba(255, 255, 255, 0.7);
    }

    </style>
</head>
<body>
<div class="form-preview-container">
    <!-- Form Section -->
    <section class="form-container">
        <h3>Submit Your Tribute</h3>
        <div style="text-align: center; margin-bottom: 2rem;">
            <p style="color: #333; font-size: 1.4rem;">All tributes will be reviewed before being published online.</p>
        </div>
        
        <form action="/submit_tribute" method="POST" enctype="multipart/form-data" id="tributeForm">
            <fieldset>
                <input type="text" name="name" id="tributeName" placeholder="Your full name" required>
            </fieldset>
            <fieldset>
                <input type="text" name="location" placeholder="Your location" required>
            </fieldset>
            <fieldset>
                <input type="text" name="church_name" placeholder="Church name (optional)">
            </fieldset>
            <fieldset>
                <select name="relationship" id="tributeRelationship" required>
                    <option value="">Select Relationship</option>
                    <option value="Family">Family</option>
                    <option value="Friend">Friend</option>
                    <option value="Church">Church</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                </select>
            </fieldset>
            <fieldset>
                <div class="formatting-tips">
                    <strong>Formatting Tips:</strong>
                    <ul>
                        <li>Press Enter to start a new paragraph after completing a thought</li>
                        <li>Complete your sentences with proper punctuation (. ? !)</li>
                        <li>Your text will be automatically formatted while typing</li>
                    </ul>
                </div>
                <textarea 
                    name="message" 
                    id="tributeMessage" 
                    class="tribute-textarea"
                    placeholder="Type your tribute message here..." 
                    required></textarea>
            </fieldset>
            <fieldset>
                <p style="font-size: 16px; color: #333; margin-bottom: 0.5rem;">Upload an image (optional)</p>
                <input type="file" name="image" id="tributeImage" accept="image/*" onchange="previewImage(event)">
                <p style="font-size: 14px; color: #666; margin-top: 0.5rem;">Supported formats: JPG, PNG, GIF (Max size: 5MB)</p>
            </fieldset>
            <fieldset>
                <button type="submit" style="font-size: 16px; padding: 12px 24px;">Submit Tribute</button>
            </fieldset>
        </form>
    </section>

    <!-- Preview Section -->
    <section class="preview-section">
        <h3>Live Preview</h3>
        <div class="preview-meta">
            <div id="previewName" class="preview-name"></div>
            <div id="previewRelationship" class="preview-relationship"></div>
        </div>
        <div class="live-preview">
            <div id="previewContent" class="preview-content"></div>
        </div>
        <div id="previewImageContainer" style="margin-top: 2rem; display: none;">
            <img id="previewImageDisplay" style="max-width: 100%; border-radius: 0.8rem;" />
        </div>
    </section>
</div>

<script>
    function formatText(text) {
        // Split text into lines
        let lines = text.split('\n');
        
        // Remove empty lines from start and end
        while (lines.length && !lines[0].trim()) lines.shift();
        while (lines.length && !lines[lines.length-1].trim()) lines.pop();
        
        if (!lines.length) return '';
        
        // Separate first line (header/salutation)
        let header = lines.shift().trim();
        let formattedLines = [header];
        
        // Find potential signature lines at the end
        let signatureLines = [];
        while (lines.length > 0 && 
               (!lines[lines.length-1].match(/[.!?]$/) || // No sentence ending
                lines[lines.length-1].length < 50)) {      // Or relatively short line
            let lastLine = lines.pop().trim();
            if (lastLine) {
                signatureLines.unshift(lastLine);
            } else if (signatureLines.length > 0) {
                break; // Stop if we hit an empty line after finding signature
            }
        }
        
        // Process main content
        let currentLine = '';
        lines.forEach(line => {
            line = line.trim();
            if (!line) {
                if (currentLine) {
                    formattedLines.push(currentLine);
                    currentLine = '';
                }
                formattedLines.push('');  // Keep empty line for paragraph break
                return;
            }
            
            if (!currentLine) {
                currentLine = line;
            } else if (currentLine.match(/[.!?]$/)) {
                formattedLines.push(currentLine);
                currentLine = line;
            } else {
                currentLine += ' ' + line;
            }
        });
        
        // Add any remaining content line
        if (currentLine) {
            formattedLines.push(currentLine);
        }
        
        // Add empty line before signature if needed
        if (signatureLines.length > 0 && formattedLines[formattedLines.length - 1] !== '') {
            formattedLines.push('');
        }
        
        // Add signature lines
        formattedLines = formattedLines.concat(signatureLines);
        
        // Join lines, clean up multiple empty lines
        text = formattedLines.join('\n').replace(/\n{3,}/g, '\n\n');
        
        // Clean up spacing and formatting
        text = text
            .replace(/ +/g, ' ')
            .replace(/([.!?])([A-Za-z])/g, '$1 $2')
            .replace(/(^\w|[.!?]\s+\w)/g, letter => letter.toUpperCase());
        
        return text.trim();
    }

    // Update preview functions
    function updatePreview() {
        const nameInput = document.getElementById('tributeName');
        const relationshipSelect = document.getElementById('tributeRelationship');
        const messageTextarea = document.getElementById('tributeMessage');
        
        // Update name
        document.getElementById('previewName').textContent = 
            nameInput.value || 'Your Name';
        
        // Update relationship
        document.getElementById('previewRelationship').textContent = 
            relationshipSelect.options[relationshipSelect.selectedIndex].value || 'Relationship';
        
        // Update message with formatting
        document.getElementById('previewContent').textContent = 
            formatText(messageTextarea.value || 'Your message will appear here...');
    }

    function previewImage(event) {
        const file = event.target.files[0];
        const container = document.getElementById('previewImageContainer');
        const preview = document.getElementById('previewImageDisplay');
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Reset any previous error messages
        const errorDisplay = document.getElementById('imageError');
        if (errorDisplay) {
            errorDisplay.remove();
        }
        
        if (file) {
            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                showImageError('Please select a valid image file (JPG, PNG, or GIF)');
                event.target.value = ''; // Clear the input
                container.style.display = 'none';
                return;
            }
            
            // Validate file size
            if (file.size > maxSize) {
                showImageError('Image size must be less than 5MB');
                event.target.value = ''; // Clear the input
                container.style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                container.style.display = 'block';
            };
            reader.onerror = function() {
                showImageError('Error reading the image file');
                event.target.value = ''; // Clear the input
                container.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            container.style.display = 'none';
        }
    }
    
    function showImageError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'imageError';
        errorDiv.style.cssText = `
            color: #dc3545;
            font-size: 14px;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 4px;
        `;
        errorDiv.textContent = message;
        
        const fileInput = document.getElementById('tributeImage');
        fileInput.parentNode.insertBefore(errorDiv, fileInput.nextSibling);
        
        // Auto-remove error message after 5 seconds
        setTimeout(() => {
            if (errorDiv && errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    // Initialize variables for typing timer
    let typingTimer;
    const doneTypingInterval = 1000; // 1 second delay

    // Add event listeners when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const tributeForm = document.getElementById('tributeForm');
        tributeForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('tributeImage');
            const file = fileInput.files[0];
            
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    showImageError('Please select a valid image file (JPG, PNG, or GIF)');
                    return false;
                }
                
                if (file.size > maxSize) {
                    e.preventDefault();
                    showImageError('Image size must be less than 5MB');
                    return false;
                }
            }

            // Format the message before submission
            const textarea = document.getElementById('tributeMessage');
            const formatted = formatText(textarea.value);
            textarea.value = formatted;
            
            // Allow form submission
            return true;
        });

        // Live preview updates
        document.getElementById('tributeName').addEventListener('input', updatePreview);
        document.getElementById('tributeRelationship').addEventListener('change', updatePreview);
        
        // Message preview with debounce
        const messageTextarea = document.getElementById('tributeMessage');
        messageTextarea.addEventListener('input', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(updatePreview, doneTypingInterval);
        });
        
        // Immediate preview update on keyup
        messageTextarea.addEventListener('keyup', updatePreview);
        
        // Image preview
        document.getElementById('tributeImage').addEventListener('change', previewImage);
        
        // Initial preview
        updatePreview();
    });
</script>


    <?php include 'nav/footer.php'; ?>
</body>
</html>