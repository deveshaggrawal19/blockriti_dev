# Blockriti Payment Gateway

## Setup

Requires a pretty recent version of Node that has good ES6 support. Developed on Node 7, so that's a good starting point.
After cloning, run `npm i` to download all dependencies. Then, `node src/index.js` will start it (make sure to configure first).

## Configuration:

### Backend

Copy config.example.json to config.json, then edit.

`modules`: object containing names of modules, then the path where to the entry point for that module. All required modules are listed in example.
`port`: port to listen on
`merchantFields`: extra data that merchants must have. Error will be thrown when creating a merchant if they don't have all of this data. They can specify additional stuff too if they want.

### Frontend

Edit the config things near the top of the file.

## Building the frontend:

`npm run compile`

## TODO:

* If a transaction does not get payment for a long period of time (24 hours?) poll less frequently
* Maybe allow transaction to expire after a certain amonut of time

## Key Features:

1. Create merchant (API key for merchant)
2. Integrate it on mercahnts site
3. pay amount on merchant site which will reflect in user account BTC balance.
4. Txn is populated in list.
5. On clicking list item I can view txn details.
6. Refund that txn.
7. Underpaid BTC amount, how it is handled.
8. Overpaid BTC amount, how it is handled.

# API Reference:

### Create Merchant:

/createMerchant

POST Data Example:

{
    "jwt": "82f3rghdueoud82h",
    "info": {
        "address": "1234 Smith Drive",
        "licenseNumber": 4321,
        "businessName": "CoolTech Inc."
    },
    "authorizedDomains": [
        "https://cooltech.com",
        "https://store.cooltech.com"
    ]
}

Info required params can be configured in config.json
Authorized domains are places where the widget may be placed. If the widget is placed on a site with a different domain it will give an error
Autosettle is set to false by default

Response Example:

{
    "status": "success",
}

You must call `getMerchantInfo` to get the api keys

### Get Merchant Info:

/getMerchantInfo

POST Data Example:

{
    "jwt": "098geouoghuhsh"
}

Response Example:

{
    "status": "success",
    "info": {
        "address": "1234 Smith Drive",
        "licenseNumber": 4321,
        "businessName": "CoolTech Inc."
    },
    "authorizedDomains": [
        "https://cooltech.com",
        "https://store.cooltech.com"
    ],
    "apiPublic": "982fguh2-32rc3gu23-29387",
    "apiSecret": "2980hsneohush-23498eou-2",
    "autoSettle": true
}

### Enable/Disable Autosettlement

/autoSettle

POST Data Example: {
    "jwt": "98eoguoehk",
    "autoSettle": false
}

Response Example:

{
    "status": "success"
}

### List Transactions:

/listTransactions

POST Data Example:

{
    "jwt": "9820hcoeutltsn"
}

Response Example:

{
    "status": "success",
    "txs": [
        {
            "txId": "9oe0hu0h",
            "startTime": 17283091,
            "state": "complete"
        },
        {
            "txId": "98ektoebk",
            "startTime": 92737492,
            "state": "pending"
        }
    ]
}

### Get Transaction Info:

/getTransactionInfo

POST Data Example:

{
    "txId": "20983ghuosehusnh"
}

Should use the txid from listTransactions or startTransaction

Response Example:

{
    "status": "success",
    "merchantInfo": {
        "address": "1234 Smith Drive",
        "licenseNumber": 4321,
        "businessName": "CoolTech Inc."
    },
    "merchant": 17053,
    "purchaseInfo": {
        "item": "iFun 5",
        "qty": 2
    },
    "requestOriginHeader": "https://store.cooltech.com",
    "requestedAmount": 120000500,
    "paidAmount": 120000500,
    "state": "unconfirmed",
    "address": "1Xcsothe098eobk2309874s",
    "qr": "data:image/png;base64,R0lGODlhyAAiALM...",
    "startTime": 1493048279354
}

### Refund Transaction:

/refundTransaction

POST Data Example:

{
    "jwt": "08eochthk",
    "txId": "238dugh09832e"
}

Response Example:

{
    "status": "success"
}

## Widget Endpoints:

These should probably not be called manually, they are handled by the widget.

### Start transaction:

/startTransaction

POST Data Example:

{
    "apiPublic": "r827fuh-3298h32rh",
    "amount": 132000,
    "info": {
        "item": "iFun 5",
        "qty": 2
    },
    "confirmedCallback": "https://store.cooltech.com/completePayment"
}

Response Example:

{
    "status": "success",
    "txId": "0982gu3-2983uh32-888",
    "address": "1XCockh9chg98g34",
    "qr": "data:image/png;base64,R082..."
}

## Pay With Prepaid

/payWithPrepaid

POST Data Example:

{
    "txId": "982u3-29398",
    "cardNum": "17053",
    "pin": 1111
}

Response Example:

{
    status: "success"
}

Failure response messages: 'Invalid card/pin' or 'Insufficient funds'
