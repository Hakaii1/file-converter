# Universal File Converter (PHP)

A professional, feature-rich file converter web application built with PHP 8.x, Tailwind CSS, and Python integrations.

## Features

- **Images**:
  - Convert between JPG, PNG, WEBP.
  - **AI Background Removal**: Automatically remove backgrounds using AI (requires Python).
- **Audio/Video**:
  - **Extract Audio**: Convert Video (MP4, MOV, etc.) to MP3.
  - **Create Video**: Convert MP3 to MP4 (with a black background) for sharing on video platforms.
- **Data**: Convert CSV to Excel (XLSX) and vice-versa.
- **Documents**: Convert HTML/Text to PDF, Text to Word (DOCX), and extract text from PDF.

## Prerequisites

To run this project fully (including AI features), you need:

1.  **PHP 8.0+** (with `gd`, `zip`, `xml` extensions enabled).
2.  **Composer** (PHP Dependency Manager).
3.  **Python 3.8+** (for Background Removal and Audio/Video features).
4.  **FFmpeg** (Optional but recommended, though the project tries to use the Python-embedded version automatically).

## Installation

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/yourusername/file_converter.git
    cd file_converter
    ```

2.  **Install PHP Dependencies**:
    ```bash
    composer install
    ```

3.  **Install Python Dependencies** (Required for Background Removal & A/V):
    ```bash
    pip3 install rembg[cli] imageio-ffmpeg
    ```
    *Note: `rembg` handles background removal, and `imageio-ffmpeg` provides the ffmpeg binary.*

## Running Locally

1.  **Start PHP Server**:
    You can use the built-in PHP development server for quick testing:
    ```bash
    php -S localhost:8000
    ```

2.  **Access the App**:
    Open your browser and navigate to: [http://localhost:8000](http://localhost:8000)

## Publishing to GitHub

Yes, you can publish this to GitHub!

1.  **Initialize Git** (if not done):
    ```bash
    git init
    # The project already has a .gitignore to exclude vendor/ and uploads/
    ```

2.  **Commit and Push**:
    ```bash
    git add .
    git commit -m "Initial release of Universal File Converter"
    git branch -M main
    git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
    git push -u origin main
    ```

## Deploying Anywhere (Production)

You can host this on any server that supports PHP and Python (e.g., DigitalOcean, Railway, Heroku, or a VPS).

**Important Note for Production**:
- Ensure **Python 3** is installed on the server.
- Ensure `shell_exec` and `exec` functions are **enabled** in your `php.ini` (shared hosts often disable these).
- The `uploads/` directory must be writable (`chmod 777 uploads`).

### Heroku / Railway Support
A `Procfile` is included for Heroku-like deployments. You may need to add a `requirements.txt` for Python dependencies if the host supports multi-buildpacks (PHP + Python).
