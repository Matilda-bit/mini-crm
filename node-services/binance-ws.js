// binance-ws.js
const WebSocket = require('ws');
const mysql = require('mysql2');
require('dotenv').config();
//BTC/USD only
const ws = new WebSocket('wss://stream.binance.com:9443/ws/btcusdt@bookTicker');

const userId = 3;

const connection = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  database: process.env.DB_NAME,
  password: process.env.DB_PASSWORD,
});

ws.on('open', () => {
  console.log('WebSocket connection opened');
});

ws.on('message', (data) => {
  const ticker = JSON.parse(data);

  //  u: 66411948049,
//   s: 'BTCUSDT',
//   b: '78852.05000000',
//   B: '0.27821000',
//   a: '78852.06000000',
//   A: '1.81029000'
//   const symbol = ticker.s;
//for another row in db/ added manually: data from- "symbol":"BTCEUR","bidPrice":"70416.09000000","bidQty":"0.06408000","askPrice":"70416.10000000","askQty":"0.00030000"},
  const bid = parseFloat(ticker.b);
  const ask = parseFloat(ticker.a);
  const now = new Date();
//   const assetName = symbol.substring(0, 3) + '/' + symbol.substring(3);

//   console.log(ticker);

  const sql = `
    INSERT INTO assets (asset_name, bid, ask, lot_size, date_update)
    VALUES ('BTC/USD', ?, ?, 10, ?)
    ON DUPLICATE KEY UPDATE
      bid = VALUES(bid),
      ask = VALUES(ask),
      date_update = VALUES(date_update),
      lot_size = VALUES(lot_size)
  `;

  connection.execute(sql, [bid, ask, now], (err) => {
    if (err) {
      console.error('Failed to update asset:', err);
    } else {
      console.log(`Updated BTC/USD - Bid: ${bid}, Ask: ${ask}`);
      
      const logSql = `
        INSERT INTO log (action_name, date_created, user_id)
        VALUES (?, ?, ?)
      `;
      connection.execute(logSql, [`Asset BTC/USD updated`, now, userId], (logErr) => {
        if (logErr) {
          console.error('Failed to log action:', logErr);
        } else {
          console.log('Action logged in the database');
        }
      });
    }
  });
});

ws.on('error', (err) => {
  console.error('WebSocket error:', err);
});
