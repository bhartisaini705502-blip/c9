<?php
/**
 * SaaS Platform Migration
 * Creates: clients, invoices, and updates leads table
 * Run once: navigate to /config/migrate-saas-system.php
 */

require_once 'db.php';

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>SaaS System Migration</h2>";

// ============================================
// 1. CREATE CLIENTS TABLE
// ============================================
$check_clients = $conn->query("SHOW TABLES LIKE 'clients'");

if ($check_clients && $check_clients->num_rows > 0) {
    echo "✓ Clients table already exists<br>";
} else {
    $sql = "CREATE TABLE clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        company VARCHAR(100),
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_status (status)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Clients table created<br>";
    } else {
        echo "✗ Error creating clients table: " . $conn->error . "<br>";
    }
}

// ============================================
// 2. CREATE INVOICES TABLE
// ============================================
$check_invoices = $conn->query("SHOW TABLES LIKE 'invoices'");

if ($check_invoices && $check_invoices->num_rows > 0) {
    echo "✓ Invoices table already exists<br>";
} else {
    $sql = "CREATE TABLE invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        description VARCHAR(255),
        amount DECIMAL(10,2) NOT NULL,
        tax DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        payment_method VARCHAR(50),
        due_date DATE,
        paid_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id),
        INDEX idx_client_id (client_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Invoices table created<br>";
    } else {
        echo "✗ Error creating invoices table: " . $conn->error . "<br>";
    }
}

// ============================================
// 3. UPDATE LEADS TABLE - ADD CLIENT_ID
// ============================================
$check_client_id = $conn->query("SHOW COLUMNS FROM leads LIKE 'client_id'");

if ($check_client_id && $check_client_id->num_rows > 0) {
    echo "✓ Lead client_id column already exists<br>";
} else {
    $sql = "ALTER TABLE leads ADD client_id INT DEFAULT NULL, ADD FOREIGN KEY (client_id) REFERENCES clients(id)";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Lead client_id column added<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 4. UPDATE LEADS TABLE - PIPELINE STAGES
// ============================================
$check_pipeline = $conn->query("SHOW COLUMNS FROM leads LIKE 'pipeline_stage'");

if ($check_pipeline && $check_pipeline->num_rows > 0) {
    echo "✓ Lead pipeline_stage column already exists<br>";
} else {
    $sql = "ALTER TABLE leads ADD pipeline_stage VARCHAR(50) DEFAULT 'new'";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Lead pipeline_stage column added<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 5. CREATE WHATSAPP TEMPLATES TABLE
// ============================================
$check_templates = $conn->query("SHOW TABLES LIKE 'whatsapp_templates'");

if ($check_templates && $check_templates->num_rows > 0) {
    echo "✓ WhatsApp templates table already exists<br>";
} else {
    $sql = "CREATE TABLE whatsapp_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ WhatsApp templates table created<br>";
        
        // Insert default templates
        $templates = [
            ['Initial Contact', 'Thank you for contacting ConnectWith! Our team will get back to you shortly.'],
            ['Follow Up', 'Hi {name}, just checking in on your {service} inquiry. When would be a good time to discuss?'],
            ['Proposal Ready', 'Good news! We\'ve prepared a customized proposal for your {service}. Would you like to review it?'],
            ['Urgent Follow Up', 'Hi {name}, we\'d love to help with your {service} needs. Are you still interested?']
        ];
        
        foreach ($templates as $template) {
            $conn->query("INSERT INTO whatsapp_templates (name, message) VALUES ('{$template[0]}', '{$template[1]}')");
        }
        echo "✓ Default WhatsApp templates inserted<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

// ============================================
// 6. CREATE PAYMENT TRACKING TABLE
// ============================================
$check_payments = $conn->query("SHOW TABLES LIKE 'payments'");

if ($check_payments && $check_payments->num_rows > 0) {
    echo "✓ Payments table already exists<br>";
} else {
    $sql = "CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        status VARCHAR(50) DEFAULT 'completed',
        paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(id),
        INDEX idx_invoice_id (invoice_id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Payments table created<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<h3>✅ Migration Complete!</h3>";
echo "<p>All SaaS tables created successfully. You can now:</p>";
echo "<ul>";
echo "<li>1. Create client logins</li>";
echo "<li>2. Manage lead pipeline</li>";
echo "<li>3. Generate invoices</li>";
echo "<li>4. Track payments</li>";
echo "</ul>";

$conn->close();
?>
