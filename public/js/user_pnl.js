document.addEventListener('DOMContentLoaded', function () {
    const totalPnlUsd = window.userTotalPnl || 0;
    const userCurrency = window.userCurrency || 'USD';
    const pnlDisplay = document.getElementById('totalPnlDisplay');
    if (!pnlDisplay) {
        console.error("Element #totalPnlDisplay not found.");
        return;
    }
    const staticRates = {
        USD: 1,
        EUR: 1.085,
        // BTC — dynamic, from socket
    };

    function updatePnlDisplay(rate = null) {
        let convertedPnl;

        if (userCurrency === 'BTC') {
            if (!rate) return;
            convertedPnl = totalPnlUsd / rate;
        } else if (staticRates[userCurrency]) {
            convertedPnl = totalPnlUsd / staticRates[userCurrency];
        } else {
            convertedPnl = totalPnlUsd; // fallback to USD
        }

        const decimals = userCurrency === 'BTC' ? 6 : 2;
        pnlDisplay.textContent = userCurrency === 'USD'
            ? `${convertedPnl.toFixed(decimals)} USD`
            : `${convertedPnl.toFixed(decimals)} ${userCurrency} (${totalPnlUsd.toFixed(2)} USD)`;

    }

    if (userCurrency === 'BTC') {
        const socket = window.tradeSocket?.socket;

        if (socket && socket.addEventListener) {
            socket.addEventListener('message', function (event) {
                let data;
                try {
                    data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                } catch (e) {
                    console.error('WS parse error:', e);
                    return;
                }

                const assetName = (data.asset_name || '').replace('/', '').toUpperCase();
                if (assetName === 'BTCUSD' && data.bid) {
                    updatePnlDisplay(parseFloat(data.bid));
                }
            });
        } else {
            console.error("Socket not available for BTC rate.");
        }
    } else {
        updatePnlDisplay();
    }
});
