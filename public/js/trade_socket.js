// public/js/trade_socket.js
if (!window.tradeSocket) {
    class TradeSocket {
        constructor() {
            this.socket = new WebSocket("wss://127.0.0.1:8080");
            this.listeners = [];

            this.socket.addEventListener("open", () => {
                console.log("WebSocket connected");
            });

            this.socket.addEventListener("message", (event) => {
                let data;
                try {
                    data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                } catch (e) {
                    console.error("Error parsing WS message:", event.data);
                    return;
                }

                this.listeners.forEach((cb) => cb(data));
            });

            this.socket.addEventListener("error", (error) => {
                console.error("WebSocket error:", error);
            });

            this.socket.addEventListener("close", () => {
                console.log("WebSocket closed");
            });
        }

        onmessage(callback) {
            this.listeners.push(callback); 
        }

        send(data) {
            if (this.socket.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify(data));
            } else {
                console.error("WebSocket is not open.");
            }
        }
    }

    window.tradeSocket = new TradeSocket();
}
