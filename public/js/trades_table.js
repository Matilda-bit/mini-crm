// /public/js/trades_table.js
console.log('trades_table.js loaded');

document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('#tradesTable');

    if (table) {
        $(table).DataTable();
        console.log('Trades table initialized');

        table.addEventListener('click', async (e) => {
            const button = e.target.closest('.closeTradeBtn');
            if (!button) return;

            console.log('click button');

            const tradeId = button.getAttribute('data-trade-id');
            const row = document.getElementById(`trade-${tradeId}`);

            try {
                const response = await fetch(`/close-trade/${tradeId}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) throw new Error(`Server error: ${response.status}`);

                const data = await response.json();

                if (!data.success) {
                    showToast('Error: ' + data.message, 'danger');
                    return;
                }

                row.querySelector(`#status-${data.trade.id}`).textContent = data.trade.status;
                row.querySelector(`#pnl-${data.trade.id}`).textContent = parseFloat(data.trade.pnl).toFixed(2);
                row.querySelector(`#closeRate-${data.trade.id}`).textContent = data.trade.closeRate;

                delete openTrades?.[data.trade.id]; // если переменная есть

                // Заменить кнопку на текст '---'
                button.replaceWith(document.createTextNode('---'));

                showToast(data.message, 'success');
            } catch (err) {
                showToast('Error: ' + err.message, 'danger');
            }
        });

    } else {
        console.warn('Trades table not found');
    }
});
