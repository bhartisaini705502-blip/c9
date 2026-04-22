<?php
/**
 * Edit Business - Admin Panel
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

$business_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$business_id) {
    die('Invalid business ID');
}

// Fetch business details
$business = getRow("SELECT * FROM extracted_businesses WHERE id = ?", [$business_id], 'i');

if (!$business) {
    die('Business not found');
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $opening_hours = trim($_POST['opening_hours'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_level = intval($_POST['price_level'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);
    $reviews = intval($_POST['reviews'] ?? 0);
    
    // Service offerings
    $dine_in = isset($_POST['dine_in']) ? 1 : 0;
    $delivery = isset($_POST['delivery']) ? 1 : 0;
    $takeout = isset($_POST['takeout']) ? 1 : 0;
    $reservable = isset($_POST['reservable']) ? 1 : 0;
    $wheelchair_accessible = isset($_POST['wheelchair_accessible']) ? 1 : 0;
    
    // Status flags
    $verified = isset($_POST['verified']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;

    if ($name) {
        $update = $GLOBALS['conn']->prepare(
            "UPDATE extracted_businesses SET 
            name = ?, 
            formatted_address = ?,
            formatted_phone_number = ?, 
            website = ?, 
            opening_hours_weekday = ?,
            editorial_summary = ?,
            price_level = ?,
            rating = ?, 
            user_ratings_total = ?, 
            dine_in = ?,
            delivery = ?,
            takeout = ?,
            reservable = ?,
            wheelchair_accessible_entrance = ?,
            verified = ?, 
            is_featured = ? 
            WHERE id = ?"
        );
        
        $update->bind_param('ssssssiidiiiiiiii', $name, $address, $phone, $website, $opening_hours, $description, $price_level, $rating, $reviews, $dine_in, $delivery, $takeout, $reservable, $wheelchair_accessible, $verified, $featured, $business_id);
        
        if ($update->execute()) {
            $message = '✓ Business updated successfully!';
            // Refresh business data
            $business = getRow("SELECT * FROM extracted_businesses WHERE id = ?", [$business_id], 'i');
        } else {
            $error = 'Error updating business: ' . $GLOBALS['conn']->error;
        }
    } else {
        $error = 'Business name is required';
    }
}

// Handle AI description generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_description'])) {
    $name = $business['name'];
    $types = $business['types'];
    $rating = $business['rating'];
    $reviews = $business['user_ratings_total'];
    
    // Simple AI description template (can be enhanced with actual API)
    $type_array = array_filter(array_map('trim', explode(',', $types)), fn($t) => !empty($t));
    $primary_type = $type_array[0] ?? 'Business';
    
    $review_text = $reviews > 100 ? "highly rated" : ($reviews > 50 ? "well-reviewed" : "popular");
    $rating_text = $rating >= 4.5 ? "excellent" : ($rating >= 4 ? "great" : "good");
    
    $ai_description = "$name is an $rating_text $primary_type with a rating of $rating stars from $reviews customer reviews. We provide quality service and maintain high customer satisfaction standards. Visit us to experience our professional and friendly service.";
    
    $business['editorial_summary'] = $ai_description;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Business - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .admin-header {
            background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%);
            color: white;
            padding: 20px 15px;
            box-shadow: 0 4px 12px rgba(11, 28, 61, 0.3);
        }

        .admin-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .admin-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0B1C3D;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .back-link:hover {
            color: #FF6A00;
            margin-left: -5px;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #0B1C3D;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="url"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="url"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #FF6A00;
            box-shadow: 0 0 0 3px rgba(255, 106, 0, 0.1);
        }

        .checkbox-group {
            display: grid;
            gap: 12px;
            margin-bottom: 15px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }

        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }

        h3 {
            color: #0B1C3D;
            margin: 25px 0 15px;
            border-bottom: 2px solid #FF6A00;
            padding-bottom: 10px;
            font-size: 16px;
        }

        h3:first-child {
            margin-top: 0;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        button, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: #0B1C3D;
            color: white;
            flex: 1;
            min-width: 120px;
        }

        .btn-primary:hover {
            background: #1E3A8A;
        }

        .btn-secondary {
            background: #ddd;
            color: #333;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 120px;
        }

        .btn-secondary:hover {
            background: #ccc;
        }

        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 14px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #e7f3ff;
            color: #004085;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #0B1C3D;
            font-size: 14px;
        }

        /* Grid layouts for form fields */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row-three {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-header h1 {
                font-size: 20px;
            }

            .admin-container {
                padding: 15px;
            }

            .form-container {
                padding: 15px;
            }

            .form-row,
            .form-row-three {
                grid-template-columns: 1fr;
            }

            h3 {
                margin: 20px 0 12px;
                font-size: 15px;
            }

            .button-group {
                flex-direction: column;
            }

            button, .btn-secondary {
                width: 100%;
            }

            textarea {
                min-height: 70px;
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 15px 10px;
            }

            .admin-header h1 {
                font-size: 18px;
            }

            .admin-container {
                padding: 10px;
            }

            .form-container {
                padding: 12px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            label {
                margin-bottom: 5px;
                font-size: 13px;
            }

            input[type="text"],
            input[type="email"],
            input[type="url"],
            input[type="number"],
            textarea,
            select {
                padding: 8px;
                font-size: 13px;
            }

            h3 {
                margin: 15px 0 10px;
                font-size: 14px;
            }

            button, .btn-secondary {
                padding: 10px 15px;
                font-size: 13px;
            }

            textarea {
                min-height: 60px;
            }

            .checkbox-group {
                gap: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Edit Business</h1>
    </div>

    <div class="admin-container">
        <a href="businesses.php" class="back-link">← Back to Businesses</a>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="info">
            <strong>Business ID:</strong> <?php echo htmlspecialchars($business['id']); ?><br>
            <strong>Created:</strong> <?php echo htmlspecialchars($business['created_at'] ?? 'N/A'); ?>
        </div>

        <div class="form-container">
            <form method="POST">
                <!-- Basic Info -->
                <h3>Basic Information</h3>
                
                <div class="form-group">
                    <label for="name">Business Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($business['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($business['formatted_address'] ?? ''); ?>" placeholder="Complete business address">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($business['formatted_phone_number'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>" placeholder="https://...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="opening_hours">Opening Hours</label>
                    <textarea id="opening_hours" name="opening_hours" placeholder="Monday: 9AM–5PM&#10;Tuesday: 9AM–5PM&#10;etc."><?php echo htmlspecialchars($business['opening_hours_weekday'] ?? ''); ?></textarea>
                </div>

                <!-- Description -->
                <h3>Business Description</h3>
                
                <div class="form-group">
                    <label for="description">Business Description</label>
                    <textarea id="description" name="description" placeholder="Describe your business, services, and what makes you unique..."><?php echo htmlspecialchars($business['editorial_summary'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="generate_description" class="btn-secondary" style="width: auto; margin-bottom: 15px;">🤖 Generate with AI</button>

                <!-- Rating & Reviews -->
                <h3>Rating & Reviews</h3>
                
                <div class="form-row-three">
                    <div class="form-group">
                        <label for="rating">Rating (0-5)</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" step="0.1" value="<?php echo htmlspecialchars($business['rating'] ?? '0'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="reviews">Review Count</label>
                        <input type="number" id="reviews" name="reviews" min="0" value="<?php echo htmlspecialchars($business['user_ratings_total'] ?? '0'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="price_level">Price Level</label>
                        <select id="price_level" name="price_level">
                            <option value="0">Not specified</option>
                            <option value="1" <?php echo ($business['price_level'] == 1 ? 'selected' : ''); ?>>Affordable ($)</option>
                            <option value="2" <?php echo ($business['price_level'] == 2 ? 'selected' : ''); ?>>Moderate ($$)</option>
                            <option value="3" <?php echo ($business['price_level'] == 3 ? 'selected' : ''); ?>>Expensive ($$$)</option>
                            <option value="4" <?php echo ($business['price_level'] == 4 ? 'selected' : ''); ?>>Very Expensive ($$$$)</option>
                        </select>
                    </div>
                </div>

                <!-- Services & Amenities -->
                <h3>Services & Amenities</h3>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="dine_in" <?php echo ($business['dine_in'] ? 'checked' : ''); ?>>
                        Dine-in Available
                    </label>
                    <label>
                        <input type="checkbox" name="delivery" <?php echo ($business['delivery'] ? 'checked' : ''); ?>>
                        Delivery Available
                    </label>
                    <label>
                        <input type="checkbox" name="takeout" <?php echo ($business['takeout'] ? 'checked' : ''); ?>>
                        Takeout Available
                    </label>
                    <label>
                        <input type="checkbox" name="reservable" <?php echo ($business['reservable'] ? 'checked' : ''); ?>>
                        Reservations Accepted
                    </label>
                    <label>
                        <input type="checkbox" name="wheelchair_accessible" <?php echo ($business['wheelchair_accessible_entrance'] ? 'checked' : ''); ?>>
                        Wheelchair Accessible
                    </label>
                </div>

                <!-- Status -->
                <h3>Status</h3>
                
                <div class="checkbox-group" style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <label>
                        <input type="checkbox" name="verified" <?php echo ($business['verified'] ? 'checked' : ''); ?>>
                        ✓ Verified Business
                    </label>
                    <label>
                        <input type="checkbox" name="featured" <?php echo ($business['is_featured'] ? 'checked' : ''); ?>>
                        ⭐ Featured Listing
                    </label>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">💾 Save Changes</button>
                    <a href="businesses.php" class="btn-secondary" style="text-decoration: none; padding: 12px 24px; display: inline-block;">✕ Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
