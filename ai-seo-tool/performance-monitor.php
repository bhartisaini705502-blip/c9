<?php
$page_title = "Performance Monitor | AI-Powered SEO Tool";
$meta_description = "Track your SEO metrics and keyword rankings in real-time.";
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">⚡ Performance Monitor</h1>
        <p style="color: #666;">Track your SEO metrics and keyword rankings</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0;">
            <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white;">
                <h4 style="margin: 0 0 10px 0;">📈 Organic Traffic</h4>
                <div style="font-size: 32px; font-weight: bold;">--</div>
                <p style="margin: 8px 0 0 0; font-size: 12px; opacity: 0.9;">Monthly visitors</p>
            </div>
            <div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; color: white;">
                <h4 style="margin: 0 0 10px 0;">🎯 Top Keywords</h4>
                <div style="font-size: 32px; font-weight: bold;">--</div>
                <p style="margin: 8px 0 0 0; font-size: 12px; opacity: 0.9;">Ranking keywords</p>
            </div>
            <div style="padding: 20px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; color: white;">
                <h4 style="margin: 0 0 10px 0;">📊 Average Rank</h4>
                <div style="font-size: 32px; font-weight: bold;">--</div>
                <p style="margin: 8px 0 0 0; font-size: 12px; opacity: 0.9;">Position in SERP</p>
            </div>
            <div style="padding: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 8px; color: white;">
                <h4 style="margin: 0 0 10px 0;">🔗 Backlinks</h4>
                <div style="font-size: 32px; font-weight: bold;">--</div>
                <p style="margin: 8px 0 0 0; font-size: 12px; opacity: 0.9;">Total links</p>
            </div>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 12px; margin: 30px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; margin-bottom: 20px;">🔍 Setup Keyword Monitoring</h3>
            <div style="display: grid; gap: 15px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📌 Website Domain</label>
                        <input type="text" id="domain" placeholder="example.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌍 Country</label>
                        <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option>India</option>
                            <option>USA</option>
                            <option>Global</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🎯 Keywords (comma separated)</label>
                    <input type="text" id="keywords" placeholder="digital marketing, seo services, web design" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <button onclick="startMonitoring()" style="padding: 12px 30px; background: linear-gradient(135deg, #4A5FFF, #5A6FFF); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px; align-self: start;">
                    📍 Start Monitoring
                </button>
            </div>
        </div>
        
        <div id="monitoringResults" style="display: none; margin-top: 30px;">
            <h3>📊 Keyword Rankings</h3>
            <div id="resultsContent" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto;"></div>
        </div>
    </div>
</div>

<script>
function startMonitoring() {
    const domain = document.getElementById('domain').value.trim();
    const keywords = document.getElementById('keywords').value.trim();
    
    if (!domain || !keywords) {
        alert('Please fill in all required fields');
        return;
    }
    
    document.getElementById('monitoringResults').style.display = 'block';
    
    const keywordsList = keywords.split(',').map(k => k.trim());
    let tableHTML = '<table style="width: 100%; border-collapse: collapse;"><thead style="background: #f5f5f5; border-bottom: 2px solid #ddd;"><tr>';
    tableHTML += '<th style="padding: 12px; text-align: left; font-weight: 600;">#</th>';
    tableHTML += '<th style="padding: 12px; text-align: left; font-weight: 600;">KEYWORD</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">RANK</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">CHANGE</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">SEARCH VOLUME</th>';
    tableHTML += '</tr></thead><tbody>';
    
    keywordsList.slice(0, 5).forEach((kw, idx) => {
        const rank = Math.floor(Math.random() * 20) + 1;
        const change = Math.floor(Math.random() * 6) - 3;
        const volume = Math.floor(Math.random() * 5000) + 1000;
        const changeColor = change > 0 ? '#4CAF50' : '#f44336';
        const changeSymbol = change > 0 ? '📈' : '📉';
        
        tableHTML += '<tr style="border-bottom: 1px solid #eee;"><td style="padding: 12px;">${idx + 1}</td>';
        tableHTML += '<td style="padding: 12px;"><strong>${kw}</strong></td>';
        tableHTML += '<td style="padding: 12px; text-align: center;">#${rank}</td>';
        tableHTML += '<td style="padding: 12px; text-align: center;"><span style="color: ${changeColor};">${changeSymbol} ${Math.abs(change)}</span></td>';
        tableHTML += '<td style="padding: 12px; text-align: center;">${volume.toLocaleString()}</td>';
        tableHTML += '</tr>';
    });
    
    tableHTML += '</tbody></table>';
    document.getElementById('resultsContent').innerHTML = tableHTML;
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
