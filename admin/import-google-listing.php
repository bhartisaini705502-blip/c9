<?php
/**
 * Admin: Import Single Google Map Listing
 * Import a business from Google Places API to local directory
 */

session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/google-api.php';

// Check admin access
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$page_title = 'Import Google Listing';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>🔗 Import Google Map Listing</h1>
        <p>Add a business from Google Places to your local directory</p>
    </div>

    <div class="admin-content">
        <div class="import-form-wrapper">
            <!-- Import Method Tabs -->
            <div class="method-tabs">
                <button class="tab-btn active" data-method="place-id">By Place ID</button>
                <button class="tab-btn" data-method="search">By Search Query</button>
            </div>

            <!-- Place ID Method -->
            <div class="method-content active" id="place-id-method">
                <div class="form-section">
                    <label for="placeId">Google Place ID:</label>
                    <input type="text" id="placeId" placeholder="e.g., ChIJN1blFLsB1AgAqeseXxYGEAE" class="form-control">
                    <small>Find Place ID on Google Maps - it's in the URL after 'place_id=' or in the API response</small>
                </div>
                <button class="btn btn-primary" onclick="importByPlaceId()">Import by Place ID</button>
            </div>

            <!-- Search Method -->
            <div class="method-content" id="search-method">
                <div class="form-section">
                    <label for="businessName">Business Name:</label>
                    <input type="text" id="businessName" placeholder="e.g., Pizza House, Delhi" class="form-control">
                </div>
                <div class="form-section">
                    <label for="businessLocation">Location (Optional):</label>
                    <input type="text" id="businessLocation" placeholder="e.g., New Delhi, India" class="form-control">
                </div>
                <button class="btn btn-primary" onclick="importBySearch()">Search & Import</button>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" style="display: none; margin-top: 30px;">
                <h3>Google Places Details</h3>
                <div id="listingPreview" class="listing-preview"></div>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="confirmImport()">✓ Confirm & Import</button>
                    <button class="btn btn-secondary" onclick="cancelImport()">✕ Cancel</button>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingSpinner" style="display: none; text-align: center; margin-top: 20px;">
                <div class="spinner"></div>
                <p>Fetching from Google Places...</p>
            </div>

            <!-- Messages -->
            <div id="messageBox" style="margin-top: 20px;"></div>
        </div>

        <!-- Recent Imports -->
        <div class="recent-imports-section" style="margin-top: 40px;">
            <h3>📋 Recent Imports</h3>
            <div id="recentImports" class="recent-list"></div>
        </div>
    </div>
</div>

<style>
.admin-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    margin-bottom: 30px;
    border-bottom: 2px solid #1E3A8A;
    padding-bottom: 20px;
}

.admin-header h1 {
    color: #0B1C3D;
    margin: 0;
    font-size: 28px;
}

.admin-header p {
    color: #666;
    margin: 8px 0 0 0;
}

.method-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 1px solid #ddd;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}

.tab-btn.active {
    color: #1E3A8A;
    border-bottom-color: #1E3A8A;
}

.tab-btn:hover {
    color: #1E3A8A;
}

.method-content {
    display: none;
}

.method-content.active {
    display: block;
}

.form-section {
    margin-bottom: 20px;
}

.form-section label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #0B1C3D;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #1E3A8A;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

.form-section small {
    display: block;
    margin-top: 6px;
    color: #999;
    font-size: 12px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #1E3A8A, #2D5BBE);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.listing-preview {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.preview-item {
    margin-bottom: 15px;
}

.preview-item strong {
    color: #0B1C3D;
    display: inline-block;
    width: 120px;
}

.preview-item span {
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.spinner {
    border: 4px solid #f3f4f6;
    border-top: 4px solid #1E3A8A;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.message {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.message.warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.recent-imports-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.recent-imports-section h3 {
    color: #0B1C3D;
    margin-top: 0;
}

.recent-list {
    max-height: 300px;
    overflow-y: auto;
}

.recent-item {
    padding: 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 14px;
}

.recent-item-name {
    font-weight: 600;
    color: #0B1C3D;
}

.recent-item-time {
    color: #999;
    font-size: 12px;
    margin-top: 4px;
}
</style>

<script>
let currentListingData = null;

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const method = this.dataset.method;
        
        // Update buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Update content
        document.querySelectorAll('.method-content').forEach(c => c.classList.remove('active'));
        document.getElementById(method + '-method').classList.add('active');
        
        // Clear previous results
        resetForm();
    });
});

async function importByPlaceId() {
    const placeId = document.getElementById('placeId').value.trim();
    
    if (!placeId) {
        showMessage('Please enter a Place ID', 'error');
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch('/api/import-google-listing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                method: 'place_id',
                place_id: placeId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentListingData = data.listing;
            displayPreview(data.listing);
        } else {
            showMessage(data.error || 'Failed to fetch listing', 'error');
        }
    } catch (error) {
        showMessage('Error: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function importBySearch() {
    const name = document.getElementById('businessName').value.trim();
    const location = document.getElementById('businessLocation').value.trim();
    
    if (!name) {
        showMessage('Please enter a business name', 'error');
        return;
    }
    
    showLoading(true);
    
    try {
        const response = await fetch('/api/import-google-listing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                method: 'search',
                name: name,
                location: location
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.listings && data.listings.length > 0) {
                currentListingData = data.listings[0];
                displayPreview(currentListingData);
            } else {
                showMessage('No businesses found. Try a different search.', 'warning');
            }
        } else {
            showMessage(data.error || 'Search failed', 'error');
        }
    } catch (error) {
        showMessage('Error: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function displayPreview(listing) {
    const preview = `
        <div class="preview-item"><strong>Name:</strong> <span>${escapeHtml(listing.name)}</span></div>
        <div class="preview-item"><strong>Address:</strong> <span>${escapeHtml(listing.address || 'N/A')}</span></div>
        <div class="preview-item"><strong>Rating:</strong> <span>⭐ ${listing.rating || 'N/A'}</span></div>
        <div class="preview-item"><strong>Reviews:</strong> <span>${listing.reviews || 0}</span></div>
        <div class="preview-item"><strong>Category:</strong> <span>${escapeHtml(listing.category || 'N/A')}</span></div>
        <div class="preview-item"><strong>Phone:</strong> <span>${escapeHtml(listing.phone || 'N/A')}</span></div>
        <div class="preview-item"><strong>Website:</strong> <span>${escapeHtml(listing.website || 'N/A')}</span></div>
    `;
    
    document.getElementById('listingPreview').innerHTML = preview;
    document.getElementById('resultsSection').style.display = 'block';
    document.getElementById('messageBox').innerHTML = '';
}

async function confirmImport() {
    if (!currentListingData) return;
    
    showLoading(true);
    
    try {
        const response = await fetch('/api/import-google-listing.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                method: 'confirm',
                listing: currentListingData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('✓ Listing imported successfully! Business ID: ' + data.business_id, 'success');
            resetForm();
            loadRecentImports();
        } else {
            showMessage(data.error || 'Import failed', 'error');
        }
    } catch (error) {
        showMessage('Error: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function cancelImport() {
    resetForm();
}

function resetForm() {
    document.getElementById('placeId').value = '';
    document.getElementById('businessName').value = '';
    document.getElementById('businessLocation').value = '';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('messageBox').innerHTML = '';
    currentListingData = null;
}

function showLoading(show) {
    document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
}

function showMessage(msg, type) {
    const box = document.getElementById('messageBox');
    box.innerHTML = `<div class="message ${type}">${escapeHtml(msg)}</div>`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function loadRecentImports() {
    fetch('/api/import-google-listing.php?action=recent')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.imports) {
                const html = data.imports.length > 0 
                    ? data.imports.map(imp => `
                        <div class="recent-item">
                            <div class="recent-item-name">${escapeHtml(imp.name)}</div>
                            <div class="recent-item-time">${imp.timestamp}</div>
                        </div>
                    `).join('')
                    : '<p style="color: #999; text-align: center;">No recent imports</p>';
                
                document.getElementById('recentImports').innerHTML = html;
            }
        })
        .catch(e => console.error('Failed to load recent imports:', e));
}

// Load recent imports on page load
loadRecentImports();
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
