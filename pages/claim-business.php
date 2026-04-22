<?php
/**
 * Claim Business Page
 * Business owners can claim and verify their listing
 */

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/tracking.php';

$business_id = $_GET['id'] ?? null;

if ($business_id) {
    $stmt = $conn->prepare("SELECT * FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $business_id);
    $stmt->execute();
    $business = $stmt->get_result()->fetch_assoc();
} else {
    $business = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Your Business - ConnectWith9</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .claim-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .claim-header {
            background: linear-gradient(135deg, #1E3A8A 0%, #0B1C3D 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 40px;
            text-align: center;
        }
        .claim-header h1 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        .claim-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .benefits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .benefit {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .benefit-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .benefit-title {
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 8px;
        }
        .benefit-desc {
            font-size: 14px;
            color: #666;
        }
        .claim-form {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 8px;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #FF6A00;
            box-shadow: 0 0 0 3px rgba(255,106,0,0.1);
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        .business-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #FF6A00;
        }
        .business-name {
            font-size: 18px;
            font-weight: 600;
            color: #0B1C3D;
            margin-bottom: 5px;
        }
        .business-address {
            font-size: 14px;
            color: #666;
        }
        .form-submit {
            background: #FF6A00;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .form-submit:hover {
            background: #E55A00;
        }
        .form-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .search-business {
            margin-bottom: 40px;
        }
        .search-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .search-btn {
            background: #1E3A8A;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-btn:hover {
            background: #0B1C3D;
        }
    </style>
</head>
<body>
    <div class="claim-container">
        <div class="claim-header">
            <h1>📋 Claim Your Business</h1>
            <p>Verify ownership and unlock premium features</p>
        </div>
        
        <?php if (!$business_id): ?>
        <div class="search-business">
            <h2 style="color: #0B1C3D; margin-bottom: 20px;">Find Your Business</h2>
            <div class="search-input-group">
                <input type="text" id="businessSearch" class="search-input" placeholder="Search by business name or location...">
                <button class="search-btn" onclick="searchBusinesses()">Search</button>
            </div>
            <div id="searchResults" style="margin-top: 20px;"></div>
        </div>
        <?php endif; ?>
        
        <?php if ($business): ?>
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">✅</div>
                <div class="benefit-title">Verified Badge</div>
                <div class="benefit-desc">Get a verified checkmark on your listing</div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">📊</div>
                <div class="benefit-title">Analytics</div>
                <div class="benefit-desc">Track views, clicks, and inquiries</div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">⭐</div>
                <div class="benefit-title">Manage Reviews</div>
                <div class="benefit-desc">Respond to customer reviews</div>
            </div>
            <div class="benefit">
                <div class="benefit-icon">💎</div>
                <div class="benefit-title">Premium Tools</div>
                <div class="benefit-desc">Upgrade for advanced features</div>
            </div>
        </div>
        
        <div class="claim-form">
            <div class="message" id="message"></div>
            
            <div class="business-info">
                <div class="business-name"><?php echo htmlspecialchars($business['name']); ?></div>
                <div class="business-address"><?php echo htmlspecialchars($business['address'] ?? ''); ?></div>
            </div>
            
            <form id="claimForm" onsubmit="submitClaim(event)">
                <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="owner_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Your Role at Business *</label>
                    <select name="role" class="form-input" required>
                        <option value="">Select...</option>
                        <option value="owner">Owner</option>
                        <option value="manager">Manager</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message (Optional)</label>
                    <textarea name="message" class="form-textarea" placeholder="Tell us why you want to claim this business..."></textarea>
                </div>
                
                <button type="submit" class="form-submit">📨 Submit Claim Request</button>
            </form>
        </div>
        
        <?php else: ?>
        <p style="text-align: center; color: #666; margin-top: 40px;">Please search for your business above to continue.</p>
        <?php endif; ?>
    </div>
    
    <script>
        async function searchBusinesses() {
            const query = document.getElementById('businessSearch').value;
            if (!query) return;
            
            try {
                const response = await fetch(`/api/search-businesses.php?q=${encodeURIComponent(query)}&limit=10`);
                const data = await response.json();
                
                const resultsDiv = document.getElementById('searchResults');
                if (data.success && data.businesses.length > 0) {
                    let html = '<div style="margin-top: 20px;"><strong>Found Businesses:</strong><div style="margin-top: 15px;">';
                    
                    data.businesses.forEach(business => {
                        html += `
                            <div style="background: white; border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin-bottom: 10px;">
                                <strong>${business.name}</strong><br>
                                <small>${business.address}</small><br>
                                <a href="?id=${business.id}" style="color: #FF6A00; font-weight: 600; text-decoration: none;">Claim This Business →</a>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<p style="color: #666;">No businesses found. Try another search.</p>';
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }
        
        async function submitClaim(e) {
            e.preventDefault();
            
            const form = document.getElementById('claimForm');
            const message = document.getElementById('message');
            const btn = form.querySelector('button[type=submit]');
            
            btn.disabled = true;
            
            try {
                const formData = new FormData(form);
                const response = await fetch('/api/claim-business.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    message.className = 'message success';
                    message.textContent = '✓ Claim request submitted! We\'ll verify your ownership within 24 hours.';
                    message.style.display = 'block';
                    form.style.display = 'none';
                } else {
                    message.className = 'message error';
                    message.textContent = '✗ ' + (data.error || 'Error submitting claim');
                    message.style.display = 'block';
                }
            } catch (error) {
                message.className = 'message error';
                message.textContent = '✗ Error: ' + error.message;
                message.style.display = 'block';
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
