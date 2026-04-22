<?php
/**
 * Mobile Menu Debug Test
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Menu Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f0f0f0; 
            padding: 20px;
        }
        .debug-box {
            background: white;
            border-left: 4px solid #FF6A00;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .debug-box h2 {
            color: #0B1C3D;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #333; }
        .value { color: #0B1C3D; font-family: monospace; }
        .success { color: #25D366; }
        .error { color: #FF4444; }
        .warning { color: #FF6A00; }
        button {
            background: #0B1C3D;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
        }
        button:hover { background: #1E3A8A; }
        .code-block {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 10px;
            color: #333;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="debug-box">
    <h2>📱 Mobile Menu Debug Information</h2>
    
    <div class="info-row">
        <span class="label">Viewport Width:</span>
        <span class="value" id="vwidth">Loading...</span>
    </div>
    <div class="info-row">
        <span class="label">Viewport Height:</span>
        <span class="value" id="vheight">Loading...</span>
    </div>
    <div class="info-row">
        <span class="label">Device Pixel Ratio:</span>
        <span class="value" id="dpr">Loading...</span>
    </div>
    <div class="info-row">
        <span class="label">Is Mobile (≤768px):</span>
        <span class="value" id="ismobile">Loading...</span>
    </div>
    
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <h3 style="color: #0B1C3D; margin-bottom: 10px;">🎯 Element Detection</h3>
        
        <div class="info-row">
            <span class="label">Header Toggle Button:</span>
            <span class="value" id="toggle-exists">Checking...</span>
        </div>
        <div class="info-row">
            <span class="label">Header Nav Container:</span>
            <span class="value" id="nav-exists">Checking...</span>
        </div>
        <div class="info-row">
            <span class="label">Nav Items Count:</span>
            <span class="value" id="items-count">Checking...</span>
        </div>
        <div class="info-row">
            <span class="label">Nav Menu Status:</span>
            <span class="value" id="menu-status">Closed</span>
        </div>
        <div class="info-row">
            <span class="label">Nav Computed Display:</span>
            <span class="value" id="nav-display">Checking...</span>
        </div>
        <div class="info-row">
            <span class="label">Nav Computed Position:</span>
            <span class="value" id="nav-position">Checking...</span>
        </div>
    </div>
    
    <button onclick="testMenuToggle()">🔧 Test Menu Toggle</button>
    <button onclick="window.location.href='/'">← Back to Home</button>
</div>

<div class="debug-box">
    <h2>📊 CSS Media Query Detection</h2>
    <div class="code-block" id="css-info">
        Checking CSS media queries...
    </div>
</div>

<script>
function updateDebugInfo() {
    const width = window.innerWidth;
    const height = window.innerHeight;
    const dpr = window.devicePixelRatio;
    const isMobile = width <= 768;
    
    document.getElementById('vwidth').textContent = width + 'px';
    document.getElementById('vheight').textContent = height + 'px';
    document.getElementById('dpr').textContent = dpr.toFixed(2);
    
    if (isMobile) {
        document.getElementById('ismobile').innerHTML = '<span class="success">✓ YES (Mobile Mode)</span>';
    } else {
        document.getElementById('ismobile').innerHTML = '<span class="warning">✗ NO (Desktop: ' + width + 'px)</span>';
    }
    
    // Check elements
    const toggle = document.getElementById('headerToggle');
    const nav = document.getElementById('headerNav');
    const items = nav ? nav.querySelectorAll('.nav-items > li') : [];
    
    document.getElementById('toggle-exists').innerHTML = toggle ? 
        '<span class="success">✓ Found</span>' : 
        '<span class="error">✗ Not Found</span>';
    
    document.getElementById('nav-exists').innerHTML = nav ? 
        '<span class="success">✓ Found</span>' : 
        '<span class="error">✗ Not Found</span>';
    
    document.getElementById('items-count').textContent = items.length + ' items';
    
    if (nav) {
        const style = window.getComputedStyle(nav);
        const isActive = nav.classList.contains('active');
        const transform = style.transform;
        const display = style.display;
        const position = style.position;
        
        document.getElementById('menu-status').innerHTML = isActive ? 
            '<span class="success">✓ OPEN</span>' : 
            '<span>Closed</span>';
        
        document.getElementById('nav-display').textContent = display;
        document.getElementById('nav-position').textContent = position;
    }
}

function testMenuToggle() {
    const toggle = document.getElementById('headerToggle');
    const nav = document.getElementById('headerNav');
    
    if (!toggle || !nav) {
        alert('Error: Menu elements not found!');
        return;
    }
    
    // Simulate click
    toggle.click();
    
    setTimeout(() => {
        updateDebugInfo();
        alert('Menu toggled! Check "Nav Menu Status" above.\n\nConsole logs:\n' + 
              'Active class: ' + nav.classList.contains('active'));
    }, 100);
}

// Update on load and window resize
window.addEventListener('load', updateDebugInfo);
window.addEventListener('resize', updateDebugInfo);

// Update every 500ms to catch dynamic changes
setInterval(updateDebugInfo, 500);
</script>
</body>
</html>
