const fs = require('fs');
const https = require('https');
const WebSocket = require('ws');
const mysql = require('mysql2');
const path = require('path');
require('dotenv').config();

// --- Настройки ---
const PORT = 8080;
const SEND_INTERVAL_MS = 1000;
const userId = 3;

// --- HTTPS + WebSocket сервер ---
const server = https.createServer({
    cert: fs.readFileSync(path.join(__dirname, 'ssl', 'cert.pem')), 
    key: fs.readFileSync(path.join(__dirname, 'ssl', 'key.pem'))   
});
const wss = new WebSocket.Server({ server });
const clients = [];

// --- Подключение к MySQL ---
const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    database: process.env.DB_NAME,
    password: process.env.DB_PASSWORD,
});

// --- Подключение к Binance WebSocket ---
const binanceWS = new WebSocket('wss://stream.binance.com:9443/ws/btcusdt@bookTicker');

let lastFrontendSendTime = 0;
let logCount = 0;

// --- Обработка подключения фронтов ---
wss.on('connection', (ws) => {
    console.log(`[${new Date().toLocaleString()}] Frontend connected`);
    clients.push(ws);
    ws.send(JSON.stringify({ message: 'Welcome to the WebSocket server!' }));

    ws.on('close', () => {
        const i = clients.indexOf(ws);
        if (i !== -1) clients.splice(i, 1);
        console.log(`[${new Date().toLocaleString()}] Frontend disconnected`);
    });
});

// --- Рассылка на фронты ---
function broadcastToFrontend(payload) {
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(payload));
        }
    });
}

// --- Binance WS ---
binanceWS.on('open', () => {
    console.log('Connected to Binance WebSocket');
});

binanceWS.on('message', (data) => {
    const ticker = JSON.parse(data);
    const now = new Date();

    const transformed = {
        asset_name: 'BTC/USD',
        bid: parseFloat(ticker.b),
        ask: parseFloat(ticker.a),
        lot_size: parseFloat(ticker.A),
        date_update: now
    };

    //   const assetName = symbol.substring(0, 3) + '/' + symbol.substring(3);
    //   console.log(ticker);

    // --- Запись в БД ---
    const sql = `
        INSERT INTO assets (asset_name, bid, ask, lot_size, date_update)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        bid = VALUES(bid),
        ask = VALUES(ask),
        lot_size = VALUES(lot_size),
        date_update = VALUES(date_update)
    `;

    db.execute(sql, [transformed.asset_name, transformed.bid, transformed.ask, transformed.lot_size, transformed.date_update], (err) => {
        if (err) return console.error('DB error:', err);

        // --- Логирование ---
        const logSql = `INSERT INTO log (action_name, date_created, user_id) VALUES (?, ?, ?)`;
        db.execute(logSql, [`Asset ${transformed.asset_name} updated`, now, userId], (logErr) => {
            if (logErr) console.error('Log error:', logErr);
        });
    });

    // --- Отправка на фронт ---
    const nowMs = Date.now();
    if (nowMs - lastFrontendSendTime > SEND_INTERVAL_MS) {
        broadcastToFrontend(transformed);
        lastFrontendSendTime = nowMs;

        if (logCount < 5) {
            console.log('Sending to frontend:', transformed);
            logCount++;
        }
    }
});

binanceWS.on('error', (err) => {
    console.error('Binance WS error:', err);
});

// --- Запуск сервера ---
server.listen(PORT, () => {
    console.log(`WebSocket server running at wss://127.0.0.1:${PORT}`);
});
