
// State
let currentTab = 'image'; // image, data, doc
let selectedFile = null;

// DOM Elements
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const uploadUI = document.getElementById('upload-ui');
const progressUI = document.getElementById('progress-ui');
const successUI = document.getElementById('success-ui');
const errorUI = document.getElementById('error-ui');
const progressBar = document.getElementById('progress-bar');
const progressPercent = document.getElementById('progress-percent');
const downloadBtn = document.getElementById('download-btn');
const errorMsg = document.getElementById('error-msg');

// Tabs
function switchTab(tab) {
    currentTab = tab;

    // Update buttons
    ['image', 'data', 'doc', 'av'].forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.classList.add('bg-gray-800', 'text-white', 'shadow');
            btn.classList.remove('text-gray-400', 'hover:text-white');
        } else {
            btn.classList.remove('bg-gray-800', 'text-white', 'shadow');
            btn.classList.add('text-gray-400', 'hover:text-white');
        }
    });

    // Update options visibility
    document.querySelectorAll('.conversion-options').forEach(el => el.classList.add('hidden'));
    document.getElementById(`options-${tab}`).classList.remove('hidden');

    resetUI();
}

// Drag & Drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-active');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-active');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-active');

    if (e.dataTransfer.files.length) {
        handleFileSelect(e.dataTransfer.files[0]);
    }
});

dropZone.addEventListener('click', (e) => {
    // 1. Only allow click-to-upload if the Upload UI is visible
    if (uploadUI.classList.contains('hidden')) return;

    // 2. Ignore clicks on interactive elements (dropdowns)
    if (e.target.tagName !== 'SELECT' && e.target.tagName !== 'OPTION') {
        fileInput.click();
    }
});


fileInput.addEventListener('change', (e) => {
    if (fileInput.files.length) {
        handleFileSelect(fileInput.files[0]);
    }
});

const readyUI = document.getElementById('ready-ui');
const fileNameDisplay = document.getElementById('file-name-display');

const allowedTypes = {
    'image': ['image/jpeg', 'image/png', 'image/webp', 'image/bmp', 'image/gif'],
    'data': ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
    'data': ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
    'doc': ['application/pdf', 'text/plain', 'text/html', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'av': ['audio/mpeg', 'audio/wav', 'audio/mp4', 'video/mp4', 'video/quicktime', 'video/x-msvideo']
};

function handleFileSelect(file) {
    // Validate File Type
    // Simple check based on MIME type or extension for better UX
    const type = file.type;
    const name = file.name.toLowerCase();

    let isValid = false;

    // Check by MIME
    if (allowedTypes[currentTab].includes(type)) isValid = true;

    // Fallback: Check extensions (some CSVs/docs have weird MIMEs)
    if (!isValid) {
        if (currentTab === 'image' && /\.(jpg|jpeg|png|webp|bmp|gif)$/.test(name)) isValid = true;
        if (currentTab === 'data' && /\.(csv|xlsx|xls)$/.test(name)) isValid = true;
        if (currentTab === 'doc' && /\.(pdf|txt|html|htm|docx)$/.test(name)) isValid = true;
        if (currentTab === 'av' && /\.(mp3|wav|mp4|mov|avi|m4a)$/.test(name)) isValid = true;
    }

    if (!isValid) {
        showError(`Invalid file for "${currentTab}" mode.\nPlease upload a valid file.`);
        return;
    }

    selectedFile = file;
    // Update UI to show selected file and "Convert" button
    fileNameDisplay.textContent = file.name;

    // Hide default upload prompt parts if you want, or just append. 
    // Let's hide the big icon/text to reduce clutter
    // document.querySelector('#upload-ui > .w-16').classList.add('hidden'); // Icon
    // document.querySelector('#upload-ui > h3').classList.add('hidden'); // Text

    readyUI.classList.remove('hidden');
}

// Global function for the 'x' button
window.removeSelectedFile = function (e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    resetUI();
}

// Make sure resetUI is available globally for the onclick handler
window.resetUI = resetUI;
window.uploadFile = uploadFile;
window.handleDownload = handleDownload;
window.closeModal = closeModal;
window.closeErrorModal = closeErrorModal;
window.switchTab = switchTab;

function uploadFile() {
    if (!selectedFile) return;

    // UI Updates
    uploadUI.classList.add('hidden');
    // Ensure readyUI is also hidden when progress starts
    readyUI.classList.add('hidden');

    progressUI.classList.remove('hidden');
    successUI.classList.add('hidden');
    errorUI.classList.add('hidden');

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('type', currentTab);

    // Get format based on current tab
    const formatSelect = document.getElementById(`format-${currentTab}`);
    formData.append('format', formatSelect.value);

    const xhr = new XMLHttpRequest();

    // Timeouts for heavy AI tasks
    xhr.timeout = 300000; // 5 minutes

    // Progress
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressPercent.textContent = percent + '%';
        }
    });

    // Complete
    xhr.addEventListener('load', () => {
        if (xhr.status === 200) {
            try {
                const res = JSON.parse(xhr.responseText);
                if (res.success) {
                    showSuccess(res.download_url, res.file_name);
                } else {
                    showError(res.message);
                }
            } catch (e) {
                showError('Invalid server response');
            }
        } else {
            showError('Server error: ' + xhr.status);
        }
    });

    xhr.addEventListener('error', () => {
        showError('Network error');
    });

    xhr.addEventListener('timeout', () => {
        showError('Request timed out (server took too long)');
    });

    xhr.open('POST', 'api/convert.php');
    xhr.send(formData);
}

// function showSuccess(url) { ... } REMOVED/UPDATED below
let downloadUrl = '';
let downloadName = ''; // Store clean filename

function showSuccess(url, name) {
    progressUI.classList.add('hidden');
    successUI.classList.remove('hidden');
    downloadUrl = url;
    downloadName = name; // From server
    downloadBtn.onclick = handleDownload;
}

async function handleDownload(e) {
    e.preventDefault();
    if (!downloadUrl) return;

    // Prevent double clicking
    downloadBtn.disabled = true;
    const originalText = downloadBtn.innerText;
    downloadBtn.innerText = 'Saving...';
    downloadBtn.classList.add('opacity-50', 'cursor-not-allowed');

    try {
        const response = await fetch(downloadUrl);
        const blob = await response.blob();

        // Use server-provided name OR fallback to URL name
        const filename = downloadName || downloadUrl.split('/').pop();

        if ('showSaveFilePicker' in window) {
            try {
                const handle = await window.showSaveFilePicker({
                    suggestedName: filename,
                    types: [{
                        description: 'Converted File',
                        accept: { '*/*': ['.' + filename.split('.').pop()] }
                    }],
                });

                const writable = await handle.createWritable();
                await writable.write(blob);
                await writable.close();

                // Show Success Modal
                document.getElementById('modal-success').classList.remove('hidden');
                document.getElementById('modal-success').classList.add('flex');

            } catch (err) {
                if (err.name !== 'AbortError') {
                    console.error('File Picker failed, falling back:', err);
                    // Fallback only on genuine error
                    fallbackDownload(blob, filename);
                }
            }
        } else {
            fallbackDownload(blob, filename);
        }

    } catch (err) {
        alert('Download failed: ' + err.message);
    } finally {
        // Reset button
        downloadBtn.disabled = false;
        downloadBtn.innerText = originalText;
        downloadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function fallbackDownload(blob, filename) {
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);

    // Also show modal for fallback logic
    document.getElementById('modal-success').classList.remove('hidden');
    document.getElementById('modal-success').classList.add('flex');
}

function closeModal() {
    document.getElementById('modal-success').classList.add('hidden');
    document.getElementById('modal-success').classList.remove('flex');
    resetUI(); // Clear file and reset to upload state
}

function closeErrorModal() {
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('modal-error').classList.remove('flex');
    resetUI();
}

function showError(msg) {
    progressUI.classList.add('hidden');
    // Using modal instead of inline error
    const modal = document.getElementById('modal-error');
    const msgEl = document.getElementById('modal-error-msg');
    if (modal && msgEl) {
        msgEl.innerText = msg; // Text content to avoid XSS, but allowing newlines if we handled them. innerText handles \n usually.
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        // Fallback if modal missing
        alert(msg);
    }
}

function resetUI() {
    selectedFile = null;
    fileInput.value = '';
    progressBar.style.width = '0%';
    progressPercent.textContent = '0%';

    uploadUI.classList.remove('hidden');
    readyUI.classList.add('hidden');
    progressUI.classList.add('hidden');
    successUI.classList.add('hidden');
    errorUI.classList.add('hidden');
}

// Initial Setup
switchTab('image');
