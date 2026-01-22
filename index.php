<?php
// Auto-Installer / Dependency Check
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Required - Universal File Converter</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-900 text-white flex items-center justify-center h-screen">
        <div class="max-w-md p-8 bg-gray-800 rounded-lg shadow-xl border border-gray-700">
            <h1 class="text-2xl font-bold mb-4 text-red-500">Missing Dependencies</h1>
            <p class="mb-4 text-gray-300">It looks like the required libraries are not installed.</p>
            <p class="mb-4">Please run the following command in your terminal:</p>
            <div class="bg-black p-4 rounded text-green-400 font-mono mb-4 select-all">
                composer install
                <br><span class="text-gray-500"># Or if you don\'t have composer global:</span><br>
                php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');" && php composer-setup.php && php composer.phar install
            </div>
            <p class="text-sm text-gray-400">After installing, refresh this page.</p>
        </div>
    </body>
    </html>';
    exit;
}
require __DIR__ . '/vendor/autoload.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universal File Converter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .drag-active {
            border-color: #3B82F6 !important;
            background-color: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>

<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="border-b border-gray-800 bg-gray-900/50 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold tracking-tight">Kyle <span class="text-blue-500">Eurie</span>
                </h1>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col items-center justify-center p-4">

        <div class="w-full max-w-3xl">
            <!-- Tabs -->
            <div class="flex space-x-1 bg-gray-900 p-1 rounded-lg mb-8 mx-auto w-fit">
                <button onclick="switchTab('image')" id="tab-image"
                    class="tab-btn px-6 py-2 rounded-md text-sm font-medium bg-gray-800 text-white shadow">Images</button>
                <button onclick="switchTab('data')" id="tab-data"
                    class="tab-btn px-6 py-2 rounded-md text-sm font-medium text-gray-400 hover:text-white">Data</button>
                <button onclick="switchTab('doc')" id="tab-doc"
                    class="tab-btn px-6 py-2 rounded-md text-sm font-medium text-gray-400 hover:text-white">Documents</button>
                <button onclick="switchTab('av')" id="tab-av"
                    class="tab-btn px-6 py-2 rounded-md text-sm font-medium text-gray-400 hover:text-white">Audio/Video</button>
            </div>

            <!-- Conversion Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden">
                <div id="drop-zone"
                    class="border-2 border-dashed border-gray-700 rounded-xl p-12 text-center transition-all duration-200 cursor-pointer hover:border-gray-500">
                    <input type="file" id="file-input" class="hidden">

                    <div id="upload-ui">
                        <div class="w-16 h-16 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Drag & Drop your file here</h3>
                        <p class="text-gray-400 text-sm mb-6">or click to browse</p>

                        <!-- Dynamic Options based on Tab -->
                        <div id="options-image" class="conversion-options">
                            <label class="text-xs text-gray-500 uppercase font-bold tracking-wider">Convert to:</label>
                            <select id="format-image"
                                class="bg-gray-800 border-none text-white text-sm rounded ml-2 focus:ring-1 focus:ring-blue-500">
                                <option value="png">PNG</option>
                                <option value="jpg">JPG</option>
                                <option value="webp">WEBP</option>
                                <option value="rembg">Remove Background (Auto)</option>
                            </select>
                        </div>

                        <div id="options-data" class="conversion-options hidden">
                            <label class="text-xs text-gray-500 uppercase font-bold tracking-wider">Convert to:</label>
                            <select id="format-data"
                                class="bg-gray-800 border-none text-white text-sm rounded ml-2 focus:ring-1 focus:ring-blue-500">
                                <option value="xlsx">Excel (XLSX)</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>

                        <div id="options-doc" class="conversion-options hidden">
                            <label class="text-xs text-gray-500 uppercase font-bold tracking-wider">Convert to:</label>
                            <select id="format-doc"
                                class="bg-gray-800 border-none text-white text-sm rounded ml-2 focus:ring-1 focus:ring-blue-500">
                                <option value="pdf">PDF</option>
                                <option value="docx">DOCX (from Text)</option>
                                <option value="txt">Extract Text (from PDF)</option>
                            </select>
                        </div>

                        <div id="options-av" class="conversion-options hidden">
                            <label class="text-xs text-gray-500 uppercase font-bold tracking-wider">Convert to:</label>
                            <select id="format-av"
                                class="bg-gray-800 border-none text-white text-sm rounded ml-2 focus:ring-1 focus:ring-blue-500">
                                <option value="mp3">MP3 (Extract Audio)</option>
                                <option value="mp4">MP4 (Video from Audio)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Readiness/Confirmation UI -->
                    <div id="ready-ui" class="hidden mt-6">
                        <div class="bg-gray-800 rounded-lg p-4 mb-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3 overflow-hidden">
                                <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span id="file-name-display"
                                    class="text-sm text-gray-200 truncate font-mono">filename.ext</span>
                            </div>
                            <button id="remove-file-btn" onclick="removeSelectedFile(event)"
                                class="text-gray-500 hover:text-red-400 relative z-20 p-2 rounded-full hover:bg-gray-700 transition">
                                <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <button onclick="uploadFile()"
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg transition transform hover:scale-[1.02]">
                            Start Conversion
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div id="progress-ui" class="hidden">
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="text-gray-400">Converting...</span>
                            <span id="progress-percent" class="text-white">0%</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2">
                            <div id="progress-bar" class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                                style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Success UI -->
                    <div id="success-ui" class="hidden">
                        <div
                            class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2 text-white">Conversion Successful!</h3>
                        <button id="download-btn"
                            class="inline-block mt-4 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition shadow-lg hover:shadow-blue-500/30">
                            Download File
                        </button>
                        <br>
                        <button onclick="resetUI()"
                            class="mt-4 text-sm text-gray-500 hover:text-white underline">Convert another file</button>
                    </div>

                    <!-- Error UI -->
                    <div id="error-ui" class="hidden">
                        <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2 text-red-500">Conversion Failed</h3>
                        <p id="error-msg" class="text-gray-400 text-sm mb-4">Something went wrong.</p>
                        <button onclick="resetUI()" class="text-sm text-gray-500 hover:text-white underline">Try
                            again</button>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="modal-success" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div
            class="bg-gray-900 border border-gray-700 p-8 rounded-2xl shadow-2xl max-w-sm text-center transform transition-all scale-100">
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Download Complete!</h3>
            <p class="text-gray-400 mb-6">Your file has been saved successfully.</p>
            <button onclick="closeModal()"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Close
            </button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="modal-error" class="fixed inset-0 bg-black/80 hidden items-center justify-center z-50 backdrop-blur-sm">
        <div
            class="bg-gray-900 border border-gray-700 p-8 rounded-2xl shadow-2xl max-w-sm text-center transform transition-all scale-100">
            <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Error</h3>
            <p id="modal-error-msg" class="text-gray-400 mb-6">Something went wrong.</p>
            <button onclick="closeErrorModal()"
                class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                Close
            </button>
        </div>
    </div>

    <footer class="text-center py-6 text-gray-600 text-sm">
        &copy; <?php echo date('Y'); ?> Universal Converter. All rights reserved.
    </footer>

    <script src="assets/js/app.js"></script>
</body>

</html>