<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Payflow Laravel') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f6f7f9;
            color: #1f2937;
        }

        main {
            width: min(92vw, 520px);
            padding: 32px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        h1 {
            margin-top: 0;
            color: #0f766e;
        }

        a {
            color: #2563eb;
        }
    </style>
</head>

<body>
    <main>
        <h1>Payflow Laravel</h1>
        <p>Create payments, process gateway responses, and credit wallets.</p>
        <p><a href="/">Open payment form</a></p>
        <p><a href="/docs/api">Open API documentation</a></p>
    </main>
</body>

</html>
