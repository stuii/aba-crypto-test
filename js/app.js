document.addEventListener("DOMContentLoaded", function() {
    getCurrentBalance();
    loadAllTx();

    document.getElementById('add-transaction').addEventListener('click', function(){
        document.getElementById('overlay').classList.add('show');
    });

    document.querySelector('button').addEventListener('click', function(){
        addNewTx();
    });

});

function getCurrentBalance(){
    fetch('/api/portfolio')
    .then(response => response.json())
    .then(data => {
        var fiat = 0;
        for(let symbol in data['data']){
            var symbols = document.querySelector('.symbols');
            var symbolContent = document.createElement('div');
            symbolContent.classList.add('symbol');

            var symbolName = document.createElement('div');
            symbolName.classList.add('symbol__name');
            symbolName.textContent = data['data'][symbol]['symbol'];

            var symbolBalance = document.createElement('div');
            symbolBalance.classList.add('symbol__balance');
            symbolBalance.textContent = (data['data'][symbol]['balance']/1000000).toString();

            fiat += data['data'][symbol]['fiat'];
            console.log(fiat);

            symbolContent.appendChild(symbolName);
            symbolContent.appendChild(symbolBalance);
            symbols.appendChild(symbolContent);

            document.querySelector('.fiat__balance').textContent = ('CHF ' + Math.round(fiat/100).toString() + '.' +(fiat%100));
        }
    });
}

function loadAllTx(){

    fetch('/api/transactions')
        .then(response => response.json())
        .then(data => {

            for(let tx in data['data']) {
                var transactions = document.querySelector('.transactions');
                var txContent = document.createElement('div');
                txContent.classList.add('transaction');


                var txType = document.createElement('div');
                txType.classList.add('transaction__type');
                txType.classList.add(data['data'][tx]['type'].toLowerCase());
                txType.textContent = data['data'][tx]['type'];

                var txTime = document.createElement('div');
                txTime.classList.add('transaction__time');
                txTime.textContent = data['data'][tx]['time'];

                var txSymbol = document.createElement('div');
                txSymbol.classList.add('transaction__symbol');
                txSymbol.textContent = data['data'][tx]['symbol'];

                var txAmount = document.createElement('div');
                txAmount.classList.add('transaction__amount');
                txAmount.textContent = (data['data'][tx]['amount'] / 1000000).toString();

                var txLeft = document.createElement('div');
                txLeft.classList.add('transaction--left');
                txLeft.appendChild(txType);
                txLeft.append(txTime);

                var txRight = document.createElement('div');
                txRight.classList.add('transaction--right');
                txRight.appendChild(txSymbol);
                txRight.append(txAmount);

                txContent.appendChild(txLeft);
                txContent.appendChild(txRight);

                transactions.appendChild(txContent);
            }
        });
}

function addNewTx(){
    (async () => {
        var symbol = document.getElementById('currency');
        symbol = symbol.options[symbol.selectedIndex].value;

        var type = document.getElementById('type');
        type = type.options[type.selectedIndex].value;

        var amount = document.getElementById('amount').value*1000000;

        var time = document.getElementById('time').value;

        const rawResponse = await fetch('/api/transaction', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                'symbol' : symbol,
                'type': type,
                'amount': amount,
                'time': time
            })
        });
        const content = await rawResponse.json();
        document.getElementById('overlay').classList.remove('show');

    })();
}