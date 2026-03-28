<img width="256" height="209" alt="spectora_logo" src="https://github.com/user-attachments/assets/2166df18-7009-466d-99b7-de29dae8bb66" />

#  Spectora: Website Monitoring Suite

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
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

##  Core Features

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

##  Technical Architecture

Spectora utilizes a modern, dockerized setup that includes all necessary dependencies for local audits.

```mermaid
graph TD
    A[Spectora App Container] --> B[Apache / PHP 8.4]
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

##  Installation

### Prerequisites
*   **Docker & Docker Compose**
*   **Hardware**: Minimum **2 GB RAM** (required for Chromium/Lighthouse processes)

### 1. Clone & Start
```bash
git clone https://github.com/Everlite/Spectora.git
cd Spectora
docker compose up -d --build
```

The entrypoint script automatically handles:
*   `.env` creation from `.env.example`
*   `APP_KEY` generation
*   Database migrations
*   Storage link creation

The application is now accessible at **http://localhost:8000**.

---

##  Configuration (.env)

Since Spectora uses no external APIs, configuration is minimal:

*   `DB_CONNECTION=sqlite`: Pre-configured by default.
*   `MAIL_*`: Configuration for sending reports.
*   **No API key required for PageSpeed!**

---

##  Production Deployment & Analytics Tracking

By default, Spectora is accessible at `http://localhost:8000`. If you want to use the **Analytics Tracking** feature (to track visitors on your clients' websites), your Spectora dashboard must be publicly accessible via a real domain/subdomain (e.g., `spectora.your-agency.com`).

### 1. Why a Public Domain is Required
Tracking external websites from a `localhost` instance is technically impossible because the client's browser wouldn't be able to reach your Spectora server to "sync" the visitor data. For global tracking, Spectora must be reachable via a public IP and a registered DNS record.

### 2. DNS & Subdomain Setup
1.  **A-Record**: Create an `A-Record` (e.g., `spectora.your-agency.com`) pointing to your server's public IP.
2.  **Subdomain vs. Main Domain**: We recommend using a dedicated subdomain so your main agency site remains independent.

### 3. Update .env
On your server, modify the `.env` file to set your public URL. This is crucial for the tracking script (`sp-core.js`) to generate correct absolute URLs:
```env
APP_URL=https://spectora.your-agency.com
```

### 4. Implementation Logic
Once Spectora is public, you can generate a **Tracking Snippet** in the domain's analytics tab. 
*   **The Snippet**: `<script src="https://spectora.your-agency.com/js/sp-core.js" data-domain="..." defer></script>`
*   **Automatic Host Detection**: The `sp-core.js` script is intelligent; it automatically detects its own origin and sends data back to your Spectora `/api/sync` endpoint, regardless of which client site it's embedded on.

### 5. Nginx & SSL (HTTPS)
For modern browsers to allow cross-domain tracking, **HTTPS is mandatory**. install Nginx and secure it with Certbot:

```bash
# Install Nginx & Certbot
apt update && apt install nginx certbot python3-certbot-nginx -y

# Setup SSL
certbot --nginx -d spectora.your-agency.com
```

---

##  Analytics: Privacy-by-Design

The Spectora tracking engine was built with the **GDPR** in mind. It provides accurate stats without compromising visitor privacy:
*   **No Cookies**: We do not use any tracking cookies.
*   **Anonymized Hashing**: Visitors are identified via a rotating daily hash composed of `IP + UserAgent + Date + APP_KEY`. This allows unique visitor counting without storing personally identifiable information (PII).
*   **No Third Parties**: No data ever leaves your server. Unlike Google Analytics, your client data is 100% yours.
---

##  Data Privacy & GDPR

Spectora is the ideal choice for European agencies:
1.  **Data Sovereignty**: All analytical data remains within your own infrastructure.
2.  **Zero Tracking**: No integration of Google Analytics, Fonts, or Maps in the dashboard.
3.  **Client Security**: Your client data is never transmitted to Google servers for analysis.

---

##  License & Credits

Developed for agencies that value privacy and independence. 

###  Recent Updates
*   **Infrastructure**: Upgraded core framework to Laravel 12.
*   **Monitoring Engine**: Optimized `CheckUrlJob` and `CheckDomainJob` for precise status tracking and preventing database race-conditions.
*   **Dashboard UI**: Restored HTML structural integrity for absolute tab-isolation (Monitoring vs. Analytics) and separated Chart.js tracking instances.

*   **Framework**: [Laravel 12](https://laravel.com)
*   **Frontend**: [Alpine.js](https://alpinejs.dev) & Tailwind CSS
*   **Monitoring**: [Lighthouse](https://developers.google.com/web/tools/lighthouse) (Local Version)

---
*Created by Everlite.*
