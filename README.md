# 💼 Mini CRM Trading System

This project is a mini CRM application developed as part of a technical interview assignment. It simulates a simple trading system that includes user and agent management, real-time trading data, and full trade lifecycle support.

## 🚀 Features

- **👥 User & Agent Registration/Login**
  - Supports role-based access: `Admin`, `Rep`, and `User`
  - Auto logout after 10 minutes (only for agents and users)

- **🌿 Agent Hierarchy Management**
  - Assign agents to users or other agents
  - Admins see everything; reps see only their subtree

- **📡 Real-Time BTC/USD Rate Updates**
  - Integrated with Binance WebSocket API: `wss://stream.binance.com:9443/ws/btcusdt@bookTicker`
  - Fallback support via REST: `https://api.binance.com/api/v3/ticker/bookTicker`
  - Stores bid/ask values in the database

- **📊 Open & Close Trades**
  - Users and agents can open trades with:
    - Lot size(always 10) and lot count
    - SL (Stop Loss) and TP (Take Profit)
    - Live bid/ask-based calculations
  - Live profit/loss (PNL), pip value, and used margin computation
  - Trades auto-close on SL/TP or via manual action

- **📝 Logging System**
  - Actions like register, login, and assignments are stored in the `log` table
  - All trade operations and asset updates are logged in the DB

- **📈 Admin/Agent Dashboards**
  - Admins can manage all users and agents
  - Reps see only their assigned users and sub-agents
  - Tables include dropdowns for agent assignment
  - Live-updating table for open trades using WebSocket
  - Unified view for open and closed trades using [DataTables](https://datatables.net/)

- **💱 Multi-Currency Support**
  - Users can register with currency: `USD`, `EUR`, or `BTC`
  - All trade values, PNL, pip values, and margins are calculated in the user's currency
  - USD to EUR conversion rate used: `0.9215`

## 🛠️ Tech Stack

- **Backend**: PHP with Symfony 5.4 + Twig
- **Frontend**: HTML, CSS, JS, [DataTables](https://datatables.net/)
- **Database**: MySQL 5.7
- **WebSocket/REST**: Binance API
- **Optional**: Node.js service for real-time processing

## 📂 Project Setup

Instructions for local setup and database import will be provided soon.

---

> This project was created as part of a coding interview task and showcases my ability to develop a full-stack application with real-time functionality and complex role-based logic.


## ⚙️ Installation & Setup

To run this project locally:

### 1. 📥 Clone the Repository

```bash
git clone https://github.com/Matilda-bit/mini-crm.git
cd mini-crm
```
### 2. 📦 Install Node.js Dependencies

```bash
cd node-services
npm install
```
exact versions specified in package-lock.json:

```bash
npm ci
```

## generate ssl key, create new folder ssl in mini-crm/node-services/ssl/ => for cert.pem & key.pem and run here: 
```bash
openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes
```

Example:
```
Country Name (2 letter code) [AU]:IL
State or Province Name (full name) [Some-State]:Tel Aviv
Locality Name (eg, city) []:Tel Aviv
Organization Name (eg, company) [Internet Widgits Pty Ltd]:devYard
Organizational Unit Name (eg, section) []:
Common Name (e.g. server FQDN or YOUR name) []:127.0.0.1
Email Address []:
```

### 3. 🧪 Configure Environment Variables

Create a .env file in the project root:

```env
APP_ENV=dev
APP_SECRET=your_app_secret_here
DATABASE_URL="mysql://root:<password>@127.0.0.1:3306/mini_crm?serverVersion=8&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Node.js Service
DB_HOST=127.0.0.1
DB_USER=root
DB_PASSWORD=<password>
DB_NAME=mini_crm
APP_DEBUG=true
```



### 4. 🛢️ Setup MySQL Database

Install MySQL (version 8+ recommended)

Create a database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE mini_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
or
```bash
php bin/console doctrine:database:create
```

### 5. 🐘 Install PHP & Symfony Dependencies
From the root of the project:

```bash
composer install
```
If you don’t have Symfony CLI installed, you can install it from Symfony CLI docs.

6. 📋 Run Database Migrations
```bash
php bin/console doctrine:migrations:migrate
```

7. 🔥 Start Symfony Server

```bash
symfony server:stop
symfony server:start
```

8. 🔌 Start WebSocket Binance Listener

In a second terminal:
```bash 
node node-services/binance-ws-server.js
```

✅ You’re ready! Visit http://localhost:8000 in your browser.
