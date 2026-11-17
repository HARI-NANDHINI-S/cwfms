<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if (!hasPermission('create')) {
    header('Location: ../dashboard.php?error=permission');
    exit;
}

$user     = getCurrentUser();
$initials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $user['name']))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Customers | Ira Sportswear</title>
    <link rel="icon" type="image/png" href="../assets/favicon.png">
    <link rel="shortcut icon" href="../assets/favicon.ico">
    <script>(function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .import-zone { border: 2px dashed var(--border-color); border-radius: 12px; padding: 48px 24px; text-align: center; cursor: pointer; transition: all 0.2s; background: var(--bg-secondary); }
        .import-zone:hover, .import-zone.dragover { border-color: var(--accent); background: rgba(229,57,53,0.04); }
        .import-zone .upload-icon { width: 56px; height: 56px; background: rgba(229,57,53,0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; color: var(--accent); }
        .import-zone h3 { font-size: 1rem; margin-bottom: 6px; }
        .import-zone p { font-size: 0.83rem; color: var(--text-muted); }
        .import-zone input[type=file] { display: none; }
        .template-section { background: var(--bg-secondary); border-radius: 10px; padding: 16px; margin-top: 20px; }
        .template-section h4 { font-size: 0.85rem; font-weight: 600; margin-bottom: 10px; }
        .column-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .column-tag { font-size: 0.73rem; padding: 3px 9px; border-radius: 20px; border: 1px solid var(--border-color); color: var(--text-secondary); font-family: monospace; }
        .column-tag.required { border-color: rgba(229,57,53,0.4); color: var(--accent); background: rgba(229,57,53,0.06); }
        .progress-bar-wrap { height: 6px; background: var(--border-color); border-radius: 20px; overflow: hidden; margin: 10px 0; }
        .progress-bar-fill { height: 100%; background: var(--accent); border-radius: 20px; transition: width 0.3s ease; }
        .result-row { display: flex; align-items: center; gap: 8px; font-size: 0.83rem; padding: 6px 0; border-bottom: 1px solid var(--border-color); }
        .result-row:last-child { border-bottom: none; }
        .result-icon { width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.65rem; }
        .result-icon.ok { background: rgba(67,160,71,0.15); color: #43a047; }
        .result-icon.err { background: rgba(229,57,53,0.15); color: var(--accent); }
        .result-icon.warn { background: rgba(251,140,0,0.15); color: #fb8c00; }
        .steps { display: flex; gap: 8px; margin-bottom: 28px; }
        .step { flex: 1; display: flex; align-items: center; gap: 8px; }
        .step-num { width: 26px; height: 26px; border-radius: 50%; background: var(--border-color); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; }
        .step.active .step-num { background: var(--accent); color: #fff; }
        .step.done .step-num { background: #43a047; color: #fff; }
        .step-label { font-size: 0.8rem; color: var(--text-muted); }
        .step.active .step-label { color: var(--text-primary); font-weight: 600; }
        .step-divider { flex: 0 0 20px; height: 1px; background: var(--border-color); }
    </style>
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/ira_logo_web.png" alt="Ira Sportswear Logo" class="brand-logo-img">
            <div class="brand-text"><h2>Ira Sportswear</h2><span>Lead Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="../dashboard.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="../dashboard.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Customers
            </a>
            <a href="import.php" class="nav-item active" style="border-left-color:var(--accent)">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import CSV
            </a>
        </nav>
        <div class="theme-toggle-wrap">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <span class="theme-label-text">Light Mode</span>
                <div class="toggle-track"></div>
            </button>
        </div>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
                <a href="../logout.php" style="margin-left:auto;color:var(--text-muted)" title="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div class="header-title">
                <div class="breadcrumb" style="font-size:0.83rem">
                    <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none">Customers</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
                    <span>Import CSV</span>
                </div>
                <h1 style="margin-top:4px">Import Customers</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary btn-sm" onclick="downloadTemplate()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download Template
                </button>
            </div>
        </header>

        <div class="page-content">
            <!-- Steps -->
            <div class="steps">
                <div class="step active" id="step1"><div class="step-num">1</div><div class="step-label">Upload File</div></div>
                <div class="step-divider"></div>
                <div class="step" id="step2"><div class="step-num">2</div><div class="step-label">Validate</div></div>
                <div class="step-divider"></div>
                <div class="step" id="step3"><div class="step-num">3</div><div class="step-label">Import</div></div>
                <div class="step-divider"></div>
                <div class="step" id="step4"><div class="step-num">4</div><div class="step-label">Done</div></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

                <!-- Main Import Panel -->
                <div>
                    <!-- Step 1: Upload -->
                    <div class="card" id="panelUpload">
                        <div class="card-header-simple"><h3>Upload CSV File</h3></div>
                        <div style="padding:20px">
                            <div class="import-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                                <input type="file" id="fileInput" accept=".csv">
                                <div class="upload-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                                <h3>Click to upload or drag & drop</h3>
                                <p>CSV files only · Max 5MB · UTF-8 encoding recommended</p>
                                <div id="selectedFile" style="display:none;margin-top:12px;padding:8px 14px;background:rgba(229,57,53,0.08);border-radius:8px;font-size:0.83rem;color:var(--accent);font-weight:600"></div>
                            </div>

                            <div id="parseResults" style="display:none;margin-top:16px">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                                    <h4 style="font-size:0.9rem">Preview (first 5 rows)</h4>
                                    <span id="parseStats" style="font-size:0.8rem;color:var(--text-muted)"></span>
                                </div>
                                <div style="overflow-x:auto;border-radius:8px;border:1px solid var(--border-color)">
                                    <table class="data-table" id="previewTable" style="font-size:0.78rem;min-width:600px">
                                        <thead id="previewHead"></thead>
                                        <tbody id="previewBody"></tbody>
                                    </table>
                                </div>
                                <div id="parseErrors" style="display:none;margin-top:12px"></div>
                            </div>

                            <div style="display:flex;gap:10px;margin-top:20px">
                                <button class="btn btn-secondary" onclick="resetImport()">Reset</button>
                                <button class="btn btn-primary" id="btnValidate" style="display:none" onclick="validateData()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><polyline points="20 6 9 17 4 12"/></svg>
                                    Validate Data
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2+3: Validation + Import -->
                    <div class="card" id="panelImport" style="display:none;margin-top:16px">
                        <div class="card-header-simple">
                            <h3 id="importPanelTitle">Validation Results</h3>
                        </div>
                        <div style="padding:20px">
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
                                <div class="stat-card" style="padding:14px">
                                    <div class="stat-icon green" style="width:32px;height:32px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><polyline points="20 6 9 17 4 12"/></svg></div>
                                    <div class="stat-info"><div class="stat-value" id="countValid" style="font-size:1.4rem">0</div><div class="stat-label">Valid Rows</div></div>
                                </div>
                                <div class="stat-card" style="padding:14px">
                                    <div class="stat-icon red" style="width:32px;height:32px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></div>
                                    <div class="stat-info"><div class="stat-value" id="countInvalid" style="font-size:1.4rem">0</div><div class="stat-label">Invalid Rows</div></div>
                                </div>
                                <div class="stat-card" style="padding:14px">
                                    <div class="stat-icon blue" style="width:32px;height:32px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                                    <div class="stat-info"><div class="stat-value" id="countTotal" style="font-size:1.4rem">0</div><div class="stat-label">Total Rows</div></div>
                                </div>
                            </div>

                            <div id="validationLog" style="max-height:280px;overflow-y:auto;border:1px solid var(--border-color);border-radius:8px;padding:12px 16px"></div>

                            <div id="importProgress" style="display:none;margin-top:16px">
                                <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:var(--text-muted);margin-bottom:4px">
                                    <span>Importing…</span>
                                    <span id="progressText">0%</span>
                                </div>
                                <div class="progress-bar-wrap">
                                    <div class="progress-bar-fill" id="progressFill" style="width:0%"></div>
                                </div>
                            </div>

                            <div style="display:flex;gap:10px;margin-top:20px">
                                <button class="btn btn-secondary" onclick="resetImport()">Start Over</button>
                                <button class="btn btn-primary" id="btnImport" style="display:none" onclick="startImport()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    Import Valid Rows
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Done panel -->
                    <div class="card" id="panelDone" style="display:none;margin-top:16px">
                        <div style="text-align:center;padding:40px 24px">
                            <div style="width:64px;height:64px;background:rgba(67,160,71,0.12);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;color:#43a047">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="28"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <h2 style="font-size:1.1rem;margin-bottom:8px">Import Complete!</h2>
                            <p id="doneMessage" style="color:var(--text-muted);font-size:0.85rem;margin-bottom:24px"></p>
                            <div style="display:flex;gap:10px;justify-content:center">
                                <button class="btn btn-secondary" onclick="resetImport()">Import More</button>
                                <a href="../dashboard.php" class="btn btn-primary">View Customers</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Instructions -->
                <div>
                    <div class="card">
                        <div class="card-header-simple"><h3>CSV Format</h3></div>
                        <div style="padding:16px 20px">
                            <div class="template-section" style="margin-top:0">
                                <h4>Required Columns <span style="color:var(--accent)">*</span></h4>
                                <div class="column-tags">
                                    <?php
                                    $required = ['name','team_academy_name','country_code','mobile_number','address_line_1','city','state','pincode','country'];
                                    foreach ($required as $col): ?>
                                    <span class="column-tag required"><?= $col ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="template-section" style="margin-top:10px">
                                <h4>Optional Columns</h4>
                                <div class="column-tags">
                                    <span class="column-tag">email</span>
                                    <span class="column-tag">address_line_2</span>
                                </div>
                            </div>
                            <div style="margin-top:16px;font-size:0.8rem;color:var(--text-muted);line-height:1.7">
                                <p><strong>Country Code:</strong> Use format like <code style="font-size:0.75rem;background:var(--bg-secondary);padding:1px 5px;border-radius:4px">+91</code>, <code style="font-size:0.75rem;background:var(--bg-secondary);padding:1px 5px;border-radius:4px">+971</code></p>
                                <p style="margin-top:6px"><strong>Mobile:</strong> Digits only, 7–15 characters</p>
                                <p style="margin-top:6px"><strong>Pincode:</strong> Digits only</p>
                                <p style="margin-top:6px"><strong>Name:</strong> Minimum 3 characters</p>
                                <p style="margin-top:6px"><strong>Encoding:</strong> UTF-8 recommended</p>
                            </div>
                            <button class="btn btn-secondary btn-sm" style="width:100%;margin-top:16px;justify-content:center" onclick="downloadTemplate()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download CSV Template
                            </button>
                        </div>
                    </div>

                    <div class="card" style="margin-top:16px">
                        <div class="card-header-simple"><h3>Sample Data</h3></div>
                        <div style="padding:16px 20px;font-size:0.78rem;color:var(--text-muted)">
                            <div style="background:var(--bg-secondary);border-radius:8px;padding:12px;font-family:monospace;font-size:0.72rem;line-height:1.8;overflow-x:auto;white-space:nowrap">
                                name,team_academy_name,...<br>
                                Rajesh Kumar,Chennai FC,...<br>
                                Priya Sharma,Mumbai Club,...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="../assets/js/animations.js"></script>
<script src="../assets/js/app.js"></script>
<script>
let parsedRows = [];
let validRows  = [];

const REQUIRED_COLS = ['name','team_academy_name','country_code','mobile_number','address_line_1','city','state','pincode','country'];

// Drag and drop
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
});
document.getElementById('fileInput').addEventListener('change', e => {
    if (e.target.files[0]) handleFile(e.target.files[0]);
});

function handleFile(file) {
    if (!file.name.endsWith('.csv')) { showToast('Please upload a CSV file', 'error'); return; }
    if (file.size > 5 * 1024 * 1024) { showToast('File too large (max 5MB)', 'error'); return; }

    const sel = document.getElementById('selectedFile');
    sel.textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    sel.style.display = 'block';

    const reader = new FileReader();
    reader.onload = e => parseCSV(e.target.result);
    reader.readAsText(file, 'UTF-8');
}

function parseCSV(text) {
    const lines = text.trim().split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length < 2) { showToast('CSV must have at least one data row', 'error'); return; }

    const headers = lines[0].split(',').map(h => h.replace(/^"|"$/g, '').trim());
    parsedRows = [];

    for (let i = 1; i < lines.length; i++) {
        const vals = lines[i].match(/(".*?"|[^,]+|(?<=,)(?=,)|(?<=,)$|^(?=,))/g) || [];
        const row = {};
        headers.forEach((h, idx) => {
            row[h] = (vals[idx] || '').replace(/^"|"$/g, '').trim();
        });
        parsedRows.push(row);
    }

    const missing = REQUIRED_COLS.filter(c => !headers.includes(c));
    const parseResults = document.getElementById('parseResults');
    parseResults.style.display = 'block';

    if (missing.length > 0) {
        document.getElementById('parseErrors').style.display = 'block';
        document.getElementById('parseErrors').innerHTML = `<div style="background:rgba(229,57,53,0.1);border:1px solid rgba(229,57,53,0.2);border-radius:8px;padding:12px 14px;font-size:0.82rem;color:var(--accent)">
            <strong>Missing required columns:</strong> ${missing.join(', ')}
        </div>`;
        document.getElementById('btnValidate').style.display = 'none';
        return;
    }

    document.getElementById('parseStats').textContent = `${parsedRows.length} rows found`;

    // Preview table
    const previewHeaders = headers.slice(0, 8);
    document.getElementById('previewHead').innerHTML = `<tr>${previewHeaders.map(h => `<th>${h}</th>`).join('')}</tr>`;
    document.getElementById('previewBody').innerHTML = parsedRows.slice(0, 5).map(row =>
        `<tr>${previewHeaders.map(h => `<td>${escHtml(row[h] || '')}</td>`).join('')}</tr>`
    ).join('');

    document.getElementById('btnValidate').style.display = 'flex';
    setStep(2);
}

function validateData() {
    setStep(2);
    const log = document.getElementById('validationLog');
    validRows = [];
    const errors = [];

    parsedRows.forEach((row, i) => {
        const rowErrors = validateRow(row, i + 2);
        if (rowErrors.length > 0) {
            errors.push(...rowErrors);
        } else {
            validRows.push(row);
        }
    });

    document.getElementById('countValid').textContent   = validRows.length;
    document.getElementById('countInvalid').textContent = errors.length > 0 ? parsedRows.length - validRows.length : 0;
    document.getElementById('countTotal').textContent   = parsedRows.length;

    log.innerHTML = '';

    if (errors.length === 0) {
        log.innerHTML = `<div class="result-row"><div class="result-icon ok">✓</div><span>All ${parsedRows.length} rows are valid and ready to import.</span></div>`;
    } else {
        errors.slice(0, 50).forEach(err => {
            log.innerHTML += `<div class="result-row"><div class="result-icon err">✗</div><span>${escHtml(err)}</span></div>`;
        });
        if (errors.length > 50) {
            log.innerHTML += `<div class="result-row" style="color:var(--text-muted)">... and ${errors.length - 50} more errors</div>`;
        }
    }

    if (validRows.length > 0) {
        document.getElementById('btnImport').style.display = 'flex';
    }

    document.getElementById('panelImport').style.display = 'block';
    document.getElementById('panelImport').scrollIntoView({behavior:'smooth'});
}

function validateRow(row, rowNum) {
    const errors = [];
    if (!row.name || row.name.length < 3) errors.push(`Row ${rowNum}: Name must be at least 3 characters`);
    if (!row.team_academy_name) errors.push(`Row ${rowNum}: team_academy_name is required`);
    if (!row.country_code) errors.push(`Row ${rowNum}: country_code is required`);
    if (!row.mobile_number || !/^[0-9]{7,15}$/.test(row.mobile_number)) errors.push(`Row ${rowNum}: Invalid mobile_number (7-15 digits)`);
    if (!row.address_line_1) errors.push(`Row ${rowNum}: address_line_1 is required`);
    if (!row.city) errors.push(`Row ${rowNum}: city is required`);
    if (!row.state) errors.push(`Row ${rowNum}: state is required`);
    if (!row.pincode || !/^[0-9]+$/.test(row.pincode)) errors.push(`Row ${rowNum}: pincode must be numeric`);
    if (!row.country) errors.push(`Row ${rowNum}: country is required`);
    if (row.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(row.email)) errors.push(`Row ${rowNum}: Invalid email format`);
    return errors;
}

async function startImport() {
    if (!validRows.length) return;

    setStep(3);
    document.getElementById('btnImport').disabled = true;
    document.getElementById('importProgress').style.display = 'block';

    const CHUNK_SIZE = 20;
    let inserted = 0, failed = 0;
    const total = validRows.length;

    for (let i = 0; i < total; i += CHUNK_SIZE) {
        const chunk = validRows.slice(i, i + CHUNK_SIZE);

        for (const row of chunk) {
            try {
                const res = await apiFetch('../api/customers.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'create',
                        name: row.name, team_academy_name: row.team_academy_name,
                        email: row.email || null, country_code: row.country_code,
                        mobile_number: row.mobile_number, address_line_1: row.address_line_1,
                        address_line_2: row.address_line_2 || null, city: row.city,
                        state: row.state, pincode: row.pincode, country: row.country,
                    })
                });
                const json = await res.json();
                if (json.success) inserted++;
                else failed++;
            } catch { failed++; }
        }

        const pct = Math.round(((i + chunk.length) / total) * 100);
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressText').textContent = pct + '%';
    }

    setStep(4);
    document.getElementById('doneMessage').textContent = `${inserted} customers imported successfully.${failed ? ` ${failed} failed.` : ''}`;
    document.getElementById('panelImport').style.display = 'none';
    document.getElementById('panelDone').style.display = 'block';
    document.getElementById('panelDone').scrollIntoView({behavior:'smooth'});
    showToast(`Import complete: ${inserted} added`, 'success');
}

function resetImport() {
    parsedRows = []; validRows = [];
    document.getElementById('fileInput').value = '';
    document.getElementById('selectedFile').style.display = 'none';
    document.getElementById('parseResults').style.display = 'none';
    document.getElementById('btnValidate').style.display = 'none';
    document.getElementById('panelImport').style.display = 'none';
    document.getElementById('panelDone').style.display = 'none';
    document.getElementById('importProgress').style.display = 'none';
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('btnImport').style.display = 'none';
    document.getElementById('btnImport').disabled = false;
    setStep(1);
}

function setStep(n) {
    for (let i = 1; i <= 4; i++) {
        const s = document.getElementById('step' + i);
        s.className = 'step' + (i < n ? ' done' : i === n ? ' active' : '');
    }
}

function downloadTemplate() {
    const cols = ['name','team_academy_name','email','country_code','mobile_number','address_line_1','address_line_2','city','state','pincode','country'];
    const sample = 'Rajesh Kumar,Chennai FC Academy,rajesh@example.com,+91,9876543210,12 Anna Nagar,,Chennai,Tamil Nadu,600040,India';
    const csv = cols.join(',') + '\n' + sample;
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'customer_import_template.csv';
    a.click();
}
</script>
</body>
</html>
