# Tenda Micro-ISP Router Project
![IMG-20260217-WA0009](https://github.com/user-attachments/assets/4c1f2d54-41d0-4f8f-824e-c523b84bcfdf)

## inside the device

![tenda internals](./tenda-inside.jpg)

this document focuses on the practical outcome and observations.  
for a detailed, step-by-step technical breakdown of the reverse-engineering process, see  
[full Docs](./Reverse-process.md)

**Router Device List**  
![Device List](https://github.com/Frost-bit-star/tendaN301-billing/blob/main/Screenshot%20(31).png)

![PHP](https://img.shields.io/badge/PHP-7.4+-blue) ![SQLite](https://img.shields.io/badge/SQLite-supported-orange) ![License](https://img.shields.io/badge/License-MIT-green) ![Build Status](https://img.shields.io/badge/Status-Active-brightgreen)

This project converts a Tenda router into a micro-ISP-style router by interacting with its internal API, reversing its firmware behavior, and using blocklists to control device internet access. Built in **PHP**, it uses **SQLite** to store router configurations.

---

## Table of Contents

1. [Project Overview](#project-overview)  
2. [Features](#features)  
3. [Architecture](#architecture)  
4. [Requirements](#requirements)  
5. [Installation](#installation)  
6. [Configuration](#configuration)  
7. [Usage](#usage)  
8. [Dashboard Screenshots](#dashboard-screenshots)  
9. [Security Considerations](#security-considerations)  
10. [File Structure & Components](#file-structure--components)  
11. [Technical Stack](#technical-stack)  
12. [How Data Flows Through the System](#how-data-flows-through-the-system)  
13. [Troubleshooting](#troubleshooting)  
14. [License](#license)  

---

## Project Overview

This project allows centralized control over multiple Tenda routers. It fetches connected devices, identifies online and blacklisted devices, and applies rules to allow or restrict internet access. Essentially, it turns Tenda routers into lightweight managed routers for small-scale ISP setups or network labs.

---

## Features

- Automatic login to Tenda routers using stored credentials  
- Fetches online devices and connection types (wired/wireless)  
- Fetches blacklisted devices  
- Allows managing which devices have unrestricted internet access  
- Returns structured JSON of devices and internet access status  
- Supports multiple routers via SQLite database  

---

## Architecture

### 1. **System Overview**

```
┌─────────────────────────────────────────────────────────────┐
│                    Web Dashboard (UI)                       │
│  (/pages/dashboard.php, /pages/add_user.php, etc.)         │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        ▼            ▼            ▼
   /api/control   /api/plans   /api/qos
   (routers)      (plans)      (throttle)
        │            │            │
        └────────────┼────────────┘
                     │
        ┌────────────▼────────────┐
        │   SQLite Database       │
        │  /db/routers.db         │
        │  (routers, devices,     │
        │   plans, billing)       │
        └────────────┬────────────┘
                     │
    ┌────────────────┼────────────────┐
    │                │                │
    ▼                ▼                ▼
 Sync Worker    Billing Worker   Device Tracker
(/auth/sync)   (/auth/billing)  (Real-time QoS)
```

### 2. **Frontend (Web Dashboard)**

- **Dashboard** (`/pages/dashboard.php`): Grid view of all routers with online/offline status and device counts
- **Devices View**: When a router is clicked, displays all connected devices with:
  - Hostname, IP, MAC address
  - Connection type (WiFi/Wired)
  - Available billing plans as clickable badges
- **Add User** (`/pages/add_user.php`): Assign billing plans to devices

### 3. **Backend API Layer**

#### **Router Management** (`/api/control.php`)
- **GET**: Fetch all registered routers with online status (TCP port check)
- **POST**: Add/Update/Delete routers with credentials
- Stores: router name, IP, port, password (encrypted)

#### **Device Management** (`/auth/login.php`)
- Authenticates to Tenda router via HTTP API
- Fetches online devices and their metadata
- Tracks device internet access status
- Returns JSON of all devices with connection details

#### **Plans API** (`/api/plans.php`)
- **GET**: Fetch all available billing plans
- **POST**: Create new plan (name, duration in days/hours/minutes)
- **PUT**: Update existing plan
- Plans are time-based subscriptions assigned to devices

#### **QoS/Throttle API** (`/api/qos.php`)
- **Block/Unblock**: Apply MAC filters to blacklist devices
- Rebuilds router's QoS table dynamically
- Real-time device throttling and bandwidth control
- Enforces internet access restrictions based on:
  - Active billing plan status
  - Admin-set blacklist rules
  - Device subscription expiration

#### **Billing/Sync** (`/auth/billing.php`, `/auth/sync.php`)
- Applies billing plans to devices (MAC-based)
- Updates device `internet_access` flag (1=enabled, 0=blocked)
- Syncs device status across all routers
- Monitors subscription expiration

### 4. **Database Schema** (`/db/routers.db`)

**Tables:**

```sql
-- Routers: Stores all managed Tenda devices
CREATE TABLE routers (
    id INTEGER PRIMARY KEY,
    name TEXT UNIQUE,
    ip TEXT,
    port INTEGER DEFAULT 80,
    password TEXT,
    online BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Plans: Billing/subscription plans
CREATE TABLE plans (
    id INTEGER PRIMARY KEY,
    name TEXT,
    days INTEGER DEFAULT 0,
    hours INTEGER DEFAULT 0,
    minutes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Devices: Connected devices with billing status
CREATE TABLE devices (
    id INTEGER PRIMARY KEY,
    router_id INTEGER,
    mac TEXT,
    plan_id INTEGER,
    internet_access BOOLEAN DEFAULT 1,  -- 1=has access, 0=blocked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY(router_id) REFERENCES routers(id),
    FOREIGN KEY(plan_id) REFERENCES plans(id)
);
```

### 5. **Worker System**

The project includes two worker execution modes:

#### **Sync Worker** (`/auth/sync.php`)
**Purpose**: Continuously monitors and syncs device status across all routers  
**How it runs**:
- **Via `php stack cron`**: Basic loop (runs every 60 seconds indefinitely)
- **Via `php stack run:worker`**: PM2-managed process (respawns on crash, persistent logging)

**What it does**:
1. Queries all routers from database
2. Authenticates to each router via HTTP API
3. Fetches current online devices
4. Fetches blacklisted devices
5. Compares with database records
6. Updates device status (online/offline)
7. Logs all changes to `/logs/sync.log`
8. Sleeps 60 seconds, then repeats

**Example workflow**:
```
1. Worker starts → reads all routers
2. Login to router → authenticate with stored password
3. Call /goform/getQos → get online/blacklist data
4. Compare with DB → detect changes
5. Update DB records → mark devices as online/offline
6. Log activity → timestamp, changes, errors
7. Sleep 60s → repeat
```

#### **Billing Worker** (`/auth/billing.php`)
**Purpose**: Apply time-based subscriptions to devices  
**Triggers**: 
- Manually via UI (when user clicks a plan badge)
- Can be triggered periodically to check expiration

**What it does**:
1. Takes device MAC, plan ID, router ID
2. Creates/updates device record in database
3. Sets `internet_access = 1` (enabled)
4. Associates device with billing plan
5. Stores subscription start/end times
6. Periodically checks expiration (via QoS worker)

---

### 6. **Throttling & Access Control**

**How throttling works:**

1. **Plan Assignment** (via UI or API)
   - User selects a device (by MAC)
   - Selects a plan (e.g., "1 Hour", "7 Days")
   - `billing.php` writes to database: `devices` table with `plan_id` and `internet_access=1`

2. **Sync Worker Monitoring**
   - Runs every 60 seconds
   - Checks each device's plan expiration
   - If expired: sets `internet_access=0`, marks device for blocking

3. **QoS Enforcement** (`/api/qos.php`)
   - Builds a "blacklist" from database
   - Devices with `internet_access=0` are added to router's MAC filter
   - Sends blacklist to Tenda router via HTTP API
   - Router automatically blocks these devices from accessing internet

4. **Real-time Control**
   - Admin can instantly block/unblock devices via UI
   - Calls `qos.php` with action: `block` or `unblock`
   - Updates database + router blacklist immediately

**Flow Diagram:**
```
User selects plan
    ↓
billing.php updates DB (plan_id, internet_access=1)
    ↓
Sync worker monitors (every 60s)
    ↓
Plan expired? → set internet_access=0
    ↓
qos.php builds blacklist from DB
    ↓
MAC filter applied to Tenda router
    ↓
Router blocks blacklisted MACs from internet
    ↓
Device can't access internet until plan renewed
```

---

### 7. **Logging & Debugging**

- **Sync Log**: `/logs/sync.log` – Device status changes, authentication attempts
- **WebSocket Log**: `/logs/ws.log` – Real-time updates from routers
- **Cron PID**: `/logs/cron.pid` – Process ID for running workers

---

## Requirements

- PHP 7.4+ with `pdo_sqlite` and `curl` extensions  
- Tenda router model N301  
- Web server (Apache/Nginx optional, or PHP built-in server)  

---

## Installation

### Step 1: Clone the repository

```bash
git clone https://github.com/Frost-bit-star/tendaN301-billing.git
cd tendaN301-billing
```
**Step 2: Install PHP and required extensions**
```
sudo apt update
sudo apt install -y php php-cli php-sqlite3 php-curl unzip
```
Step 3: Build and run the project
# Install project dependencies (if any)
```
php stack install

# Build the project
php stack build

# Start the server
php stack start
```

Once started, the server will be active on http://localhost:8000
.

## Configuration

### Step 1: Start the server

```bash
# Option A: Development mode (single terminal, no background workers)
php stack start

# Option B: Start with workers (requires PM2)
php stack dev           # Terminal 1: Web server
php stack run:worker    # Terminal 2: Background sync worker

# Option C: Use cron mode (simple loop)
php stack cron          # Runs sync in infinite loop
```

### Step 2: Add routers via web UI

1. Go to [http://localhost:8000](http://localhost:8000)  
2. Click "Add Router"  
3. Enter:
   - **Router Name**: Friendly name (e.g., "Floor 1 Router")
   - **IP Address**: Tenda router's IP (e.g., `192.168.0.1`)
   - **Port**: HTTP port (default: 80)
   - **Password**: Tenda admin password (from router settings)
4. Click "Add" → Router will appear on dashboard

### Step 3: Create billing plans

1. Click "Plans" in sidebar  
2. Click "Create Plan"  
3. Enter:
   - **Plan Name**: (e.g., "1 Hour", "7 Days", "Monthly")
   - **Duration**: Days/Hours/Minutes
4. Click "Create" → Plan saved to database

### Step 4: Assign plans to devices

1. Click on a router card → View devices
2. Click a plan badge next to a device's MAC
3. Device is instantly added to billing system
4. If sync worker is running, it will monitor expiration
5. When plan expires → device auto-blocked (if using workers)

### Environment Variables (Optional)

Create `.env` file in project root:

```env
DB_PATH=./db/routers.db
ROUTER_TIMEOUT=5
SYNC_INTERVAL=60
LOG_LEVEL=info
```

---

## Usage

### Dashboard Overview

**Step 1: Router Status**
- Grid view shows all routers
- Green dot = Online (reachable via TCP)
- Red dot = Offline (not responding)
- Shows connected device count

**Step 2: Device Management**
- Click router → View connected devices
- See: Hostname, IP, MAC, Connection Type (WiFi/Wired)
- Available plans shown as clickable badges

**Step 3: Billing**
- Click plan badge → Assign to device
- Device gets internet access for plan duration
- Sync worker monitors expiration
- Expired devices auto-blocked from internet

**Step 4: Manual Control**
- Right-click device → "Block" or "Unblock"
- Instantly blocks/unblocks device from all internet traffic
- Changes applied to router in real-time

### Command Line Operations

```bash
# Start web server
php stack start

# Start sync worker (PM2-managed)
php stack run:worker

# Stop worker
php stack stop:worker

# View logs
tail -f logs/sync.log      # Device sync activity
tail -f logs/ws.log        # WebSocket activity
tail -f logs/cron.pid      # Worker process ID
```

### API Examples

**Fetch all routers:**
```bash
curl http://localhost:8000/api/control.php
```

**Fetch devices for router:**
```bash
curl "http://localhost:8000/auth/login.php?id=1"
```

**Get all plans:**
```bash
curl http://localhost:8000/api/plans.php
```

**Block a device:**
```bash
curl -X POST http://localhost:8000/api/qos.php \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "mac": "00:11:22:33:44:55",
    "action": "block"
  }'
```

**Unblock a device:**
```bash
curl -X POST http://localhost:8000/api/qos.php \
  -H "Content-Type: application/json" \
  -d '{
    "id": 1,
    "mac": "00:11:22:33:44:55",
    "action": "unblock"
  }'
```

**Apply plan to device:**
```bash
curl "http://localhost:8000/auth/billing.php?id=1&paid_mac=00:11:22:33:44:55&plan_id=2"
```

---

### Dashboard Screenshots

**Dashboard Overview**  
![Dashboard](https://github.com/Frost-bit-star/tendaN301-billing/blob/main/Screenshot%20(30).png)


## Security Considerations

- **Router credentials** are stored in SQLite—ensure proper file permissions:
  ```bash
  chmod 600 db/routers.db    # Read/write for owner only
  chmod 700 db/              # Restrict directory access
  ```
- **Passwords stored plaintext** – Consider AES-256 encryption for production
- **Use HTTPS** when exposing dashboard externally:
  ```bash
  # Use Nginx/Apache reverse proxy with SSL
  # Or use ngrok for secure tunneling: ngrok http 8000
  ```
- **Restrict API access** – Implement authentication/tokens for external API calls
- **Rate limiting** – Add rate limits to prevent brute force attacks on router login
- **Cookie security** – Cookies stored in `/auth/*.cookie` should be cleared periodically
- **Regularly update** PHP and server packages to minimize vulnerabilities
- **Firewall**: Restrict access to router IPs—only allow from trusted networks

### Data Protection Checklist

- [ ] Set database file permissions to 600
- [ ] Enable HTTPS on production
- [ ] Add authentication to `/api/` endpoints
- [ ] Encrypt router passwords in database
- [ ] Implement API rate limiting
- [ ] Set up firewall rules for router access
- [ ] Regular database backups
- [ ] Log rotation for `/logs/` directory

---

## File Structure & Components

```
.
├── index.php                    # Main entry point / router
├── package.json                 # NPM dependencies
├── stack                        # CLI tool for building/running
├── README.md                    # This file
│
├── api/                         # REST APIs
│   ├── control.php              # Router CRUD operations
│   ├── plans.php                # Billing plan management
│   ├── qos.php                  # Device throttling/blocking
│   ├── common.js                # Shared JS utilities
│   ├── login.js                 # Device list fetching
│   ├── macro_config.js          # Router config macros
│   ├── router_files/            # Router management UI
│   │   ├── advanced.js
│   │   ├── login.html
│   │   ├── net-control.html
│   │   ├── userManage.js
│   │   └── wireless.js
│   └── dump/                    # Debug endpoints
│       └── login.html
│
├── auth/                        # Authentication & Billing
│   ├── login.php                # Device fetching from router
│   ├── billing.php              # Apply plans to devices
│   ├── billing.php              # v2 (extended features)
│   ├── sync.php                 # Worker: Monitor device status
│   ├── config.php               # Shared auth config
│   ├── getstatus.php            # Get router status
│   └── tenda.cookie             # Session cookie storage
│
├── components/                  # Reusable UI components
│   ├── header.php               # Page header
│   ├── footer.php               # Page footer
│   └── sidebar.php              # Navigation sidebar
│
├── pages/                       # Web pages
│   ├── dashboard.php            # Main dashboard (router grid)
│   ├── add_router.php           # Add/manage routers
│   ├── add_user.php             # Assign plans to devices
│   ├── users.php                # User/device list
│   └── plans.php                # Plan management
│
├── db/                          # Database
│   ├── routers.db               # SQLite database
│   └── schema.php               # Database schema generator
│
└── logs/                        # Runtime logs
    ├── sync.log                 # Device sync activity
    ├── ws.log                   # WebSocket logs
    └── cron.pid                 # Worker process ID
```

### Key Files Explained

| File | Purpose |
|------|---------|
| `stack` | CLI tool for starting dev/prod servers and workers |
| `api/control.php` | Manage routers (CRUD) |
| `api/plans.php` | Manage billing plans |
| `api/qos.php` | Block/unblock devices in real-time |
| `auth/login.php` | Fetch device list from Tenda router |
| `auth/billing.php` | Apply billing plans to devices |
| `auth/sync.php` | Background worker (syncs all routers) |
| `pages/dashboard.php` | Main UI—shows routers & devices |
| `db/routers.db` | SQLite database (routers, devices, plans) |
| `logs/sync.log` | Sync worker activity log |

---

## Technical Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 7.4+ with PDO (SQLite), cURL |
| **Frontend** | Vanilla JavaScript, HTML5, Bootstrap CSS |
| **Database** | SQLite3 (embedded, no server needed) |
| **Web Server** | PHP built-in server or Apache/Nginx |
| **Workers** | PM2 (optional) or simple PHP loop |
| **Router API** | HTTP REST (Tenda N301 firmware) |
| **Build Tool** | Custom `stack` CLI (shell script + PHP) |

---

## How Data Flows Through the System

### 1. **Initial Setup**

```
User visits dashboard
    ↓
loadRouters() → GET /api/control.php
    ↓
Returns all routers from database
    ↓
Dashboard displays router grid
```

### 2. **View Devices**

```
User clicks router
    ↓
showDevices(routerId) → GET /auth/login.php?id=routerId
    ↓
Backend:
  1. Loads router credentials from DB
  2. Authenticates to Tenda via HTTP API
  3. Fetches online devices via /goform/getQos
  4. Returns JSON of devices
    ↓
Frontend: Displays device table with plan badges
```

### 3. **Assign Plan**

```
User clicks plan badge
    ↓
redirectToAddUser(mac, planId)
    ↓
GET /auth/billing.php?id=routerId&paid_mac=MAC&plan_id=planId
    ↓
Backend:
  1. Loads router + device + plan info
  2. Creates/updates devices table row
  3. Sets internet_access=1 (enabled)
  4. Returns success JSON
    ↓
Frontend: Shows confirmation, refreshes table
```

### 4. **Monitor & Throttle (Sync Worker)**

```
Sync worker starts (every 60 seconds)
    ↓
Load all routers from DB
    ↓
For each router:
  1. Authenticate
  2. Fetch current online devices
  3. Fetch blacklist
  4. Compare with DB
  5. Update device status
    ↓
Check for expired plans
    ↓
Call qos.php → build blacklist
    ↓
Send MAC filter to Tenda router
    ↓
Router applies filter, blocks blacklisted devices
    ↓
Sleep 60 seconds, repeat
```

### 5. **Manual Block (Immediate)**

```
Admin right-clicks device → "Block"
    ↓
POST /api/qos.php {id, mac, action: "block"}
    ↓
Backend:
  1. Updates database (internet_access=0)
  2. Fetches current QoS state
  3. Adds MAC to blacklist
  4. Sends updated blacklist to router
    ↓
Router immediately blocks device
    ↓
Response: success JSON
    ↓
Frontend: Refreshes UI
```

---

## Troubleshooting

### Router not appearing online
- Check if router IP is correct
- Verify firewall allows TCP connection to router
- Test connectivity: `ping <router-ip>`
- Check `/logs/sync.log` for auth errors

### Devices not showing
- Verify router credentials in database
- Check if router is online (green dot on dashboard)
- Click "Refresh" button to sync manually
- Check sync worker is running: `php stack run:worker`

### Plan not applied to device
- Verify plan exists in Plans page
- Check if device MAC is correct (copy from device table)
- Verify `/auth/billing.php` returns success JSON
- Check database: `SELECT * FROM devices WHERE mac='...';`

### Throttling not working
- Ensure sync worker is running
- Check `/logs/sync.log` for errors
- Verify router QoS support (Tenda N301)
- Check MAC filter in router's web UI (manual verification)

### Workers not starting
- Check PM2 installed: `npm list pm2`
- Check `/logs/cron.pid` for process ID
- Review `/logs/sync.log` for errors
- Try starting with cron mode: `php stack cron`

---
Made with ❤️ by **mwakidenis**

