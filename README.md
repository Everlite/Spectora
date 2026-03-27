# 🛡️ Spectora: Google-Free Agency Edition

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker)](https://www.docker.com)
[![GDPR](https://img.shields.io/badge/GDPR-Safe-003399?style=for-the-badge)](https://gdpr-info.eu/)
[![Google-Free](https://img.shields.io/badge/Google-Purged-green?style=for-the-badge)](https://github.com/Everlite/Spectora)

**Spectora Agency Edition** is a highly specialized, self-hosted monitoring tool for agencies. It was designed to provide a central dashboard for all client domains – without dependency on third-party providers and with maximum data privacy.

---

## 🚀 The "Google-Free" Philosophy

Unlike traditional monitoring tools, Spectora operates fully autonomously. All analyses take place **locally within your container**.

*   **No Google PageSpeed API Key**: Audits are performed via local Lighthouse CLI and Chromium.
*   **No Google Fonts**: We use a modern **System Font Stack**. No external requests, no tracking cookies, maximum loading speed.
*   **No CDNs**: All scripts (including Alpine.js) are bundled locally.
*   **Private Search Engine Crawler**: Our watchdog identifies itself as `SpectoraBot` to perform security checks without masquerading.

---

## 📊 Core Features

### 1. Uptime & Performance Monitoring
Real-time monitoring of availability and latency for your domains.
*   **Interval**: Every 15 minutes (configurable).
*   **Lighthouse Audits**: Full performance scores (Desktop/Mobile) generated directly within your container.

### 2. Security Watchdog
An intelligent scanner that checks websites for typical threats:
*   **Malware Keywords**: Scans for pharma-spam, gambling content, and malicious keywords.
*   **SEO Check**: Inspects for `display:none` manipulations and hidden links.
*   **Verification Checks**: Validates Search Console meta tags.

### 3. SSL & Domain Health
*   **SSL Status**: Displays the remaining validity days of your certificates.
*   **Health Report**: Color-coded dashboard for an immediate overview of critical issues.

### 4. Agency Reporting
Generate professional reports for your clients directly from the dashboard.

---

## 🛠️ Technical Architecture

Spectora utilizes a modern, dockerized setup that includes all necessary dependencies for local audits.

```mermaid
graph TD
    A[Spectora App Container] --> B[Apache / PHP 8.2]
    A --> C[Laravel Scheduler / Cron]
    A --> D[Lighthouse CLI]
    A --> E[Chromium Headless]
    
    C --> F[Uptime Check Job]
    C --> G[Local Lighthouse Job]
    
    G --> D
    D --> E
    E --> H[Target Website]
    
    B --> I[(Local SQLite DB)]
```

---

## 📥 Installation

### Prerequisites
*   **Docker & Docker Compose**
*   **Hardware**: Minimum **2 GB RAM** (required for Chromium/Lighthouse processes)

### 1. Setup
```bash
# Clone the repository
git clone https://github.com/Everlite/Spectora.git
cd Spectora

# Prepare configuration
cp .env.example .env
```

### 2. Start
```bash
docker-compose up -d --build
```

### 3. Initialization
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
```

The application is now accessible at **http://localhost:8000**.

---

## ⚙️ Configuration (.env)

Since Spectora uses no external APIs, configuration is minimal:

*   `DB_CONNECTION=sqlite`: Pre-configured by default.
*   `MAIL_*`: Configuration for sending reports.
*   **No API key required for PageSpeed!**

---

## 🛡️ Data Privacy & GDPR

Spectora is the ideal choice for European agencies:
1.  **Data Sovereignty**: All analytical data remains within your own infrastructure.
2.  **Zero Tracking**: No integration of Google Analytics, Fonts, or Maps in the dashboard.
3.  **Client Security**: Your client data is never transmitted to Google servers for analysis.

---

## 📝 License & Credits

Developed for agencies that value privacy and independence. 
*   **Framework**: [Laravel 11](https://laravel.com)
*   **Frontend**: [Alpine.js](https://alpinejs.dev) & Tailwind CSS
*   **Monitoring**: [Lighthouse](https://developers.google.com/web/tools/lighthouse) (Local Version)

---
*Created by Everlite.*
