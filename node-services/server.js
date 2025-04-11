const fs = require('fs');
const https = require('https');
const WebSocket = require('ws');
const path = require('path');

const server = https.createServer({
    cert: fs.readFileSync(path.join(__dirname, 'ssl', 'cert.pem')), 
    key: fs.readFileSync(path.join(__dirname, 'ssl', 'key.pem'))   
});

const wss = new WebSocket.Server({ server }); 

// const wss = new WebSocket.Server({ port: 8080 }); // WebSocket сервер на порту 8080

const clients = []; // Массив для хранения всех подключённых клиентов

wss.on('connection', (ws) => {
    const currentTime = new Date().toLocaleString();
    console.log(`[${currentTime}] Frontend connected`);
    clients.push(ws); // Добавляем клиента в массив

    // Отправка приветственного сообщения при подключении
    ws.send(JSON.stringify({ message: 'Welcome to the WebSocket server!' }));

    // Удаление клиента из массива, когда он отключается
    ws.on('close', () => {
        const disconnectTime = new Date().toLocaleString();
        console.log(`[${disconnectTime}] Frontend disconnected`);
        const index = clients.indexOf(ws);
        if (index !== -1) clients.splice(index, 1);
    });
});

// Функция для рассылки сообщений всем подключённым клиентам
function broadcast(message) {
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message); // Отправка сообщения клиенту
        }
    });
}

// Пример: через 10 секунд рассылаем всем клиентам информацию
setInterval(() => {
    const message = JSON.stringify({ update: 'This is a test message for all clients' });
    broadcast(message);
}, 10000);


server.listen(8080, () => {
    console.log('WebSocket server is running at wss://127.0.0.1:8080');
});