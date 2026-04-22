<?php
$page_title = "Keyword Research | AI-Powered SEO Tool";
$meta_description = "AI-powered keyword research and analysis tool for SEO optimization.";
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">🔍 Keyword Research</h1>
        <p style="color: #666;">Discover high-performing keywords for your business</p>
        
        <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form id="keywordForm" style="display: grid; gap: 15px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🔍 Keyword</label>
                    <input type="text" name="topic" id="topic" placeholder="e.g., digital marketing" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" required>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌍 Country</label>
                    <select name="country" id="country" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option>India</option>
                        <option>USA</option>
                        <option>UK</option>
                        <option>Canada</option>
                        <option>Australia</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📍 State</label>
                    <input type="text" name="state" id="state" placeholder="Optional" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🏙️ City</label>
                    <input type="text" name="city" id="city" placeholder="Optional" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📖 Language</label>
                    <select name="language" id="language" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option>English</option>
                        <option>Hindi</option>
                        <option>Spanish</option>
                        <option>French</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📅 Time Period</label>
                    <select name="duration" id="duration" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option>Last 30 Days</option>
                        <option>Last 90 Days</option>
                        <option>Last Year</option>
                        <option>All Time</option>
                    </select>
                </div>
            </form>
            <button type="button" onclick="analyzeKeywords()" style="margin-top: 15px; padding: 12px 30px; background: linear-gradient(135deg, #4A5FFF, #5A6FFF); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px;">
                🔍 Search Keywords
            </button>
        </div>
        
        <div id="results" style="display: none; margin-top: 30px;">
            <div id="metricCards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0;">📊 Results</h3>
                <div>
                    <input type="text" id="filterInput" placeholder="Filter keywords..." onkeyup="filterResults()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; margin-right: 10px; width: 200px;">
                    <button onclick="exportCSV()" style="padding: 8px 15px; background: white; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 500;">⬇️ Export CSV</button>
                </div>
            </div>
            
            <div id="resultsContent" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <!-- Results table will be inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
let allKeywords = [];

function analyzeKeywords() {
    const topic = document.getElementById('topic').value.trim();
    const country = document.getElementById('country').value.trim();
    const language = document.getElementById('language').value;
    
    if (!topic || !country) {
        alert('Please fill in all required fields');
        return;
    }
    
    allKeywords = generateKeywordSuggestions(topic);
    displayResults(allKeywords, topic, country, language);
}

function generateKeywordSuggestions(topic) {
    const keywords = [
        { keyword: `${topic}`, volume: Math.floor(Math.random() * 5000) + 5000, difficulty: Math.floor(Math.random() * 40) + 40, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
        { keyword: `best ${topic}`, volume: Math.floor(Math.random() * 3000) + 3000, difficulty: Math.floor(Math.random() * 35) + 35, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
        { keyword: `${topic} near me`, volume: Math.floor(Math.random() * 2000) + 2000, difficulty: Math.floor(Math.random() * 25) + 20, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
        { keyword: `${topic} tips`, volume: Math.floor(Math.random() * 1500) + 1500, difficulty: Math.floor(Math.random() * 30) + 25, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
        { keyword: `how to ${topic}`, volume: Math.floor(Math.random() * 2500) + 2500, difficulty: Math.floor(Math.random() * 32) + 28, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
        { keyword: `affordable ${topic}`, volume: Math.floor(Math.random() * 1000) + 1000, difficulty: Math.floor(Math.random() * 20) + 15, cpcLow: (Math.random() * 2 + 0.5).toFixed(2), cpcHigh: (Math.random() * 5 + 3).toFixed(2), trend: Math.floor(Math.random() * 40) - 20 },
    ];
    return keywords;
}

function displayResults(keywords, topic, country, language) {
    document.getElementById('results').style.display = 'block';
    
    // Calculate metrics
    const totalKeywords = keywords.length;
    const avgVolume = Math.round(keywords.reduce((a, b) => a + b.volume, 0) / keywords.length);
    const avgCPC = (keywords.reduce((a, b) => a + parseFloat(b.cpcLow), 0) / keywords.length).toFixed(2);
    const highCompetition = keywords.filter(k => k.difficulty > 60).length;
    
    // Display metric cards
    let metricHTML = `
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${totalKeywords}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">KEYWORDS FOUND</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${avgVolume.toLocaleString()}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">AVG SEARCH VOLUME</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">$${avgCPC}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">AVG CPC</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${highCompetition}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">HIGH COMPETITION</div>
        </div>
    `;
    document.getElementById('metricCards').innerHTML = metricHTML;
    
    // Display table
    let tableHTML = '<table style="width: 100%; border-collapse: collapse;"><thead style="background: #f5f5f5; border-bottom: 2px solid #ddd;"><tr>';
    tableHTML += '<th style="padding: 12px; text-align: left; font-weight: 600;">#</th>';
    tableHTML += '<th style="padding: 12px; text-align: left; font-weight: 600;">KEYWORD</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">SEARCH VOLUME</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">COMPETITION</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">CPC LOW</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">CPC HIGH</th>';
    tableHTML += '<th style="padding: 12px; text-align: center; font-weight: 600;">TREND</th>';
    tableHTML += '</tr></thead><tbody>';
    
    keywords.forEach((kw, idx) => {
        const compColor = kw.difficulty < 25 ? '#4CAF50' : kw.difficulty < 50 ? '#FF9800' : '#f44336';
        const compLabel = kw.difficulty < 25 ? 'LOW' : kw.difficulty < 50 ? 'MEDIUM' : 'HIGH';
        const trendColor = kw.trend > 0 ? '#4CAF50' : '#f44336';
        const trendSymbol = kw.trend > 0 ? '📈' : '📉';
        
        tableHTML += '<tr style="border-bottom: 1px solid #eee;"><td style="padding: 12px;">${idx + 1}</td>';
        tableHTML += '<td style="padding: 12px;"><strong>🔗 ${kw.keyword}</strong></td>';
        tableHTML += '<td style="padding: 12px; text-align: center;">${kw.volume.toLocaleString()}</td>';
        tableHTML += '<td style="padding: 12px; text-align: center;"><span style="color: ${compColor}; font-weight: 600;">${compLabel}</span></td>';
        tableHTML += '<td style="padding: 12px; text-align: center;">$${kw.cpcLow}</td>';
        tableHTML += '<td style="padding: 12px; text-align: center;">$${kw.cpcHigh}</td>';
        tableHTML += '<td style="padding: 12px; text-align: center;"><span style="color: ${trendColor};">${trendSymbol} ${Math.abs(kw.trend)}%</span></td>';
        tableHTML += '</tr>';
    });
    
    tableHTML += '</tbody></table>';
    document.getElementById('resultsContent').innerHTML = tableHTML;
}

function filterResults() {
    const filter = document.getElementById('filterInput').value.toLowerCase();
    const filtered = allKeywords.filter(kw => kw.keyword.toLowerCase().includes(filter));
    displayResults(filtered, filter || 'Keywords', 'All', 'All');
}

function exportCSV() {
    if (allKeywords.length === 0) {
        alert('No results to export');
        return;
    }
    let csv = 'Keyword,Search Volume,Difficulty,CPC Low,CPC High,Trend\n';
    allKeywords.forEach(kw => {
        csv += `"${kw.keyword}",${kw.volume},"${kw.difficulty}%",${kw.cpcLow},${kw.cpcHigh},${kw.trend}%\n`;
    });
    const blob = new Blob([csv], {type: 'text/csv'});
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'keyword-research.csv';
    a.click();
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
