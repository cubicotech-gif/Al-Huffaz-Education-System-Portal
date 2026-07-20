<?php
// Get payment details from URL
$student_id = isset($_GET['student']) ? intval($_GET['student']) : 0;
$sponsorship_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

if (!$student_id || !$sponsorship_type || !$amount) {
    wp_die('Invalid payment details');
}

$student = get_post($student_id);
if (!$student) {
    wp_die('Student not found');
}

get_header();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
.payment-container {
    font-family: 'Poppins', sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
}

.payment-header {
    text-align: center;
    margin-bottom: 40px;
}

.payment-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #001a33;
    margin-bottom: 10px;
}

.payment-header h1 i {
    color: #0080ff;
}

.payment-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.payment-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border: 2px solid #e2e8f0;
}

.payment-card h2 {
    font-size: 20px;
    font-weight: 700;
    color: #001a33;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e6f2ff;
}

.payment-card h2 i {
    color: #0080ff;
}

.student-info {
    background: #f0f8ff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #cce6ff;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
}

.info-value {
    font-weight: 700;
    color: #001a33;
    font-size: 14px;
}

.amount-display {
    background: linear-gradient(135deg, #0080ff, #004d99);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.amount-display .label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.amount-display .amount {
    font-size: 36px;
    font-weight: 800;
    font-family: monospace;
}

.bank-details {
    background: #fff7ed;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #f59e0b;
}

.bank-details h3 {
    font-size: 16px;
    font-weight: 700;
    color: #92400e;
    margin: 0 0 15px 0;
}

.bank-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.bank-label {
    color: #78350f;
    font-weight: 500;
    font-size: 13px;
}

.bank-value {
    color: #92400e;
    font-weight: 700;
    font-size: 14px;
    font-family: monospace;
}

.instructions {
    background: #f0f8ff;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.instructions h3 {
    font-size: 16px;
    font-weight: 700;
    color: #004d99;
    margin: 0 0 15px 0;
}

.instructions ol {
    margin: 0;
    padding-left: 20px;
}

.instructions li {
    margin-bottom: 10px;
    color: #00264d;
    font-size: 14px;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group label .required {
    color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    transition: border 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0080ff;
    box-shadow: 0 0 0 3px rgba(0, 128, 255, 0.1);
}

.file-upload {
    border: 2px dashed #cbd5e1;
    padding: 30px;
    text-align: center;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.file-upload:hover {
    border-color: #0080ff;
    background: #f0f8ff;
}

.file-upload i {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 10px;
}

.file-upload p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.file-upload input[type="file"] {
    display: none;
}

.file-preview {
    margin-top: 15px;
    padding: 15px;
    background: #f0f8ff;
    border-radius: 8px;
    display: none;
}

.file-preview img {
    max-width: 100%;
    border-radius: 8px;
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Poppins', sans-serif;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
}

.submit-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

@media (max-width: 768px) {
    .payment-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="payment-container">
    
    <div class="payment-header">
        <h1><i class="fas fa-hand-holding-heart"></i> Complete Your Sponsorship</h1>
    </div>
    
    <div id="alertBox" class="alert"></div>
    
    <div class="payment-grid">
        
        <div class="payment-card">
            <h2><i class="fas fa-info-circle"></i> Sponsorship Details</h2>
            
            <div class="student-info">
                <div class="info-row">
                    <span class="info-label">Student Name:</span>
                    <span class="info-value"><?php echo esc_html($student->post_title); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">GR Number:</span>
                    <span class="info-value"><?php echo esc_html(get_post_meta($student_id, 'gr_number', true)); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sponsorship Type:</span>
                    <span class="info-value"><?php echo esc_html(ucfirst($sponsorship_type)); ?></span>
                </div>
            </div>
            
            <div class="amount-display">
                <div class="label">Total Amount to Pay</div>
                <div class="amount">PKR <?php echo number_format($amount); ?></div>
            </div>
            
            <div class="bank-details">
                <h3><i class="fas fa-university"></i> Payment Details</h3>
                
                <div class="bank-row">
                    <span class="bank-label">Bank Name:</span>
                    <span class="bank-value">[BANK NAME]</span>
                </div>
                <div class="bank-row">
                    <span class="bank-label">Account Title:</span>
                    <span class="bank-value">[ACCOUNT TITLE]</span>
                </div>
                <div class="bank-row">
                    <span class="bank-label">Account Number:</span>
                    <span class="bank-value">[ACCOUNT NUMBER]</span>
                </div>
                <div class="bank-row">
                    <span class="bank-label">IBAN (Int'l):</span>
                    <span class="bank-value">[IBAN NUMBER]</span>
                </div>
                <div class="bank-row">
                    <span class="bank-label">SWIFT Code:</span>
                    <span class="bank-value">[SWIFT CODE]</span>
                </div>
            </div>
            
            <div class="instructions">
                <h3><i class="fas fa-list-ol"></i> Payment Instructions</h3>
                <ol>
                    <li>Transfer the amount to the bank account above</li>
                    <li>Use Transaction Reference: <strong>STU-<?php echo $student_id; ?>-<?php echo strtoupper(substr($sponsorship_type, 0, 1)); ?></strong></li>
                    <li>Take a screenshot of your payment confirmation</li>
                    <li>Fill the form and upload the screenshot</li>
                    <li>We'll verify and send you confirmation within 24 hours</li>
                </ol>
            </div>
        </div>
        
        <div class="payment-card">
            <h2><i class="fas fa-upload"></i> Submit Payment Proof</h2>
            
            <form id="paymentProofForm" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="sponsor_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <input type="email" name="sponsor_email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone Number <span class="required">*</span></label>
                    <input type="tel" name="sponsor_phone" required>
                </div>
                
                <div class="form-group">
                    <label>Country <span class="required">*</span></label>
                    <input type="text" name="sponsor_country" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Method <span class="required">*</span></label>
                    <select name="payment_method" required>
                        <option value="">Select Method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="wire_transfer">International Wire Transfer</option>
                        <option value="western_union">Western Union</option>
                        <option value="moneygram">MoneyGram</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Transaction ID / Reference Number <span class="required">*</span></label>
                    <input type="text" name="transaction_id" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Date <span class="required">*</span></label>
                    <input type="date" name="payment_date" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Screenshot <span class="required">*</span></label>
                    <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload payment screenshot</p>
                        <p style="font-size: 12px; color: #94a3b8;">JPG, PNG or PDF (Max 5MB)</p>
                        <input type="file" id="fileInput" name="payment_screenshot" accept="image/*,application/pdf" required>
                    </div>
                    <div id="filePreview" class="file-preview"></div>
                </div>
                
                <div class="form-group">
                    <label>Additional Notes (Optional)</label>
                    <textarea name="notes" rows="3" placeholder="Any additional information..."></textarea>
                </div>
                
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                <input type="hidden" name="sponsorship_type" value="<?php echo $sponsorship_type; ?>">
                <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                
                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Payment Proof
                </button>
                
            </form>
        </div>
        
    </div>
    
</div>

<script>
// --- File Input Preview Logic (Unchanged from original) ---
document.getElementById('fileInput').addEventListener('change', function(e) {
    var file = e.target.files[0];
    var preview = document.getElementById('filePreview');
    
    if (file) {
        preview.style.display = 'block';
        
        if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            }
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '<p style="margin: 0;"><i class="fas fa-file-pdf"></i> ' + file.name + '</p>';
        }
    }
});

// --- New jQuery AJAX Submission Logic (Replaces the original fetch block) ---
// Note: This assumes jQuery has been enqueued correctly in your WordPress environment.
jQuery(document).ready(function($) {
    $('#paymentProofForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var submitBtn = $('#submitBtn');
        var alertBox = $('#alertBox');
        
        // Add action and nonce to formData
        formData.append('action', 'submit_payment_proof');
        formData.append('nonce', '<?php echo wp_create_nonce('payment_proof_nonce'); ?>');

        // Disable button and show loading state
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
        alertBox.hide().removeClass('alert-success alert-error').text('');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>', // Use the absolute path
            type: 'POST',
            data: formData,
            processData: false, // Important: Don't process the files
            contentType: false, // Important: Don't set content type
            dataType: 'json', // Expect JSON response
            
            success: function(response) {
                if (response.success) {
                    // Show brief success message and redirect
                    alertBox.addClass('alert-success').html('<i class="fas fa-check-circle"></i> ' + response.data.message).show();
                    
                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    if (response.data.redirect_url) {
                        // Redirect to dashboard
                        setTimeout(function() {
                             window.location.href = response.data.redirect_url;
                        }, 1500); // Wait 1.5 seconds before redirecting
                    } else {
                        // Fallback message and form reset if no redirect URL is provided by the server
                        form.trigger('reset');
                        $('#filePreview').hide().html('');
                        submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment Proof');
                    }
                } else {
                    // Handle server-side error
                    alertBox.addClass('alert-error').html('<i class="fas fa-exclamation-circle"></i> ' + response.data).show();
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment Proof');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX connection error
                alertBox.addClass('alert-error').html('<i class="fas fa-exclamation-circle"></i> Connection error or server issue. Please check the console for details.').show();
                submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment Proof');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                console.error('AJAX Error:', status, error, xhr);
            }
        });
    });
});
</script>

<?php get_footer(); ?>