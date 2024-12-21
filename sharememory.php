<!DOCTYPE html>
<html lang="en">
<head>
    <title>Share Memory | Reverend Elijah O. Akinyemi</title>
</head>
<?php include 'nav/header.php'; ?>

<body>
    
    <section class="form-container">
        <form id="tribute-form" action="submit_tribute.php" method="post" enctype="multipart/form-data" onsubmit="return previewTribute(event)">
            <h3>Submit Your Tribute</h3>
            <fieldset>
                <input type="text" name="name" placeholder="Your full name" required>
            </fieldset>
            <fieldset>
                <input type="text" name="location" placeholder="Your location" required>
            </fieldset>
            <fieldset>
                <input type="text" name="church_name" placeholder="Church name (optional)">
            </fieldset>
            <fieldset>
                <select name="relationship" required>
                    <option value="">Select Relationship</option>
                    <option value="Family">Family</option>
                    <option value="Friend">Friend</option>
                    <option value="Church">Church</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                </select>
            </fieldset>
            <fieldset>
                <textarea name="message" placeholder="Type your message here..." required></textarea>
            </fieldset>
            <fieldset>
                <input type="file" name="image" accept="image/*">
            </fieldset>
            <fieldset>
                <button type="submit">Preview Tribute</button>
            </fieldset>
        </form>
        
        <div id="preview-section" style="display: none; margin-top: 20px;">
            <h3>Preview Your Tribute</h3>
            <div id="preview-content"></div>
            <button onclick="confirmSubmission()">Confirm Submission</button>
            <button onclick="editSubmission()">Edit Tribute</button>
        </div>
    </section>

    <script>
        function previewTribute(event) {
            event.preventDefault();

            const form = document.getElementById('tribute-form');
            const previewSection = document.getElementById('preview-section');
            const previewContent = document.getElementById('preview-content');

            const formData = new FormData(form);
            const name = formData.get('name');
            const location = formData.get('location');
            const churchName = formData.get('church_name') || 'Not provided';
            const relationship = formData.get('relationship');
            const message = formData.get('message');

            let imagePreview = '';
            if (formData.get('image').name) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview = `<img src="${e.target.result}" alt="Uploaded Image" style="max-width: 200px;">`;
                    updatePreview();
                };
                reader.readAsDataURL(formData.get('image'));
            } else {
                updatePreview();
            }

            function updatePreview() {
                previewContent.innerHTML = `
                    <p><strong>Name:</strong> ${name}</p>
                    <p><strong>Location:</strong> ${location}</p>
                    <p><strong>Church Name:</strong> ${churchName}</p>
                    <p><strong>Relationship:</strong> ${relationship}</p>
                    <p><strong>Message:</strong> ${message}</p>
                    ${imagePreview}
                `;
                previewSection.style.display = 'block';
                form.style.display = 'none';
            }
        }

        function confirmSubmission() {
            document.getElementById('tribute-form').submit();
        }

        function editSubmission() {
            document.getElementById('tribute-form').style.display = 'block';
            document.getElementById('preview-section').style.display = 'none';
        }
    </script>
    
</body>

<?php include 'footer.php'; ?>

</html>
