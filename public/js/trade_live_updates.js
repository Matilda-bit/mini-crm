// public/js/trade_live_updates.js

export function initLiveTradePnlUpdater(socket, openTrades) {
    if (!socket || !socket.addEventListener) {
        console.error("Socket is not ready or undefined.");
        return;
    }

    function calculatePnl(trade, assetData) {
        const conversionRate = (assetData.asset_name === 'BTC/USD' && trade.userCurrency === 'EUR') ? 0.9215 : 1;
        const pipValue = trade.tradeSize * 0.01 * conversionRate;
        let pnl = 0;

        if (trade.position === 'buy') {
            pnl = (assetData.ask - trade.entryRate) * pipValue * 100;
        } else if (trade.position === 'sell') {
            pnl = (trade.entryRate - assetData.bid) * pipValue * 100;
        }

        return parseFloat(pnl.toFixed(2));
    }

    socket.addEventListener('message', function(event) {
        let assetData;
        try {
            assetData = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
        } catch (e) {
            console.error("WS Parse Error", event.data);
            return;
        }

        for (const tradeId in openTrades) {
            const trade = openTrades[tradeId];
            if (assetData.asset_name === 'BTC/USD') {
                const pnl = calculatePnl(trade, assetData);
                const cell = document.getElementById('pnl-' + tradeId);
                if (cell) {
                    cell.textContent = pnl;
                    cell.style.transition = 'color 0.3s ease';
                    cell.style.color = pnl < 0 ? 'red' : pnl > 0 ? 'green' : 'black';
                }
            }
        }
    });
}
